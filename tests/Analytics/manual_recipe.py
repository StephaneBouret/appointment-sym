"""Visible, persistent manual GA4 reception diagnostic. Explicit start/stop; no automatic consent."""
import argparse
from collections import Counter
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import socket
import subprocess
import sys
import time
import urllib.request
from uuid import uuid4

sys.dont_write_bytecode = True
from real_driver import Driver, OBSERVER, MEASUREMENT_ID, ORIGIN, parameters
from real_recipe import ROOT, attach_netlog, guard_checks, sanitize

BASE = ROOT / 'var' / 'analytics-manual'
ACTIVE = BASE / 'active.json'
CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe'
DNS_RULES = 'MAP * ~NOTFOUND, EXCLUDE localhost, EXCLUDE 127.0.0.1, EXCLUDE www.googletagmanager.com, EXCLUDE region1.google-analytics.com, EXCLUDE www.google-analytics.com'


def debug_decorator():
    # Reuse the real consent validator, rather than a looser test-only interpretation.
    policy = (ROOT/'assets/modules/analytics_policy.js').read_text(encoding='utf-8')
    consent_policy = policy.split('export function configured(',1)[0].replace('export ','')
    return '''item => {
      try {
        %s
        if (location.origin !== %s || location.pathname !== '/audience/frame'
            || readConsent()?.choice !== 'accepted') return;
        const config=JSON.parse(document.body.dataset.analyticsConfig);
        if (config.enabled !== true || config.id !== %s) return;
        if ((item[0]==='config' && item[1]===config.id)
            || (item[0]==='event' && item[2]?.send_to===config.id)) {
          item[2]={...item[2],debug_mode:true};
        }
      } catch { /* No consent/configuration available: never inject debug. */ }
    }''' % (consent_policy,json.dumps(ORIGIN),json.dumps(MEASUREMENT_ID))


def observer(debug):
    return OBSERVER.replace('const decorateRecipeCommand = item => {};',
        'const decorateRecipeCommand = '+debug_decorator()+';') if debug else OBSERVER


class ManualDriver(Driver):
    def __init__(self, url, debug):
        self.pages = {}
        super().__init__(url, observer(debug))
        self.browser_version=self.call('Browser.getVersion')['product']

    def event(self, message):
        p = message.get('params',{})
        if message['method']=='Target.attachedToTarget' and p['targetInfo']['type']=='page':
            session=p['sessionId']
            if session not in self.pages.values():
                self.pages[p['targetInfo']['targetId']]=session
                self.sessions.append(session)
                self.protect(session)
                if p.get('waitingForDebugger'):
                    self.call('Runtime.runIfWaitingForDebugger',session=session)
            return
        if message['method']=='Target.detachedFromTarget':
            self.pages={target:session for target,session in self.pages.items() if session!=p.get('sessionId')}
        super().event(message)

    def open(self):
        # Browser-level auto-attach also protects tabs manually opened later, before they run.
        self.call('Target.setAutoAttach',{'autoAttach':True,'waitForDebuggerOnStart':True,'flatten':True})
        if not self.pages:
            self.call('Target.createTarget',{'url':'about:blank'})
        session=next(iter(self.pages.values()))
        self.go(session,'/')
        return session


def write_json(path, data):
    temporary=path.with_suffix('.tmp')
    temporary.write_text(json.dumps(data,ensure_ascii=False,indent=2),encoding='utf-8')
    for attempt in range(10):
        try:
            temporary.replace(path)
            return
        except PermissionError:
            if attempt==9:
                raise
            time.sleep(.05) # A concurrent Windows status/read can briefly hold the destination open.


def active():
    state=json.loads(ACTIVE.read_text(encoding='utf-8'))
    output=Path(state['output']).resolve()
    if output.parent!=BASE.resolve() or not output.is_dir():
        raise RuntimeError('Invalid recipe directory in active state')
    return state,output


def snapshot(driver, debug, status):
    network=list(driver.requests.values())
    events=[(r,p) for r in driver.collect() for p in parameters(r['url'],r['body']) if 'en' in p]
    return {'suite':'manual-visible','measurement_id':MEASUREMENT_ID,'origin':ORIGIN,'debug_mode_requested':debug,
        'browser':driver.browser_version,'headless':False,
        'updated_utc':datetime.now(timezone.utc).isoformat(),'status':status,'validated':False,
        'global_acceptance':'Not validated; multi-tab withdrawal anomaly remains open.',
        'analytics_ui_reception':'To be confirmed by the user in Realtime and DebugView.',
        'queued_events':dict(Counter(o['command'][1] for o in driver.observations if o['kind']=='queue' and o['command'][0]=='event')),
        'attempted_events':dict(Counter(p['en'][0] for r,p in events)),
        'http_acknowledged_events':dict(Counter(p['en'][0] for r,p in events if r.get('status') in (200,204))),
        'debug_commands':sum(o['kind']=='queue' and o['command'][0] in ('config','event')
            and len(o['command'])>2 and isinstance(o['command'][2],dict) and o['command'][2].get('debug_mode') is True
            for o in driver.observations),
        'debug_transport_markers':[{'event':p['en'][0],'markers':{k:v for k,v in p.items() if k in ('_dbg','ep.debug_mode','epn.debug_mode')}}
            for r,p in events],
        'network':network,'observations':driver.observations,'javascript_errors':driver.errors}


def self_check(driver, session):
    # Pure-JS consent/debug tests inside the browser, shadowing storage and DOM. No gtag load/event.
    source='''(() => {
      let choice;
      const window={localStorage:{getItem:()=>JSON.stringify(choice)}};
      const location={origin:%s,pathname:'/audience/frame'};
      const config={enabled:true,id:%s};
      const document={body:{dataset:{get analyticsConfig(){return JSON.stringify(config)}}}};
      const decorate=%s;
      const date=Date.now()-1000;
      const expiry=new Date(date); const day=expiry.getUTCDate(); expiry.setUTCDate(1);
      expiry.setUTCMonth(expiry.getUTCMonth()+6);
      expiry.setUTCDate(Math.min(day,new Date(Date.UTC(expiry.getUTCFullYear(),expiry.getUTCMonth()+1,0)).getUTCDate()));
      const accepted={version:1,choice:'accepted',date,expires:expiry.getTime()};
      const result=[];
      function test(label,expected,kind='config',id=config.id) {
        const item=kind==='config' ? ['config',id,{send_page_view:false}] : ['event','page_view',{send_to:id}];
        decorate(item); result.push({name:label,passed:(item[2].debug_mode===true)===expected});
      }
      test('absent consent',false);
      choice={...accepted,choice:'refused'}; test('refusal',false);
      choice={...accepted,version:99}; test('invalid consent',false);
      choice={...accepted,date:1,expires:Date.UTC(1970,6,1)+1}; test('expired consent',false);
      choice=accepted; test('accepted config',true); test('accepted event',true,'event');
      test('wrong measurement id',false,'config','G-MWZX1GYXZE');
      config.enabled=false; test('disabled integration',false); config.enabled=true;
      location.pathname='/login'; test('private page',false);
      return result;
    })()''' % (json.dumps(ORIGIN),json.dumps(MEASUREMENT_ID),debug_decorator())
    checks=driver.evaluate(session,source)
    checks.append({'name':'No Analytics before consent in actual visible page',
        'passed':not driver.cookies(session) and not driver.collect() and not any(r['kind']=='library' for r in driver.requests.values())})
    driver.call('Target.createTarget',{'url':ORIGIN+'/login'})
    driver.pump(1)
    checks.append({'name':'Manually opened page is auto-attached and protected','passed':len(driver.pages)==2})
    assert all(c['passed'] for c in checks), checks
    return checks


def worker(output, debug, check_only):
    flags=subprocess.CREATE_NO_WINDOW if os.name=='nt' else 0
    php=chrome=driver=None
    report={'validated':False}
    log=(output/'process.log').open('w',encoding='utf-8')
    state={'status':'starting','output':str(output),'pid':os.getpid(),'debug_mode':debug,'origin':ORIGIN,
        'started_utc':datetime.now(timezone.utc).isoformat()}
    try:
        guard_checks()
        for port in (8021,9233):
            with socket.socket() as probe:
                probe.bind(('127.0.0.1',port))
        environment=dict(os.environ,GA4_ENABLED='1',GA4_MEASUREMENT_ID=MEASUREMENT_ID,GA4_REAL_RECIPE=MEASUREMENT_ID)
        php=subprocess.Popen(['php','-d','xdebug.mode=off','-S','127.0.0.1:8021','-t','public','tests/Analytics/real_preview.php'],
            cwd=ROOT,env=environment,stdout=log,stderr=log,creationflags=flags)
        chrome=subprocess.Popen([CHROME,'--no-first-run','--disable-background-networking','--disable-component-update','--disable-sync',
            '--host-resolver-rules='+DNS_RULES,'--log-net-log='+str(output/'netlog-private.json'),
            '--net-log-capture-mode=Default','--remote-debugging-port=9233','--user-data-dir='+str(output/'chrome'),
            '--new-window','about:blank'],stdout=log,stderr=log) # Visible browser explicitly requested.
        for attempt in range(100):
            try:
                with urllib.request.urlopen('http://127.0.0.1:9233/json/version',timeout=1) as response:
                    version=json.load(response)
                break
            except OSError:
                if chrome.poll() is not None or php.poll() is not None:
                    raise RuntimeError('Recipe process exited during startup')
                time.sleep(.2)
        else:
            raise RuntimeError('Isolated Chrome unavailable')
        driver=ManualDriver(version['webSocketDebuggerUrl'],debug)
        driver.phase='manual-debug' if debug else 'manual-no-debug'
        if check_only:
            driver.tag_mode='fail' # Self-check can never load Google, even if clicked accidentally.
        session=driver.open()
        if check_only:
            report=snapshot(driver,debug,'self-check')
            report['checks']=self_check(driver,session)
        else:
            state['status']='running'
            write_json(ACTIVE,state)
            while not (output/'STOP').exists() and chrome.poll() is None and php.poll() is None:
                driver.pump(.5) # Keep interception serviced for the entire manual verification.
                report=snapshot(driver,debug,'running')
                write_json(output/'report-live.json',sanitize(report))
    except (Exception,KeyboardInterrupt) as error:
        report=snapshot(driver,debug,'error') if driver else report
        if chrome and chrome.poll() is not None and driver:
            report['closure_reason']='Chrome exited; the isolated server is stopped too.'
        else:
            report['error']=type(error).__name__+': '+str(error)
    finally:
        if driver:
            if not check_only:
                report={**snapshot(driver,debug,'stopped'),**({'error':report['error']} if 'error' in report else {})}
            try:
                driver.send('Browser.close')
            except OSError:
                pass
            driver.sock.close()
        for process in (chrome,php):
            if process:
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.terminate()
                    process.wait(timeout=5)
        log.close()
        report['processes_stopped']=all(p is None or p.poll() is not None for p in (chrome,php))
        if (output/'netlog-private.json').exists():
            try:
                attach_netlog(report,output/'netlog-private.json')
                report['http_acknowledged_events']=dict(Counter(p['en'][0] for r in report.get('network',[])
                    if r.get('kind')=='collect' and r.get('decision')=='continue'
                    and (r.get('status') in (200,204) or r.get('netlog_status') in (200,204))
                    for p in parameters(r['url'],r['body']) if 'en' in p))
            except (OSError,ValueError,KeyError) as error:
                report['netlog_error']=str(error)
        report['validated']=False # This diagnostic never validates global acceptance or GA4 ingestion.
        write_json(output/'report.json',sanitize(report))
        state.update(status='error' if 'error' in report else 'stopped',error=report.get('error'),processes_stopped=report['processes_stopped'])
        write_json(ACTIVE,state)
    return 1 if 'error' in report else 0


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    commands=parser.add_subparsers(dest='action',required=True)
    start=commands.add_parser('start')
    start.add_argument('--authorize-real-ga4',required=True,choices=[MEASUREMENT_ID])
    start.add_argument('--debug-mode',action='store_true')
    start.add_argument('--self-check',action='store_true',help='Visible offline check, no Google loading, then automatic shutdown')
    commands.add_parser('stop')
    commands.add_parser('status')
    child=commands.add_parser('_worker')
    child.add_argument('--authorize-real-ga4',required=True,choices=[MEASUREMENT_ID])
    child.add_argument('--debug-mode',action='store_true')
    child.add_argument('--self-check',action='store_true')
    child.add_argument('--output',required=True)
    args=parser.parse_args()
    if args.action=='_worker':
        state,output=active()
        if output!=Path(args.output).resolve() or state['status']!='starting':
            parser.error('Worker must be launched by start')
        return worker(output,args.debug_mode,args.self_check)
    if args.action in ('status','stop'):
        if not ACTIVE.exists():
            print('No manual recipe has been started.'); return 0
        state,output=active()
        if args.action=='stop' and state['status'] in ('starting','running'):
            (output/'STOP').touch()
            for attempt in range(150):
                time.sleep(.2)
                state,_=active()
                if state['status'] in ('stopped','error'):
                    break
            else:
                raise RuntimeError('Stop not acknowledged; inspect '+str(output/'worker.log'))
        if args.action=='status' and (output/'report-live.json').exists():
            live=json.loads((output/'report-live.json').read_text(encoding='utf-8'))
            state['live']={k:live[k] for k in ('updated_utc','queued_events','attempted_events','http_acknowledged_events','debug_commands')}
        print(json.dumps(state,ensure_ascii=False,indent=2)); return 0
    BASE.mkdir(parents=True,exist_ok=True)
    if ACTIVE.exists():
        state,_=active()
        if state['status'] in ('starting','running'):
            parser.error('A manual recipe is already active; use status or stop')
    output=BASE/(datetime.now(timezone.utc).strftime('%Y%m%d-%H%M%SZ')+'-'+uuid4().hex[:8])
    output.mkdir()
    write_json(ACTIVE,{'status':'starting','output':str(output),'debug_mode':args.debug_mode})
    command=[sys.executable,str(Path(__file__).resolve()),'_worker','--authorize-real-ga4',MEASUREMENT_ID,'--output',str(output)]
    if args.debug_mode: command.append('--debug-mode')
    if args.self_check: command.append('--self-check')
    with (output/'worker.log').open('w',encoding='utf-8') as log:
        child_process=subprocess.Popen(command,cwd=ROOT,stdin=subprocess.DEVNULL,stdout=log,stderr=log,
            creationflags=subprocess.CREATE_NO_WINDOW if os.name=='nt' else 0)
    for attempt in range(300):
        time.sleep(.2)
        state,_=active()
        if state['status']!='starting':
            print(json.dumps(state,ensure_ascii=False,indent=2))
            return 1 if state['status']=='error' else 0
        if child_process.poll() is not None:
            raise RuntimeError('Worker exited; inspect '+str(output/'worker.log'))
    raise RuntimeError('Startup timeout; use stop. Logs: '+str(output/'worker.log'))


if __name__=='__main__':
    raise SystemExit(main())
