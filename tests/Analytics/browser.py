"""Real Chromium + real Turbo/Stimulus; synthetic PHP fixture, Google replaced by a mock.

php -d xdebug.mode=off -S 127.0.0.1:8019 -t public tests/Analytics/preview.php
Launch headless Chrome on CDP port 9231 with an isolated profile, then run this file.
Only same-origin GET/HEAD may reach the local harness. No external request is continued.
"""
import base64
import json
from pathlib import Path
import struct
import sys
import time
from urllib.parse import urlsplit

sys.dont_write_bytecode = True
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'Prestations'))
import browser as driver

driver.ORIGIN = ORIGIN = 'http://127.0.0.1:8019'
ARTIFACTS = Path(__file__).resolve().parents[2] / 'var/analytics-tests'
ARTIFACTS.mkdir(parents=True, exist_ok=True)
MOCK = r"""
(() => {
  let configuration;
  const process = args => {
    const command = Array.from(args);
    if (window['ga-disable-G-TEST123456']) return;
    fetch('/__analytics_test/collect', {method:'POST', body:JSON.stringify({command, document:location.href, referrer:document.referrer})});
    if (command[0] === 'config') {
      configuration = command[2];
      for (const name of [`${configuration.cookie_prefix}_ga`, `${configuration.cookie_prefix}_ga_TEST123456`]) {
        // Keep existing values; a deletion/recreation must yield a different value.
        if (!document.cookie.split(';').some(cookie => cookie.trim().startsWith(`${name}=`))) {
          document.cookie = `${name}=mock-${crypto.randomUUID()}; path=/; SameSite=Lax`;
        }
      }
    }
  };
  window.dataLayer.forEach(process);
  window.dataLayer.push = function(args) { process(args); return Array.prototype.push.call(this, args); };
})();
"""


class Browser(driver.Browser):
    def __init__(self):
        super().__init__(allowed_origin=ORIGIN)
        self.commands = []
        self.tag_requests = 0
        self.held = []
        self.tag_mode = 'mock'
        self.hold_detail = False
        self.details = []
        self.errors = []
        self.mock_maps = True

    def fulfill(self, request_id, body, content_type='application/javascript'):
        self.send('Fetch.fulfillRequest', {'requestId': request_id, 'responseCode': 200,
            'responseHeaders': [{'name':'Content-Type','value':content_type}, {'name':'Access-Control-Allow-Origin','value':'*'}],
            'body':base64.b64encode(body.encode()).decode()})

    def request(self, request):
        req, request_id = request['request'], request['requestId']
        url = req['url']
        if url.startswith('https://www.googletagmanager.com/gtag/js?'):
            assert url == 'https://www.googletagmanager.com/gtag/js?id=G-TEST123456', url
            self.tag_requests += 1
            if self.tag_mode == 'hold':
                self.held.append(request_id)
            elif self.tag_mode == 'fail':
                self.send('Fetch.failRequest', {'requestId':request_id, 'errorReason':'BlockedByClient'})
            else:
                self.fulfill(request_id, MOCK)
        elif url == ORIGIN + '/__analytics_test/collect':
            self.commands.append(json.loads(req['postData']))
            self.fulfill(request_id, '{}', 'application/json')
        elif 'unpkg.com/@googlemaps/' in url and self.mock_maps:
            self.fulfill(request_id, 'export const APILoader = { importLibrary() { throw new Error("Blocked in tests"); } };')
        elif req['method'] not in ('GET', 'HEAD') or not url.startswith(ORIGIN + '/'):
            self.blocked_external += 1
            self.send('Fetch.failRequest', {'requestId':request_id, 'errorReason':'BlockedByClient'})
        elif self.hold_detail and '/rendez-vous/types/' in url and req.get('headers', {}).get('X-Requested-With') == 'XMLHttpRequest':
            self.details.append(request_id)
        else:
            self.send('Fetch.continueRequest', {'requestId':request_id})

    def call(self, method, params=None):
        seq = self.send(method, params or {})
        while True:
            first, second = self.read(2)
            length = second & 127
            if length == 126:
                length = struct.unpack('!H', self.read(2))[0]
            elif length == 127:
                length = struct.unpack('!Q', self.read(8))[0]
            message = json.loads(self.read(length))
            if message.get('method') == 'Fetch.requestPaused':
                self.request(message['params'])
            elif message.get('method') == 'Runtime.exceptionThrown':
                self.errors.append(message['params'])
            if message.get('id') == seq:
                if 'error' in message:
                    raise RuntimeError(message['error'])
                return message.get('result', {})

    def settle(self):
        self.evaluate('new Promise(resolve => setTimeout(resolve, 350))')

    def go(self, path):
        self.call('Page.navigate', {'url':ORIGIN + path})
        self.wait('!!document.querySelector("[data-analytics-config]") && document.readyState !== "loading"')
        self.settle()
        self.wait('document.querySelector("[data-analytics-status]")?.textContent !== "" || !JSON.parse(document.querySelector("[data-analytics-config]").dataset.analyticsConfig).enabled')

    def events(self, name=None):
        return [item['command'] for item in self.commands if item['command'][0] == 'event' and (name is None or item['command'][1] == name)]

    def analytics_cookies(self):
        return {(cookie['name'], cookie['domain'], cookie['path']): cookie['value']
            for cookie in self.call('Network.getAllCookies')['cookies'] if cookie['name'].startswith('audience_')}

    def click(self, selector):
        self.evaluate(f'document.querySelector({json.dumps(selector)}).click()')
        self.settle()

    def reset(self, mode='enabled'):
        self.tag_mode = 'mock'
        self.call('Network.clearBrowserCookies')
        self.call('Network.setCookie', {'name':'analytics_test_mode','value':mode,'url':ORIGIN,'path':'/'})
        self.go('/')
        self.evaluate('localStorage.removeItem("audience-consent")')
        self.go('/')
        self.commands.clear()
        self.tag_requests = 0


b = Browser()
def failure(kind, value, traceback):
    print('JavaScript errors:', json.dumps(b.errors, ensure_ascii=False))
    sys.__excepthook__(kind, value, traceback)
sys.excepthook = failure
b.call('Network.enable')
b.call('Runtime.enable')
b.call('Fetch.enable', {'patterns':[{'urlPattern':'*','requestStage':'Request'}]})
checks = []

# Deterministic transport semantics; no real transport or Google library involved.
root = Path(__file__).resolve().parents[2]
transport_source = (root/'assets/modules/analytics_transport.js').read_text(encoding='utf-8').replace('export ', '')
policy_source = (root/'assets/modules/analytics_policy.js').read_text(encoding='utf-8').replace('export ', '')
transport_tests = (root/'tests/Analytics/transport_checks.js').read_text(encoding='utf-8')
transport_checks = b.evaluate('(async () => {'+policy_source+'\n'+transport_source+'\n'+transport_tests+'})()')
(ARTIFACTS/'transport-results.json').write_text(json.dumps(transport_checks,indent=2),encoding='utf-8')
checks.append('transport guard: '+str(len(transport_checks))+' deterministic assertions passed')
if '--transport-only' in sys.argv:
    print(json.dumps(transport_checks,indent=2))
    sys.exit(0)

# Actual frame: a different valid agreement must not authorize an old queue, even
# before any storage notification or periodic cleanup runs in that frame.
b.reset()
b.click('[data-analytics-choice="accepted"]')
b.settle()
before = len(b.commands)
result = b.evaluate('''(async () => {
 const url = performance.getEntriesByType('resource').find(r => r.name.includes('analytics_policy-')).name;
 const {readConsent,sixMonthsAfter} = await import(url);
 const frame = document.querySelector('iframe').contentWindow;
 const value = JSON.parse(localStorage.getItem('audience-consent'));
 value.date -= 1000; value.expires = sixMonthsAfter(value.date);
 localStorage.setItem('audience-consent',JSON.stringify(value));
 return {valid:readConsent()?.choice==='accepted',sent:frame.navigator.sendBeacon('/__analytics_test/collect','{"command":["event","OLD_AGREEMENT"]}')};
})()''')
assert result == {'valid':True,'sent':False} and len(b.commands) == before
checks.append('actual frame rejects old agreement queue before asynchronous notification')
if '--agreement-only' in sys.argv:
    print(json.dumps(checks,indent=2))
    sys.exit(0)

# Regression: private pages stop the runtime without revoking valid consent.
for authenticated in [False, True]:
    b.reset()
    if authenticated:
        b.call('Network.setCookie', {'name':'analytics_test_user','value':'yes','url':ORIGIN,'path':'/'})
    b.go('/rendez-vous/types/1')
    b.click('[data-analytics-choice="accepted"]')
    b.wait('document.cookie.includes("audience_G_TEST123456_ga_TEST123456=")')
    original_cookies = b.analytics_cookies()
    assert len(original_cookies) == 2 and len(set(original_cookies.values())) == 2, original_cookies
    consent_record = b.evaluate('localStorage.getItem("audience-consent")')
    original_tag_requests = b.tag_requests
    public_event_count = len(b.events())
    b.evaluate('window.__publicDocument = true')
    b.click('[data-analytics-event="reserve_click"]')
    private_path = '/rendez-vous/type/1/form' if authenticated else '/login'
    b.wait(f'location.pathname === {json.dumps(private_path)}')
    b.settle()
    assert b.evaluate('window.__publicDocument === undefined'), 'Expected a full document navigation'
    assert not b.evaluate('!!document.querySelector("iframe")')
    assert b.tag_requests == original_tag_requests
    assert [event[1] for event in b.events()[public_event_count:]] == ['reserve_click']
    private_commands = len(b.commands)
    b.evaluate('document.dispatchEvent(new Event("turbo:load")); document.dispatchEvent(new Event("visibilitychange"))')
    b.evaluate('new Promise(resolve => setTimeout(resolve, 1200))')
    assert len(b.commands) == private_commands, 'Private page sent analytics commands'
    assert b.analytics_cookies() == original_cookies, 'Private arrival erased accepted analytics cookies'
    assert b.evaluate('localStorage.getItem("audience-consent")') == consent_record

    # Actual return link, followed by a fresh tag instance that must reuse both cookies.
    b.evaluate('window.__privateDocument = true')
    b.click('a[href="/"]')
    b.wait('location.pathname === "/" && !!document.querySelector("iframe")')
    b.settle()
    assert b.evaluate('window.__privateDocument === undefined')
    assert b.tag_requests == original_tag_requests + 1
    assert b.analytics_cookies() == original_cookies, 'Return recreated cookies instead of retaining them'

    # Withdraw on the private screen, then check that no tag is resurrected on return.
    b.go(private_path)
    assert b.analytics_cookies() == original_cookies
    private_commands, private_tag_requests = len(b.commands), b.tag_requests
    b.click('[data-analytics-manage]')
    b.click('[data-analytics-choice="refused"]')
    assert not b.analytics_cookies()
    assert not b.evaluate('!!document.querySelector("iframe")')
    assert len(b.commands) == private_commands and b.tag_requests == private_tag_requests
    b.go('/')
    assert not b.analytics_cookies() and b.tag_requests == private_tag_requests
    checks.append(f'cookie lifecycle ({"authenticated form" if authenticated else "anonymous login"}): full private navigation, no collection, unchanged cookie values on return, private withdrawal cleans cookies')

# Expiration must also clean retained cookies on a private page with NO iframe.
for reason in ['absent', 'invalid', 'expired', 'disabled', 'missing', 'invalid-id']:
    b.reset()
    b.click('[data-analytics-choice="accepted"]')
    b.wait('document.cookie.includes("audience_G_TEST123456_ga_TEST123456=")')
    original_cookies = b.analytics_cookies()
    b.go('/login')
    assert b.analytics_cookies() == original_cookies and len(original_cookies) == 2
    private_commands, private_tag_requests = len(b.commands), b.tag_requests
    if reason in ['disabled', 'missing', 'invalid-id']:
        mode = 'invalid' if reason == 'invalid-id' else reason
        b.call('Network.setCookie', {'name':'analytics_test_mode','value':mode,'url':ORIGIN,'path':'/'})
        b.go('/login')
    elif reason == 'expired':
        b.evaluate('window.__realNow = Date.now; Date.now = () => window.__realNow() + 367*86400000')
    else:
        b.evaluate('localStorage.removeItem("audience-consent")' if reason == 'absent'
            else 'localStorage.setItem("audience-consent", "{broken")')
        b.evaluate('window.dispatchEvent(new StorageEvent("storage", {key:"audience-consent"}))')
    b.wait('!document.cookie.includes("audience_G_")')
    assert not b.analytics_cookies() and not b.evaluate('!!document.querySelector("iframe")')
    assert len(b.commands) == private_commands and b.tag_requests == private_tag_requests
    if reason == 'expired':
        b.evaluate('Date.now = window.__realNow')
    checks.append(f'private retained cookies cleaned when {reason}; no frame or analytics commands')

if '--cookie-lifecycle-only' in sys.argv:
    report = {'checks':checks, 'real_google_requests':0, 'javascript_errors':b.errors,
        'limits':'Synthetic cookies and intercepted gtag only; actual Google behavior not tested.'}
    (ARTIFACTS / 'cookie-lifecycle-results.json').write_text(json.dumps(report, indent=2), encoding='utf-8')
    print(json.dumps(report, indent=2))
    assert not b.errors, b.errors
    sys.exit(0)

for mode in ['disabled', 'missing', 'invalid']:
    b.reset(mode)
    assert b.evaluate('document.querySelector("#analytics-consent").hidden')
    b.click('[data-analytics-manage]')
    assert b.evaluate('document.querySelector("[data-analytics-choice]").hidden')
    assert not b.tag_requests and not b.events()
checks.append('disabled / absent / invalid ID: no tag and no consent prompt')

b.reset()
assert not b.evaluate('document.querySelector("#analytics-consent").hidden')
b.click('[data-analytics-close]')
assert b.evaluate('localStorage.getItem("audience-consent")') is None
b.click('[data-analytics-event="discover_session_click"]')
b.wait('location.pathname === "/rendez-vous/types/1"')
assert not b.tag_requests and not b.events()
assert '_ga' not in b.evaluate('document.cookie')
b.click('[data-analytics-manage]')
b.click('[data-analytics-choice="refused"]')
saved = b.evaluate('localStorage.getItem("audience-consent")')
b.go('/rendez-vous/types')
assert saved == b.evaluate('localStorage.getItem("audience-consent")')
assert b.evaluate('document.querySelector("#analytics-consent").hidden')
assert not b.tag_requests
checks.append('close and pre-consent interactions do not consent; refusal persists without tag/cookies')

b.click('[data-analytics-manage]')
b.click('[data-analytics-choice="accepted"]')
b.wait('!!document.querySelector("iframe")')
b.settle()
assert len(b.events('page_view')) == 1, b.commands
assert len(b.events('discover_session_click')) == 0
assert b.tag_requests == 1
b.evaluate('document.dispatchEvent(new Event("turbo:load")); document.dispatchEvent(new Event("DOMContentLoaded"))')
b.settle()
assert len(b.events('page_view')) == 1
b.click('a[data-action*="showDetail"]')
b.wait('!!document.querySelector("[data-analytics-service]")')
b.settle()
assert len(b.events('view_service')) == 1, b.events()
assert b.events('view_service')[0][2]['display_context'] == 'catalogue_ajax'
assert len(b.events('page_view')) == 1
b.evaluate('document.dispatchEvent(new Event("analytics:service-displayed")); document.dispatchEvent(new Event("turbo:load"))')
b.settle()
assert len(b.events('view_service')) == 1
b.click('a[data-action*="reloadRecap"]')
b.wait('!!document.querySelector("a[data-action*=showDetail]")')
b.click('a[data-action*="showDetail"]')
b.wait('!!document.querySelector("[data-analytics-service]")')
b.settle()
assert len(b.events('view_service')) == 2
checks.append('one initialization; hook deduplication; AJAX views without extra page_view; repeat view counted')

# Reserve remains a native, unblocked navigation, and private responses exclude the runtime.
b.click('[data-analytics-event="reserve_click"]')
b.wait('location.pathname === "/login"')
b.settle()
assert len(b.events('reserve_click')) == 1, b.events()
assert not b.evaluate('!!document.querySelector("iframe")')
assert b.events('reserve_click')[0][2]['display_context'] == 'catalogue_ajax'
checks.append('anonymous reserve click tracked; full navigation to private login, no analytics runtime')

# Direct page with sensitive query/fragment and title never transmits those values.
b.commands.clear()
b.go('/rendez-vous/types/1?email=PRIVATE_EMAIL&token=PRIVATE_TOKEN#PRIVATE_FRAGMENT')
b.settle()
assert len(b.events('page_view')) == 1 and len(b.events('view_service')) == 1
assert b.events('page_view')[0][2]['page_location'] == ORIGIN + '/rendez-vous/types/1'
assert all(item['referrer'] == '' for item in b.commands), b.commands
assert not any(secret in json.dumps(b.commands) for secret in ['PRIVATE_EMAIL','PRIVATE_TOKEN','PRIVATE_FRAGMENT'])
checks.append('direct view counted once; raw query, fragment, DOM title and referrer absent')

# Withdrawal after load deletes only our prefixed cookies, including domain/path variants.
b.evaluate('document.cookie="unrelated=keep; path=/"; document.cookie="audience_G_TEST123456_ga_TEST123456=variant; path=/rendez-vous; domain=127.0.0.1"')
b.click('[data-analytics-manage]')
b.click('[data-analytics-choice="refused"]')
assert not b.evaluate('!!document.querySelector("iframe")')
cookies = b.call('Network.getAllCookies')['cookies']
assert not any(c['name'].startswith('audience_') for c in cookies), cookies
assert any(c['name'] == 'unrelated' for c in cookies)
before = len(b.events())
b.go('/rendez-vous/types/1')
assert len(b.events()) == before
checks.append('withdrawal destroys tag context and scoped cookies; unrelated cookies retained')

# Withdrawal while the script request is still held. Releasing the old response cannot resurrect it.
b.reset()
b.tag_mode = 'hold'
b.click('[data-analytics-choice="accepted"]')
b.wait('!!document.querySelector("iframe")')
b.settle()
assert b.held
b.click('[data-analytics-manage]')
b.click('[data-analytics-choice="refused"]')
for request_id in b.held:
    b.fulfill(request_id, MOCK)
b.held.clear()
b.settle()
assert not b.events() and not b.evaluate('!!document.querySelector("iframe")')
assert '_ga' not in b.evaluate('document.cookie')
checks.append('withdrawal during asynchronous load prevents resurrection and queued events')

# A detached old frame must not replay work when a new agreement is accepted.
b.reset()
b.click('[data-analytics-choice="accepted"]')
b.settle()
b.evaluate('window.oldAnalyticsFetch = document.querySelector("iframe").contentWindow.fetch')
b.click('[data-analytics-manage]')
b.click('[data-analytics-choice="refused"]')
b.commands.clear()
b.click('[data-analytics-manage]')
b.click('[data-analytics-choice="accepted"]')
b.settle()
assert len(b.events('page_view')) == 1, b.events()
count = len(b.commands)
outcome = b.evaluate('''(async () => {
 try { await window.oldAnalyticsFetch('/__analytics_test/collect', {method:'POST',body:'{"command":["event","OLD_QUEUE"]}'}); return 'sent'; }
 catch(e) { return e.name; }
})()''')
assert outcome == 'AbortError' and len(b.commands) == count, (outcome,b.commands)
checks.append('new agreement measures current page once; old detached context cannot replay its queue')

for value in ['{broken', json.dumps({'version':999,'choice':'accepted'}), json.dumps({'version':1,'choice':'accepted','date':1,'expires':2})]:
    b.reset()
    b.evaluate(f'localStorage.setItem("audience-consent", {json.dumps(value)})')
    b.go('/')
    assert not b.tag_requests and not b.evaluate('document.querySelector("#analytics-consent").hidden')
checks.append('malformed, wrong-version and expired consent fail closed')

# A blocked script never prevents a functional visit or reservation link.
b.reset()
b.tag_mode = 'fail'
b.click('[data-analytics-choice="accepted"]')
b.click('[data-analytics-event="discover_session_click"]')
b.wait('location.pathname === "/rendez-vous/types/1"')
b.click('[data-analytics-event="reserve_click"]')
b.wait('location.pathname === "/login"')
assert not b.events()
checks.append('tag failure leaves public navigation and reservation entry functional')

# Real Turbo visits and history restoration are distinct displays; repeated hooks are not.
b.reset()
b.click('[data-analytics-choice="accepted"]')
b.click('[data-analytics-event="discover_session_click"]')
b.wait('location.pathname === "/rendez-vous/types/1"')
b.settle()
assert len(b.events('page_view')) == 2, b.events()
assert len(b.events('discover_session_click')) == 1
b.evaluate('history.back()')
b.wait('location.pathname === "/" && !!document.querySelector("[data-analytics-event=discover_session_click]")')
b.settle()
assert len(b.events('page_view')) == 3, b.events()
assert b.tag_requests == 1, b.tag_requests
checks.append('Turbo initial visit and cached history restoration counted once each, one tag instance')

# Authenticated entry uses the actual Symfony security token and a synthetic user.
b.reset()
b.call('Network.setCookie', {'name':'analytics_test_user','value':'yes','url':ORIGIN,'path':'/'})
b.go('/rendez-vous/types/1')
b.click('[data-analytics-choice="accepted"]')
b.settle()
b.click('[data-analytics-event="reserve_click"]')
b.wait('location.pathname === "/rendez-vous/type/1/form"')
b.settle()
assert len(b.events('reserve_click')) == 1, b.events()
assert b.events('reserve_click')[0][2]['display_context'] == 'service_page'
assert not b.evaluate('!!document.querySelector("iframe")')
checks.append('authenticated reserve click from direct detail; real private form GET without submission or collection')

# Replaced/aborted AJAX and a superseded DOM insertion must not become views.
b.reset()
b.go('/rendez-vous/types')
b.click('[data-analytics-choice="accepted"]')
b.hold_detail = True
b.click('a[href="/rendez-vous/types/1"][data-action]')
b.click('a[href="/rendez-vous/types/2"][data-action]')
assert len(b.details) == 2, b.details
for request_id, service in zip(b.details, ['1', '2']):
    b.fulfill(request_id, f'<div data-analytics-service="{service}"><h1>Prestation synthétique {service}</h1></div>', 'text/html; charset=utf-8')
b.details.clear()
b.hold_detail = False
b.wait('document.querySelector("[data-analytics-service]")?.dataset.analyticsService === "2"')
b.settle()
assert len(b.events('view_service')) == 1 and b.events('view_service')[0][2]['service_id'] == '2', b.events()
b.evaluate('''(() => {
 const c = document.querySelector('[data-appointment-type-target=container]');
 c.innerHTML = '<div data-analytics-service="1"><h1>Not painted</h1></div>';
 document.dispatchEvent(new Event('analytics:service-displayed'));
 c.innerHTML = '<p>Replaced before paint</p>';
})()''')
b.settle()
assert len(b.events('view_service')) == 1
checks.append('aborted/replaced AJAX and replaced-before-paint insertion are not counted')

# Failed AJAX falls back to the real public document, without counting a phantom fragment.
b.reset()
b.go('/rendez-vous/types')
b.click('[data-analytics-choice="accepted"]')
b.hold_detail = True
b.click('a[href="/rendez-vous/types/1"][data-action]')
assert len(b.details) == 1
b.send('Fetch.failRequest', {'requestId':b.details.pop(),'errorReason':'Failed'})
b.hold_detail = False
b.wait('location.pathname === "/rendez-vous/types/1"')
b.settle()
assert len(b.events('view_service')) == 1, b.events()
assert b.events('view_service')[0][2]['display_context'] == 'service_page'
checks.append('failed AJAX: no fragment view, functional fallback to real detail navigation')

# Calendar-month expiry, future dates, wrong expires, and denied browser storage.
b.reset()
policy_result = b.evaluate('''(async () => {
 const url = performance.getEntriesByType('resource').find(r => r.name.includes('analytics_policy-')).name;
 const {readConsent,sixMonthsAfter} = await import(url);
 const now = Date.now(), date = Date.UTC(2025,7,31,12), expires = sixMonthsAfter(date);
 const read = value => readConsent({getItem:()=>JSON.stringify(value)},now);
 return {clamp:new Date(expires).toISOString(),
   expired:read({version:1,choice:'accepted',date,expires}) === null,
   future:read({version:1,choice:'accepted',date:now+10000,expires:sixMonthsAfter(now+10000)}) === null,
   wrongExpiry:read({version:1,choice:'accepted',date:now,expires:now+1000}) === null,
   throws:readConsent({getItem:()=>{throw Error('blocked')}},now) === null};
})()''')
assert policy_result == {'clamp':'2026-02-28T12:00:00.000Z','expired':True,'future':True,'wrongExpiry':True,'throws':True}, policy_result
b.click('[data-analytics-choice="accepted"]')
b.evaluate('window.__realNow = Date.now; Date.now = () => window.__realNow() + 367*86400000')
b.wait('!document.querySelector("iframe")')
assert '_ga' not in b.evaluate('document.cookie')
b.evaluate('Date.now = window.__realNow')
b.click('[data-analytics-choice="accepted"]')
b.evaluate('localStorage.removeItem("audience-consent"); window.dispatchEvent(new StorageEvent("storage", {key:"audience-consent"}))')
b.settle()
assert not b.evaluate('!!document.querySelector("iframe")')
assert '_ga' not in b.evaluate('document.cookie')
b.evaluate('Storage.prototype.getItem = () => {throw Error("denied")}; Storage.prototype.setItem = () => {throw Error("denied")};')
b.click('[data-analytics-choice="accepted"]')
assert not b.evaluate('!!document.querySelector("iframe")')
assert 'désactivée' in b.evaluate('document.querySelector("[data-analytics-status]").textContent')
checks.append('six calendar months with clamping; future/expired/invalid dates; cross-tab removal signal; unavailable storage fails closed')

# Consent has its own module graph, so even an unrelated Google Maps import failing
# cannot prevent a visitor from refusing or managing analytics.
b.mock_maps = False
b.reset()
b.click('[data-analytics-choice="refused"]')
assert b.evaluate('JSON.parse(localStorage.getItem("audience-consent")).choice') == 'refused'
b.click('[data-analytics-event="discover_session_click"]')
b.wait('location.pathname === "/rendez-vous/types/1"')
assert not b.tag_requests
b.mock_maps = True
checks.append('consent and native public links remain usable when another Google dependency is blocked')

# Consent controls: actual keyboard activation, identical buttons, mobile and both themes.
for width, height in [(1365,900),(390,844)]:
    b.call('Emulation.setDeviceMetricsOverride', {'width':width,'height':height,'deviceScaleFactor':1,'mobile':width < 500})
    b.reset()
    for theme in ['light','dark']:
        b.evaluate(f'document.documentElement.dataset.bsTheme = {json.dumps(theme)}')
        assert b.evaluate('document.documentElement.scrollWidth <= innerWidth')
        sizes = b.evaluate('Array.from(document.querySelectorAll("[data-analytics-choice]")).map(el => ({w:el.offsetWidth,h:el.offsetHeight,color:getComputedStyle(el).color,bg:getComputedStyle(el).backgroundColor}))')
        assert sizes[0] == sizes[1] and sizes[0]['h'] >= 44, sizes
        shot = b.call('Page.captureScreenshot', {'format':'png','captureBeyondViewport':False})
        (ARTIFACTS / f'consent-{width}-{theme}.png').write_bytes(base64.b64decode(shot['data']))
    b.evaluate('document.querySelector("[data-analytics-choice=accepted]").focus()')
    for kind in ['keyDown','keyUp']:
        b.call('Input.dispatchKeyEvent', {'type':kind,'key':'Tab','code':'Tab','windowsVirtualKeyCode':9})
    assert b.evaluate('document.activeElement.dataset.analyticsChoice') == 'refused'
    for kind in ['keyDown','keyUp']:
        b.call('Input.dispatchKeyEvent', {'type':kind,'key':'Enter','code':'Enter','windowsVirtualKeyCode':13,'text':'\r'})
    b.settle()
    assert b.evaluate('JSON.parse(localStorage.getItem("audience-consent")).choice') == 'refused'
    assert b.evaluate('document.activeElement.hasAttribute("data-analytics-manage")')
checks.append('keyboard Tab/Enter and focus return; equal buttons; desktop/mobile light/dark without overflow')

report = {'checks':checks,'external_requests_blocked':b.blocked_external,
    'real_google_requests':0, 'javascript_errors':b.errors,
    'limits':'Mock validates our lifecycle and payloads, not proprietary gtag automatic events or Google configuration.'}
(ARTIFACTS / 'results.json').write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding='utf-8')
print(json.dumps(report, ensure_ascii=False, indent=2))
assert not b.errors, b.errors
