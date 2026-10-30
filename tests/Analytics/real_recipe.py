"""Opt-in only: real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8.

Separate PHP process, fresh Chrome profile, exact destination filter, automatic shutdown.
No private env files loaded or changed. Ordinary mock tests never invoke this entrypoint.
"""
import argparse
from collections import Counter
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import sys
import time
import urllib.request
from urllib.parse import parse_qs, urlencode, urlsplit

sys.dont_write_bytecode = True
from real_driver import Driver, MEASUREMENT_ID, ORIGIN, allowed_external, parameters

ROOT = Path(__file__).resolve().parents[2]
CANARY = 'GA4_RECIPE_CANARY_'
SENSITIVE_QUERY_KEYS = {'cid','sid','_p','client_id','session_id','user_id','uid','_ga','gclid','dclid','gdid'}


def guard_checks():
    good = 'https://www.google-analytics.com/g/collect?tid=' + MEASUREMENT_ID
    assert allowed_external(good, 'POST', 'en=page_view') == 'collect'
    assert allowed_external('https://www.googletagmanager.com/gtag/js?id='+MEASUREMENT_ID,'GET') == 'library'
    for url, body in [(good.replace(MEASUREMENT_ID,'G-MWZX1GYXZE'),''),
        (good,'tid=G-MWZX1GYXZE&en=page_view'), (good+'&tid=G-MWZX1GYXZE',''),
        (good.replace('www.google-analytics.com','evil.google-analytics.com'),''),
        (good.replace('/g/collect','/other'),''), (good.replace('https:','http:'),''),
        ('https://www.google.com/ccm/collect?tid='+MEASUREMENT_ID,'')]:
        assert allowed_external(url,'POST',body) is None


def sanitize(report):
    aliases = {}
    sensitive = SENSITIVE_QUERY_KEYS
    def alias(value, kind):
        key = (kind,json.dumps(value,sort_keys=True))
        if key not in aliases:
            aliases[key] = f'<{kind}-{sum(k[0]==kind for k in aliases)+1}>'
        return aliases[key]
    def clean(value, key=''):
        if key in sensitive:
            return alias(value,'browser' if key in ('cid','client_id') else 'session' if key in ('sid','session_id') else 'identifier')
        if key == 'session' and isinstance(value,str):
            return alias(value,'tab')
        if key == 'value':
            return alias(value,'cookie')
        if isinstance(value,dict):
            return {k:clean(v,k) for k,v in value.items()}
        if isinstance(value,list):
            return [clean(v,key) for v in value]
        if isinstance(value,str) and key == 'url':
            url = urlsplit(value)
            return url._replace(query=urlencode(clean(parse_qs(url.query)),doseq=True)).geturl()
        if isinstance(value,str) and key == 'body':
            return '\n'.join(urlencode(clean(parse_qs(line)),doseq=True) for line in value.splitlines())
        return value
    return clean(report)


def attach_netlog(report, path):
    """Use network-service responses for beacons no longer attached to a DevTools frame.

    Works on raw or sanitized reports. Private identifiers are excluded from correlation;
    equal request signatures are matched in chronological order, each source only once.
    """
    log = json.loads(Path(path).read_text(encoding='utf-8'))
    names = {value:key for key,value in log['constants']['logEventTypes'].items()}
    requests = {}
    for event in log['events']:
        params = event.get('params',{})
        if names[event['type']]=='URL_REQUEST_START_JOB' and 'url' in params:
            url = urlsplit(params['url'])
            if url.path=='/g/collect' and url.hostname in ('www.google-analytics.com','region1.google-analytics.com'):
                requests[event['source']['id']] = {'url':params['url'],'status':None,
                    'source_id':event['source']['id'],'start_tick_ms':event['time'],
                    'start_time':(float(event['time'])+float(log['constants']['timeTickOffset']))/1000}
    for event in log['events']:
        record = requests.get(event['source']['id'])
        if record is None:
            continue
        params = event.get('params',{})
        if names[event['type']]=='HTTP_TRANSACTION_READ_RESPONSE_HEADERS':
            for header in params.get('headers',[]):
                match = re.match(r'(?:HTTP/\S+\s+|:status:\s*)(\d{3})',header)
                if match:
                    record.update(status=int(match[1]),response_tick_ms=event['time'],
                        response_time=(float(event['time'])+float(log['constants']['timeTickOffset']))/1000)
        if params.get('net_error'):
            record['net_error'] = params['net_error']
    def signature(url):
        parts = urlsplit(url)
        query = {k:v for k,v in parse_qs(parts.query).items() if k not in SENSITIVE_QUERY_KEYS}
        return (parts.scheme,parts.netloc,parts.path,json.dumps(query,sort_keys=True))
    available = list(requests.values())
    for record in report.get('network',[]):
        if record.get('kind')!='collect' or record.get('decision')!='continue':
            continue
        match = next((item for item in available if signature(item['url'])==signature(record['url'])),None)
        if match:
            record['netlog_status'] = match['status']
            record['netlog_source_id'] = match['source_id']
            available.remove(match)
    report['netlog_requests'] = [{**record,'url':urlsplit(record['url'])._replace(query=urlencode(
        {k:v for k,v in parse_qs(urlsplit(record['url']).query).items() if k not in SENSITIVE_QUERY_KEYS},doseq=True)).geturl()}
        for record in requests.values()]
    for item in report.get('after_withdrawal_requests',[]):
        match=next((r for r in report.get('network',[]) if signature(r['url'])==signature(item['url'])
            and 'netlog_status' in r),None)
        if match:
            item['netlog_status']=match['netlog_status']
            item['netlog_source_id']=match['netlog_source_id']
    return report


def audit_report(report):
    """Offline evidence review; safe to run against a previously sanitized JSON report."""
    checks = report.setdefault('checks',[])
    # Idempotent so the same evidence can be reviewed without sending another event.
    checks[:] = [c for c in checks if not c['name'].startswith('Evidence audit:')]
    requests = [r for r in report.get('network',[]) if r['kind']=='collect' and r['decision']=='continue']
    events = [(r,p) for r in requests for p in parameters(r['url'],r['body']) if 'en' in p]
    report['request_events'] = dict(Counter(p['en'][0] for r,p in events))
    responded = lambda r: r.get('status') in (200,204) or r.get('netlog_status') in (200,204)
    report['http_acknowledged_events'] = dict(Counter(p['en'][0] for r,p in events if responded(r)))
    observations = report.get('observations',[])
    native_calls=[o for o in observations if o['kind']=='native-transport' and '/g/collect' in o.get('url','')]
    for request in requests:
        native=next((o for o in native_calls if o['url']==request['url'] and o['session']==request['session']),None)
        if native:
            request.update(native_transport=native['transport'],native_time=native['time'],
                native_to_network_ms=(request['time']-native['time'])*1000)
            native_calls.remove(native)
    for trial in report.get('withdrawal_trials',[]):
        trial['timing_evidence']=[{
            'native_delta_ms':(r['native_time']-trial['cutoff'])*1000 if 'native_time' in r else None,
            'network_delta_ms':(r['time']-trial['cutoff'])*1000,
            'response_delta_ms':(r['response_time']-trial['cutoff'])*1000 if 'response_time' in r else None,
            'classification':('native-after-refusal' if r['native_time']>=trial['cutoff'] else
                'native-before-refusal-network-observed-after' if r['time']>=trial['cutoff'] else 'native-and-network-before-refusal')
                if 'native_time' in r else 'native-time-unobserved',
        } for r in requests if trial['cutoff']-6 <= r['time'] <= trial['end']]
    report['queued_events'] = dict(Counter(o['command'][1] for o in observations if o['kind']=='queue' and o['command'][0]=='event'))
    report['bfcache'] = [o for o in observations if o['kind'] in ('pageshow','bfcache-not-used')]
    for label in (() if report.get('suite')=='withdrawal-only' else ('anonymous','authenticated')):
        native = [(r,p) for r,p in events if r['phase']==label+'-reserve' and p['en']==['reserve_click']]
        checks.append({'name':'Evidence audit: '+label+' native reserve delivery confirmed',
            'passed':any(responded(r) for r,p in native),
            'detail':{'attempts':len(native),'http_responses':[r.get('status') for r,p in native],
                'network_service_responses':[r.get('netlog_status') for r,p in native],
                'note':'No response means unconfirmed delivery, not proof of loss. Diagnostic control excluded.'}})
    pages = {'/':'Accueil','/rendez-vous/types':'Prestations','/rendez-vous/types/1':'Fiche prestation'}
    bad = []
    for r,p in events:
        url = urlsplit(p.get('dl',[''])[0])
        if (url.scheme+'://'+url.netloc != ORIGIN or url.path not in pages or url.query or url.fragment
            or p.get('dt') != [pages.get(url.path)] or any(p.get('dr',[])) or 'uid' in p
            or set(k for k in p if k.startswith(('ep.','epn.','up.','upn.'))) - {'ep.service_id','ep.display_context'}
            or ('ep.service_id' in p and p['ep.service_id'] not in (['1'],['2']))
            or ('ep.display_context' in p and p['ep.display_context'] not in
                (['home_featured'],['service_page'],['catalogue_ajax']))):
            bad.append({'phase':r['phase'],'params':p})
    checks.append({'name':'Evidence audit: actual payloads use allowed public metadata and business parameters',
        'passed':bool(events) and not bad,'detail':bad})
    report['validated'] = bool(checks) and not report.get('fatal_error') and all(c['passed'] for c in checks)
    report['production_validated'] = False
    return report


def run_recipe(d, results):
    tab = d.tab()
    results.update(browser=d.call('Browser.getVersion')['product'],cookies=[],checks=[])

    def check(name, condition, detail=None):
        results['checks'].append({'name':name,'passed':bool(condition),'detail':detail})
        print(('PASS ' if condition else 'FAIL ') + name, flush=True)

    def phase(name):
        d.phase = name

    def events(name):
        return Counter(e['params']['en'][0] for e in d.events(name))

    def withdraw(session, name):
        phase(name)
        # The consent module is independent and may initialize after the private DOM.
        if not d.wait(session,'!!document.querySelector("[data-analytics-status]")?.textContent',30):
            raise RuntimeError('Consent coordinator not ready for withdrawal')
        d.click(session,'[data-analytics-manage]')
        d.click(session,'[data-analytics-choice="refused"]')
        marks = [o['time'] for o in d.observations if o['kind']=='consent-written' and o.get('choice')=='refused' and o['phase']==name]
        if not marks:
            raise RuntimeError('Withdrawal write was not observed')
        cutoff = marks[-1] if marks else time.time()
        d.pump(3)
        check(name+': no frame/cookies', not d.evaluate(session,'!!document.querySelector("iframe")') and not d.cookies(session))
        after = [r for r in d.collect() if r['time'] >= cutoff]
        check(name+': no requests initiated after withdrawal', not after,
            {'initiated_before':sum(r['time'] < cutoff for r in d.collect()), 'initiated_after':len(after)})
        return cutoff

    def accept(session, name):
        phase(name)
        d.click(session,'[data-analytics-manage]')
        d.click(session,'[data-analytics-choice="accepted"]')
        d.pump(10) # Real gtag batches events; drain the batch before changing the observed phase.

    phase('before-consent')
    d.go(tab,'/?probe='+CANARY+'QUERY#'+CANARY+'FRAGMENT')
    results['local_context'] = d.evaluate(tab,'({origin:location.origin,secureContext:isSecureContext,userAgent:navigator.userAgent})')
    d.pump(2)
    check('Before consent: no library, collection or analytics cookies',
        not d.collect() and not d.cookies(tab) and not any(r['kind']=='library' for r in d.requests.values()))
    phase('initial-refusal')
    d.click(tab,'[data-analytics-choice="refused"]')
    d.go(tab,'/rendez-vous/types/1')
    check('Refusal survives navigation with no library/cookies', not d.cookies(tab)
        and not any(r['kind']=='library' for r in d.requests.values()))
    d.go(tab,'/?probe='+CANARY+'QUERY#'+CANARY+'FRAGMENT')
    accept(tab,'accept-home')
    cookies = d.cookies(tab)
    results['cookies'].append({'stage':'accepted-home','items':cookies})
    check('Real library responded 200', any(r['kind']=='library' and r.get('status')==200 for r in d.requests.values()))
    check('Real Secure cookies exist', bool(cookies) and all(c['secure'] for c in cookies))
    check('Home: one actual page_view', events('accept-home')['page_view']==1, dict(events('accept-home')))
    check('Collection response received', any(r.get('status') in (200,204) for r in d.collect()))

    phase('turbo-detail')
    d.click(tab,'[data-analytics-event="discover_session_click"]')
    d.wait(tab,'location.pathname === "/rendez-vous/types/1"')
    d.pump(10)
    check('Turbo detail: discover + page_view + view_service once', events('turbo-detail')==Counter(
        discover_session_click=1,page_view=1,view_service=1),dict(events('turbo-detail')))
    phase('duplicate-hooks')
    d.evaluate(tab,'document.dispatchEvent(new Event("turbo:load")); document.dispatchEvent(new Event("DOMContentLoaded")); document.dispatchEvent(new Event("analytics:service-displayed"))')
    d.pump(7)
    check('Repeated hooks emit no duplicate explicit events', not (events('duplicate-hooks').keys() & {'page_view','view_service'}))
    phase('turbo-history-back')
    d.evaluate(tab,'history.back()')
    d.wait(tab,'location.pathname === "/"')
    d.pump(10)
    check('History return: one new page_view',events('turbo-history-back')['page_view']==1,dict(events('turbo-history-back')))
    phase('direct-detail')
    d.go(tab,'/rendez-vous/types/1?probe='+CANARY+'DIRECT#'+CANARY+'DIRECT_FRAGMENT')
    d.pump(10)
    check('Direct detail: one page_view and view_service',events('direct-detail')['page_view']==1 and events('direct-detail')['view_service']==1,dict(events('direct-detail')))
    phase('catalogue')
    d.go(tab,'/rendez-vous/types')
    d.pump(10)
    phase('ajax-detail')
    d.click(tab,'a[href="/rendez-vous/types/1"][data-action]')
    d.wait(tab,'!!document.querySelector("[data-analytics-service]")')
    d.pump(10)
    check('AJAX: one view_service and no extra page_view',events('ajax-detail')['view_service']==1 and events('ajax-detail')['page_view']==0,dict(events('ajax-detail')))

    for authenticated in [False,True]:
        label = 'authenticated' if authenticated else 'anonymous'
        if authenticated:
            d.call('Network.setCookie',{'name':'analytics_test_user','value':'yes','url':ORIGIN,'path':'/'},tab)
            phase('authenticated-detail')
            d.go(tab,'/rendez-vous/types/1')
            d.pump(10)
        previous = d.events()[-1]['params'] if d.events() else {}
        before = {c['name']:c['value'] for c in d.cookies(tab)}
        d.evaluate(tab,'window.recipePublicDocument = true')
        phase(label+'-reserve')
        d.click(tab,'[data-analytics-event="reserve_click"]')
        private = '/rendez-vous/type/1/form' if authenticated else '/login'
        d.wait(tab,f'location.pathname === {json.dumps(private)}')
        d.pump(1)
        check(label+': full private navigation',d.evaluate(tab,'window.recipePublicDocument === undefined'))
        d.evaluate(tab,f'document.title={json.dumps(CANARY+"PRIVATE_TITLE")}; document.body.insertAdjacentHTML("beforeend","<p>{CANARY}PRIVATE_DOM</p>"); history.replaceState(null,"",location.pathname+"?probe={CANARY}PRIVATE_QUERY#{CANARY}PRIVATE_FRAGMENT")')
        phase(label+'-private-idle')
        cutoff = time.time()
        d.pump(4)
        check(label+': no private frame or collection',not [r for r in d.collect() if r['time']>=cutoff] and not d.evaluate(tab,'!!document.querySelector("iframe")'))
        after = {c['name']:c['value'] for c in d.cookies(tab)}
        browser_cookie = 'audience_G_7ZGZR4L2F8_ga'
        check(label+': browser cookie retained and session cookie present on private arrival',
            bool(before.get(browser_cookie)) and before.get(browser_cookie)==after.get(browser_cookie)
            and set(before)==set(after), {'browser_cookie_unchanged':before.get(browser_cookie)==after.get(browser_cookie),
                'session_cookie_may_update':True})
        check(label+': reserve_click request emitted',events(label+'-reserve')['reserve_click']==1,dict(events(label+'-reserve')))
        phase(label+'-public-return')
        d.click(tab,'a[href="/"]')
        d.wait(tab,'location.pathname === "/"')
        d.pump(10)
        returned = d.events(label+'-public-return')
        current = returned[-1]['params'] if returned else {}
        check(label+': same browser and session on short return',bool(previous.get('cid')) and
            previous.get('cid')==current.get('cid') and bool(previous.get('sid')) and previous.get('sid')==current.get('sid'),
            {'same_cid':previous.get('cid')==current.get('cid'), 'same_sid':previous.get('sid')==current.get('sid')})
        results['cookies'].append({'stage':label+'-return','items':d.cookies(tab)})
        phase(label+'-browser-back')
        d.evaluate(tab,'history.back()')
        d.wait(tab,f'location.pathname === {json.dumps(private)}')
        d.pump(3)
        check(label+': browser back to private does not collect',not d.collect(label+'-browser-back') and not d.evaluate(tab,'!!document.querySelector("iframe")'))
        withdraw(tab,label+'-withdraw-private')
        phase(label+'-cached-public-after-withdrawal')
        d.evaluate(tab,'history.back()')
        d.wait(tab,'location.pathname.startsWith("/rendez-vous/types")')
        d.pump(3)
        check(label+': cached public return after withdrawal stays stopped',
            not d.cookies(tab) and not d.evaluate(tab,'!!document.querySelector("iframe")')
            and not d.collect(label+'-cached-public-after-withdrawal'))
        phase(label+'-return-after-withdrawal')
        d.click(tab,'a[href="/"]')
        d.wait(tab,'location.pathname === "/"')
        d.pump(2)
        check(label+': refusal does not block return or reload tag',not d.cookies(tab) and not d.evaluate(tab,'!!document.querySelector("iframe")'))
        if not authenticated:
            accept(tab,'reaccept-for-authenticated')

    # Diagnostic control only: keep the public document alive to distinguish event generation
    # from delivery lost on navigation. This does NOT validate a real reservation link.
    accept(tab,'reaccept-before-reserve-control')
    phase('reserve-control-detail')
    d.go(tab,'/rendez-vous/types/1')
    d.pump(10)
    phase('reserve-control-no-navigation')
    d.evaluate(tab,'document.addEventListener("click", event => { if(event.target.closest("[data-analytics-event=reserve_click]")) event.preventDefault(); }, {once:true})')
    d.click(tab,'[data-analytics-event="reserve_click"]')
    d.pump(10)
    check('Diagnostic only: reserve_click acknowledged when public document stays alive',
        any(e['params']['en']==['reserve_click'] and e['request'].get('status') in (200,204)
            for e in d.events('reserve-control-no-navigation')))
    phase('public-before-withdrawal')
    d.go(tab,'/')
    d.pump(10)
    withdraw(tab,'withdraw-loaded-public')
    phase('slow-library')
    d.call('Network.setCacheDisabled',{'cacheDisabled':True},tab)
    d.tag_mode = 'slow'
    d.click(tab,'[data-analytics-manage]')
    d.click(tab,'[data-analytics-choice="accepted"]')
    for attempt in range(60):
        if d.held:
            break
        d.pump(.25)
    check('Slow load: real library response held before execution',bool(d.held))
    cutoff = withdraw(tab,'withdraw-during-load')
    for fetch_id, session in d.held:
        d.send('Fetch.continueResponse',{'requestId':fetch_id},session)
    d.held.clear()
    d.tag_mode = 'normal'
    d.call('Network.setCacheDisabled',{'cacheDisabled':False},tab)
    d.pump(10)
    check('Releasing slow response cannot resurrect tag or cookies',not d.cookies(tab)
        and not d.evaluate(tab,'!!document.querySelector("iframe")') and not [r for r in d.collect() if r['time']>=cutoff])
    accept(tab,'reaccept-multiple-tabs')
    phase('second-tab')
    second = d.tab()
    d.go(second,'/rendez-vous/types/1')
    d.pump(3)
    check('Second tab has real Analytics context before withdrawal',d.evaluate(second,'!!document.querySelector("iframe")') and bool(d.cookies(second)))
    d.call('Page.bringToFront',session=tab)
    cutoff = withdraw(tab,'withdraw-multiple-tabs')
    d.pump(2)
    check('Other tab stops and removes cookies via storage event',not d.evaluate(second,'!!document.querySelector("iframe")')
        and not d.cookies(second) and not [r for r in d.collect() if r['time']>=cutoff])
    d.call('Page.navigate',{'url':'about:blank'},second)
    phase('google-failure')
    d.call('Network.setCacheDisabled',{'cacheDisabled':True},tab)
    d.tag_mode = 'fail'
    d.click(tab,'[data-analytics-manage]')
    d.click(tab,'[data-analytics-choice="accepted"]')
    d.pump(2)
    check('Google failure is actually injected',any(r['kind']=='library' and r['decision']=='block'
        and r['phase']=='google-failure' for r in d.requests.values()))
    d.click(tab,'[data-analytics-event="discover_session_click"]')
    d.wait(tab,'location.pathname === "/rendez-vous/types/1"')
    d.click(tab,'[data-analytics-event="reserve_click"]')
    check('Google failure does not block reservation entry',d.wait(tab,'location.pathname.startsWith("/rendez-vous/type/")'))
    d.wait(tab,'document.readyState !== "loading" && !!document.querySelector("[data-analytics-config]") && !document.querySelector("[data-analytics-event=reserve_click]")')
    withdraw(tab,'final-refusal')
    d.tag_mode = 'normal'
    names = Counter(e['params']['en'][0] for e in d.events())
    results['request_events'] = dict(names)
    acknowledged = Counter(e['params']['en'][0] for e in d.events() if e['request'].get('status') in (200,204))
    results['http_acknowledged_events'] = dict(acknowledged)
    results['queued_events'] = dict(Counter(o['command'][1] for o in d.observations if o['kind']=='queue' and o['command'][0]=='event'))
    check('All four intended events received HTTP acknowledgement', all(acknowledged[name] for name in ['page_view','discover_session_click','view_service','reserve_click']),dict(acknowledged))
    leak = [r for r in d.requests.values() if r['decision']=='continue' and CANARY in (r['url']+r['body']+r['referer_header'])]
    check('No synthetic canary transmitted to Google',not leak,{'leaking_requests':len(leak)})
    check('No other measurement destination continued',all(allowed_external(r['url'],r['method'],r['body']) for r in d.requests.values() if r['decision']=='continue'))
    check('Every observed external request has an interception decision',not any(r['decision']=='pending' for r in d.requests.values()))
    check('No JavaScript exceptions',not d.errors,d.errors)
    results['bfcache'] = [o for o in d.observations if o['kind'] in ('pageshow','bfcache-not-used')]


def native_click_recipe(d, results):
    """Focused real navigation for network-service logging of detached-frame beacons."""
    tab = d.tab()
    results.update(browser=d.call('Browser.getVersion')['product'],checks=[],cookies=[])
    for authenticated in (False,True):
        label = 'authenticated' if authenticated else 'anonymous'
        if authenticated:
            d.call('Network.setCookie',{'name':'analytics_test_user','value':'yes','url':ORIGIN,'path':'/'},tab)
        d.phase = label+'-detail'
        d.go(tab,'/rendez-vous/types/1')
        if not authenticated:
            if not d.wait(tab,'document.querySelector("[data-analytics-status]")?.textContent === "Aucun accord en cours."',30):
                raise RuntimeError('Consent coordinator did not initialize; native diagnostic not exercised')
            d.click(tab,'[data-analytics-choice="accepted"]')
        d.pump(10)
        if not d.cookies(tab) or not d.events(label+'-detail'):
            raise RuntimeError('Real Analytics did not initialize; native diagnostic not exercised')
        d.phase = label+'-reserve'
        d.click(tab,'[data-analytics-event="reserve_click"]')
        expected = '/rendez-vous/type/1/form' if authenticated else '/login'
        landed = d.wait(tab,f'location.pathname === {json.dumps(expected)} && document.readyState !== "loading" && !document.querySelector("[data-analytics-event=reserve_click]")')
        d.pump(10) # The public frame is destroyed; no artificial navigation delay.
        results['checks'].append({'name':label+' native private arrival','passed':landed and not d.evaluate(tab,'!!document.querySelector("iframe")')})
        print(label+' native navigation observed',flush=True)
    d.phase = 'focused-final-refusal'
    d.click(tab,'[data-analytics-manage]')
    d.click(tab,'[data-analytics-choice="refused"]')
    d.pump(3)
    results['checks'].append({'name':'Focused cleanup: no frame or cookies','passed':not d.cookies(tab) and not d.evaluate(tab,'!!document.querySelector("iframe")')})


def withdrawal_recipe(d, results, delays=(3,)):
    """Keep the original network criterion AND timestamp application/native calls separately."""
    first=d.tab()
    results.update(browser=d.call('Browser.getVersion')['product'],checks=[],cookies=[],withdrawal_trials=[])
    for index, delay in enumerate(delays):
        d.phase=f'accept-{index}'
        d.go(first,'/')
        if not d.wait(first,'!!document.querySelector("[data-analytics-status]")?.textContent',30):
            raise RuntimeError('Consent coordinator did not initialize')
        d.click(first,'[data-analytics-manage]')
        d.click(first,'[data-analytics-choice="accepted"]')
        d.pump(7)
        if not d.cookies(first) or not d.events(d.phase):
            raise RuntimeError('Real Analytics did not initialize')
        d.phase=f'second-tab-{index}'
        second=d.tab()
        # No go() fixed one-second wait: delay is measured from observed view_service queue.
        d.call('Page.bringToFront',session=second)
        d.call('Page.navigate',{'url':ORIGIN+'/rendez-vous/types/1'},second)
        deadline=time.monotonic()+20
        while time.monotonic()<deadline:
            queued=[o for o in d.observations if o['kind']=='queue' and o['phase']==d.phase
                and o['command'][:2]==['event','view_service']]
            if queued: break
            d.pump(.05)
        if not queued: raise RuntimeError('Second tab did not queue its events')
        scheduled=queued[-1]['time']+delay
        while time.time()<scheduled: d.pump(min(.1,max(.001,scheduled-time.time())))
        d.call('Page.bringToFront',session=first)
        d.phase=f'withdraw-{index}'
        # Do not add click()'s 600ms pause between opening the panel and writing refusal.
        d.evaluate(first,'document.querySelector("[data-analytics-manage]").click(); document.querySelector("[data-analytics-choice=refused]").click()')
        marks=[o['time'] for o in d.observations if o['kind']=='consent-written'
            and o.get('choice')=='refused' and o['phase']==d.phase]
        if not marks: raise RuntimeError('Completed consent write not observed')
        cutoff=marks[-1]
        d.pump(6)
        after=[r for r in d.collect() if r['time']>=cutoff]
        native=[o for o in d.observations if o['kind']=='native-transport'
            and '/g/collect' in o.get('url','') and o['time']>=cutoff]
        blocked=[o for o in d.observations if o['kind']=='guarded-transport-attempt'
            and '/g/collect' in o.get('url','') and o['time']>=cutoff and not o['delegated']]
        trial={'delay_requested_seconds':delay,'actual_queue_to_refusal_ms':(cutoff-queued[-1]['time'])*1000,
            'cutoff':cutoff,'end':time.time(),'native_after':len(native),'blocked_calls_after':len(blocked),
            'network_after':len(after),'late_network_observations':[
                {'delay_ms':(r['time']-cutoff)*1000,'time_source':r['time_source'],'url':r['url']} for r in after]}
        results['withdrawal_trials'].append(trial)
        checks=[
            {'name':f'Trial {index}: both frames destroyed','passed':not d.evaluate(first,'!!document.querySelector("iframe")') and not d.evaluate(second,'!!document.querySelector("iframe")')},
            {'name':f'Trial {index}: cookies removed','passed':not d.cookies(first) and not d.cookies(second)},
            {'name':f'Trial {index}: no native transport call after completed refusal','passed':not native},
            {'name':f'Trial {index}: original no-network-after-refusal criterion','passed':not after},
        ]
        results['checks'].extend(checks)
        for check in checks: print(('PASS ' if check['passed'] else 'FAIL ')+check['name'],flush=True)
        d.call('Page.navigate',{'url':'about:blank'},second)
    results['production_validated']=False


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--authorize-real-ga4', required=True, choices=[MEASUREMENT_ID])
    scope=parser.add_mutually_exclusive_group()
    scope.add_argument('--native-click-only', action='store_true', help='Focused native-navigation diagnostic with network-service logging')
    scope.add_argument('--withdrawal-only', action='store_true', help='Focused multi-tab withdrawal after actual storage write')
    parser.add_argument('--withdrawal-delays', default='3', help='Seconds from queued second-tab view_service; withdrawal-only')
    args = parser.parse_args()
    delays=tuple(float(value) for value in args.withdrawal_delays.split(','))
    if not delays or any(not 0 <= value <= 10 for value in delays): parser.error('Withdrawal delays must be between 0 and 10 seconds')
    guard_checks()
    for port in (8021,9233):
        with socket.socket() as probe:
            probe.bind(('127.0.0.1',port))
    output = ROOT / 'var' / 'analytics-real' / time.strftime('%Y%m%d-%H%M%S')
    output.mkdir(parents=True,exist_ok=False)
    environment = dict(os.environ, GA4_ENABLED='1',GA4_MEASUREMENT_ID=MEASUREMENT_ID,GA4_REAL_RECIPE=MEASUREMENT_ID)
    results = {'measurement_id':MEASUREMENT_ID, 'origin':ORIGIN,
        'suite':'native-click-only' if args.native_click_only else 'withdrawal-only' if args.withdrawal_only else 'full',
        'analytics_ui_reception':'Not checked; manual confirmation required.', 'enhanced_measurement':'Disabled, screenshot verified by user.'}
    php = chrome = driver = None
    flags = subprocess.CREATE_NO_WINDOW if os.name=='nt' else 0
    log = (output/'process.log').open('w',encoding='utf-8')
    try:
        php = subprocess.Popen(['php','-d','xdebug.mode=off','-S','127.0.0.1:8021','-t','public','tests/Analytics/real_preview.php'],
            cwd=ROOT,env=environment,stdout=log,stderr=log,creationflags=flags)
        chrome = subprocess.Popen(['C:/Program Files/Google/Chrome/Application/chrome.exe','--headless=new','--disable-gpu',
            '--no-first-run','--disable-background-networking','--disable-component-update','--disable-sync',
            '--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE localhost, EXCLUDE 127.0.0.1, EXCLUDE www.googletagmanager.com, EXCLUDE region1.google-analytics.com, EXCLUDE www.google-analytics.com',
            '--log-net-log='+str(output/'netlog-private.json'), '--net-log-capture-mode=Default',
            '--remote-debugging-port=9233','--user-data-dir='+str(output/'chrome'),'about:blank'],stdout=log,stderr=log,creationflags=flags)
        for attempt in range(60):
            try:
                with urllib.request.urlopen('http://127.0.0.1:9233/json/version',timeout=1) as response:
                    version = json.load(response)
                break
            except OSError:
                time.sleep(.25)
        else:
            raise RuntimeError('Isolated Chrome unavailable')
        driver = Driver(version['webSocketDebuggerUrl'])
        if args.withdrawal_only:
            withdrawal_recipe(driver,results,delays)
        else:
            (native_click_recipe if args.native_click_only else run_recipe)(driver,results)
    except Exception as error:
        results['fatal_error'] = type(error).__name__+': '+str(error)
        print(results['fatal_error'],flush=True)
    finally:
        if driver:
            results['network'] = list(driver.requests.values())
            results['observations'] = driver.observations
            results['queue'] = [o for o in driver.observations if o['kind']=='queue']
            results['javascript_errors'] = driver.errors
            try:
                driver.send('Browser.close')
            except OSError:
                pass
            driver.sock.close()
        for process in (chrome,php):
            if process:
                try:
                    process.wait(timeout=3)
                except subprocess.TimeoutExpired:
                    process.terminate()
                    process.wait(timeout=5)
        log.close()
        results['processes_stopped'] = all(p is None or p.poll() is not None for p in (php,chrome))
        if (output/'netlog-private.json').exists():
            try:
                attach_netlog(results,output/'netlog-private.json')
            except (OSError,ValueError,KeyError) as error:
                results['netlog_error'] = type(error).__name__+': '+str(error)
        for index, trial in enumerate(results.get('withdrawal_trials',[])):
            late=[r for r in results.get('netlog_requests',[]) if trial['cutoff'] <= r['start_time'] <= trial['end']]
            trial['netlog_after']=[{'delay_ms':(r['start_time']-trial['cutoff'])*1000,'status':r['status']} for r in late]
            results['checks'].append({'name':f'Trial {index}: NetLog no request started after refusal','passed':not late})
        audit_report(results)
        (output/'report.json').write_text(json.dumps(sanitize(results),ensure_ascii=False,indent=2),encoding='utf-8')
        print('Report: '+str(output/'report.json'),flush=True)
        print('Validated: '+str(results['validated'])+'; processes stopped: '+str(results['processes_stopped']),flush=True)
    return 0 if results['validated'] else 1


if __name__ == '__main__':
    raise SystemExit(main())
