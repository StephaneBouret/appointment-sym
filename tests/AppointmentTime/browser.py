"""Read-only HTTP + real Stimulus/FullCalendar checks; no form submission."""
import json
import sys
from datetime import date, timedelta
from pathlib import Path
sys.dont_write_bytecode = True
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'PaymentBrowser'))
from browser import Browser

b = Browser(origin='https://127.0.0.1:8000')
b.call('Security.setIgnoreCertificateErrors', {'ignore': True})
b.evaluate('window.timeOldPage=true')
b.call('Page.navigate', {'url': 'https://127.0.0.1:8000/rendez-vous/types'})
b.wait('!window.timeOldPage && document.readyState==="complete" && !!window.FullCalendar')
b.evaluate('''(() => {
window.timeErrors=[]; addEventListener('error',e=>timeErrors.push(e.message));
const Original=FullCalendar.Calendar;
FullCalendar.Calendar=class extends Original {constructor(...args){super(...args);window.timeCalendar=this;}};
const box=document.createElement('form'); box.id='time-test-form';box.onsubmit=e=>e.preventDefault();
box.innerHTML=`<input id="appointment_startAt" name="appointment[startAt]" type="hidden">
<div id="calendar" data-controller="calendar" data-calendar-endpoint-value="/api/fixed-slots-range" data-calendar-type-id-value="3" data-calendar-initial-view-value="timeGridWeek" data-calendar-slot-min-time-value="08:00:00" data-calendar-slot-max-time-value="20:00:00" data-calendar-open-delay-hours-value="48" data-calendar-time-zone-value="Europe/Paris" data-calendar-open-days-value="1,2,3,4,5"></div>`;
document.body.append(box);return true;
})()''')
b.wait('!!window.timeCalendar && !!document.querySelector(".fc-next-button")')
results=[]
try:
    for zone in ['Europe/Paris', 'UTC']:
        b.call('Emulation.setTimezoneOverride', {'timezoneId': zone})
        for day in ['2026-10-12','2027-01-12']:
            b.evaluate('timeCalendar.gotoDate('+json.dumps(day)+');true')
            b.wait('!!document.querySelector(\'.fc-slot-btn[data-start-tz^="'+day+'T09:00"]\')')
            end=(date.fromisoformat(day)+timedelta(days=1)).isoformat()
            api=b.evaluate('fetch("/api/fixed-slots-range?type=3&start='+day+'&end='+end+'").then(async r=>({status:r.status,body:await r.json()}))')
            # Read the same RFC3339 values used by the real controller's list.
            entry=b.evaluate('''(() => {const btn=document.querySelector('.fc-slot-btn[data-start-tz^="%sT09:00"]');btn.click();return {start:btn.dataset.startTz,end:btn.dataset.endTz,input:document.querySelector('#appointment_startAt').value,submitted:new FormData(document.querySelector('#time-test-form')).get('appointment[startAt]'),notice:document.querySelector('.fc-selected-text').textContent};})()''' % day)
            grid=b.evaluate('''(() => {const ev=timeCalendar.getEvents().find(e=>e.startStr.startsWith('%sT09:00'));timeCalendar.getOption('eventClick')({event:ev});return {startStr:ev.startStr,startDate:ev.start.toISOString(),input:document.querySelector('#appointment_startAt').value,notice:document.querySelector('.fc-selected-text').textContent,errors:timeErrors};})()''' % day)
            results.append({'browser_timezone':zone,'day':day,'api':api,'list':entry,'grid':grid})
            assert api['status']==200 and any(s['start']==entry['start'] for s in api['body'])
            assert entry['input']==entry['submitted']==grid['input']==day+'T09:00'
            assert '09:00 – 10:15' in entry['notice'] and '09:00 – 10:15' in grid['notice']
            assert not grid['errors']
finally:
    b.call('Emulation.setTimezoneOverride', {'timezoneId':''})
print(json.dumps(results,ensure_ascii=False,indent=2))
