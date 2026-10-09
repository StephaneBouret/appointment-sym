"""Read-only calendar check in the dedicated Edge profile; no login or booking."""
import json
from pathlib import Path
import sys
import urllib.request
sys.dont_write_bytecode = True
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'PaymentBrowser'))
from browser import Browser, STATE

mode = sys.argv[1]
origin = 'http://127.0.0.1:8097' if mode == 'isolated' else 'https://127.0.0.1:8000'
if mode == 'inspect':
    b = Browser(origin=origin)
    print(json.dumps(b.evaluate('''({ready:document.readyState,fc:!!window.FullCalendar,errors:window.slotErrors,requests:window.slotRequests,calendar:!!document.querySelector('#calendar'),next:!!document.querySelector('.fc-next-button'),loader:document.querySelector('.fc-loading-indicator')?.style.display,stimulus:!!window.Stimulus})'''), indent=2))
    sys.exit(0)
targets = json.load(urllib.request.urlopen('http://127.0.0.1:9227/json'))
if not any(t['type'] == 'page' and t['url'].startswith(origin) for t in targets):
    urllib.request.urlopen(urllib.request.Request('http://127.0.0.1:9227/json/new?' + origin + '/rendez-vous/types', method='PUT')).close()
browser = Browser(origin=origin)
browser.call('Security.setIgnoreCertificateErrors', {'ignore': True})
instrumentation = '''
window.slotRequests=[]; window.slotErrors=[]; window.slotConsoleErrors=[];
const nativeConsoleError=console.error;
console.error=(...args)=>{ if(args[0]==='[calendar] events load error') slotConsoleErrors.push(String(args[1]?.message || args[1])); nativeConsoleError(...args); };
addEventListener('error',e=>slotErrors.push(String(e.message)));
addEventListener('unhandledrejection',e=>slotErrors.push(String(e.reason)));
const nativeFetch=window.fetch;
window.fetch=async (...args)=>{
 const u=new URL(String(args[0]),location.origin);
 if(u.pathname!='/api/fixed-slots-range') return nativeFetch(...args);
 let r;
 if(window.slotMock==='network') throw new TypeError('Failed to fetch');
 if(window.slotMock==='500') r=new Response('server detail must not be displayed',{status:500});
 else if(window.slotMock==='invalid') r=new Response('not JSON',{status:200});
 else if(window.slotMock==='object') r=new Response(JSON.stringify({error:'invalid shape'}));
 else if(window.slotMock==='empty') r=new Response('[]');
 else if(window.slotMock==='success') { const day=u.searchParams.get('start'); r=new Response(JSON.stringify([{start:day+'T14:00:00+02:00',end:day+'T15:15:00+02:00'}])); }
 else r=await nativeFetch(...args);
 const body=await r.clone().text();
 let count=null; try {const a=JSON.parse(body); if(Array.isArray(a)) count=a.length;} catch{}
 slotRequests.push({url:u.pathname+u.search,status:r.status,count}); return r;
};
'''
browser.evaluate('window.slotOldPage=true')
browser.call('Page.navigate', {'url': origin + '/rendez-vous/types'})
browser.wait('!window.slotOldPage && document.readyState === "complete" && !!window.FullCalendar')
browser.evaluate(instrumentation)
if mode == 'isolated':
    browser.evaluate("window.slotMock='empty'")
browser.evaluate('''(() => { const box=document.createElement('section'); box.innerHTML=`
<input id="appointment_startAt" type="hidden">
<div id="calendar" data-controller="calendar" data-calendar-endpoint-value="/api/fixed-slots-range" data-calendar-type-id-value="3" data-calendar-initial-view-value="timeGridWeek" data-calendar-slot-min-time-value="08:00:00" data-calendar-slot-max-time-value="20:00:00" data-calendar-open-delay-hours-value="48" data-calendar-time-zone-value="Europe/Paris" data-calendar-open-days-value="1,2,3,4,5"></div>`;
document.body.append(box); return true; })()''')
browser.wait('!!document.querySelector(".fc-next-button") && slotRequests.length>0 && !!document.querySelector(".fc-slot-list-body")?.innerHTML')

def snapshot():
    return browser.evaluate('''({requests:slotRequests,errors:slotErrors,consoleErrors:slotConsoleErrors,title:document.querySelector('.fc-toolbar-title')?.innerText,
    notice:document.querySelector('.fc-empty-notice:not(.d-none)')?.innerText || null,
    error:document.querySelector('.fc-load-error:not(.d-none)')?.innerText || null,
    list:document.querySelector('.fc-slot-list-body')?.innerText,
    slots:document.querySelectorAll('.fc-slot-btn').length})''')

initial = snapshot()
count = len(initial['requests'])
browser.evaluate('document.querySelector(".fc-next-button").click(); true')
browser.wait('slotRequests.length>' + str(count))
next_week = snapshot()
result = {'mode': mode, 'origin': origin, 'initial': initial, 'next_week': next_week}
if mode == 'migrated':
    assert initial['requests'][-1]['status'] == 200 and initial['slots'] == 0
    assert next_week['requests'][-1]['status'] == 200 and next_week['requests'][-1]['count'] == next_week['slots'] == 20
    assert not next_week['error'] and not next_week['consoleErrors'] and not next_week['errors']
    periods = browser.evaluate('''[...document.querySelectorAll('.fc-slot-btn')].map(b=>({start:b.dataset.startTz,end:b.dataset.endTz}))''')
    from datetime import datetime
    assert sorted(set(p['start'][:10] for p in periods)) == ['2026-10-12','2026-10-13','2026-10-14','2026-10-15','2026-10-16']
    for period in periods:
        assert (datetime.fromisoformat(period['end'])-datetime.fromisoformat(period['start'])).total_seconds() == 75 * 60
    result['verified_periods'] = periods
    navigation = []
    for direction, expected_count in [('next', 20), ('prev', 20), ('prev', 0), ('next', 20)]:
        browser.evaluate('window.slotRequests=[]; document.querySelector(".fc-' + direction + '-button").click(); true')
        browser.wait('slotRequests.length>0')
        observed = snapshot()
        assert observed['requests'][-1]['status'] == 200 and observed['slots'] == expected_count
        assert not observed['error'] and not observed['consoleErrors'] and not observed['errors']
        navigation.append({'direction': direction, 'title': observed['title'], 'http': observed['requests'][-1]['status'], 'displayed_slots': observed['slots']})
    result['navigation'] = navigation
if mode == 'after':
    assert initial['requests'][-1]['status'] == 200 and initial['requests'][-1]['count'] == 0
    assert next_week['requests'][-1]['status'] == 500 and next_week['error'] and not next_week['notice']
    browser.evaluate('window.slotRequests=[]; document.querySelector(".fc-prev-button").click(); true')
    browser.wait('slotRequests.length>0 && !!document.querySelector(".fc-empty-notice:not(.d-none)")')
    assert not snapshot()['error']
    browser.evaluate('window.slotRequests=[]; document.querySelector(".fc-next-button").click(); true')
    browser.wait('slotRequests.length>0 && !!document.querySelector(".fc-load-error:not(.d-none)")')
    browser.evaluate('window.slotRequests=[]; document.querySelector(".fc-load-error button").click(); true')
    browser.wait('slotRequests.length>0 && !!document.querySelector(".fc-load-error:not(.d-none)")')
    result['back_next_retry'] = snapshot()
if mode == 'isolated':
    cases = []
    for mock in ['500', 'invalid', 'object', 'network', 'empty', 'success']:
        browser.evaluate('window.slotMock=' + json.dumps(mock) + '; window.slotRequests=[]; document.querySelector(".fc-prev-button").click(); true')
        browser.wait('slotRequests.length>0 || !!document.querySelector(".fc-load-error:not(.d-none)")')
        # Navigate to the next week as well: verifies recovery after a failed load.
        browser.evaluate('window.slotRequests=[]; document.querySelector(".fc-next-button").click(); true')
        browser.wait('slotRequests.length>0 || !!document.querySelector(".fc-load-error:not(.d-none)")')
        observed = snapshot()
        if mock in ['500', 'invalid', 'object', 'network']:
            assert observed['error'] and not observed['notice'] and observed['slots'] == 0
            assert 'Aucun créneau' not in observed['list']
            assert 'server detail' not in observed['error']
        elif mock == 'empty':
            assert not observed['error'] and 'Aucun créneau disponible sur cette période' in observed['notice']
        else:
            assert not observed['error'] and not observed['notice'] and observed['slots'] == 1
        cases.append({'case': mock, 'observed': observed})
    result['cases'] = cases
    browser.evaluate('document.querySelector(".fc-slot-btn").click(); window.slotMock="500"; window.slotRequests=[]; document.querySelector(".fc-next-button").click(); true')
    browser.wait('!!document.querySelector(".fc-load-error:not(.d-none)")')
    assert browser.evaluate('document.getElementById("appointment_startAt").value') == ''
    browser.evaluate('window.slotMock="success"; window.slotRequests=[]; document.querySelector(".fc-load-error button").click(); true')
    browser.wait('slotRequests.length>0 && !document.querySelector(".fc-load-error:not(.d-none)")')
    assert snapshot()['slots'] == 1
    result['retry_after_failure'] = snapshot()
print(json.dumps(result, ensure_ascii=False, indent=2))
(STATE / ('slots-' + mode + '.json')).write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
