"""Local Edge/CDP helper using only Python's standard library; no credentials printed."""
import base64
import hashlib
import json
import os
from pathlib import Path
import socket
import struct
import sys
import time
import urllib.request
from urllib.parse import urlsplit
sys.stdout.reconfigure(encoding='utf-8')

STATE = Path(os.environ['LOCALAPPDATA']) / 'Temp/appointment-payment-browser-20261007'


class Browser:
    def __init__(self, iframe=None, origin='http://127.0.0.1:8097'):
        targets = json.load(urllib.request.urlopen('http://127.0.0.1:9227/json'))
        target = next(t for t in targets if (t['type'] == 'iframe' and iframe in t['url']) if iframe) if iframe else next(t for t in targets if t['type'] == 'page' and t['url'].startswith(origin))
        url = urlsplit(target['webSocketDebuggerUrl'])
        self.sock = socket.create_connection((url.hostname, url.port), timeout=15)
        key = base64.b64encode(os.urandom(16)).decode()
        self.sock.sendall((f'GET {url.path} HTTP/1.1\r\nHost: {url.netloc}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n').encode())
        response = b''
        while not response.endswith(b'\r\n\r\n'):
            response += self.sock.recv(1)
        assert response.split(b'\r\n', 1)[0].split()[1] == b'101', 'WebSocket upgrade rejected'
        expected = base64.b64encode(hashlib.sha1((key + '258EAFA5-E914-47DA-95CA-C5AB0DC85B11').encode()).digest())
        assert expected in response
        self.seq = 0

    def read(self, size):
        data = b''
        while len(data) < size:
            chunk = self.sock.recv(size - len(data))
            if not chunk:
                raise RuntimeError('CDP disconnected')
            data += chunk
        return data

    def call(self, method, params=None):
        self.seq += 1
        data = json.dumps({'id': self.seq, 'method': method, 'params': params or {}}).encode()
        mask = os.urandom(4)
        header = bytes([0x81, 0x80 | len(data)]) if len(data) < 126 else bytes([0x81, 0xFE]) + struct.pack('!H', len(data))
        self.sock.sendall(header + mask + bytes(c ^ mask[i % 4] for i, c in enumerate(data)))
        while True:
            first, second = self.read(2)
            length = second & 127
            if length == 126:
                length = struct.unpack('!H', self.read(2))[0]
            elif length == 127:
                length = struct.unpack('!Q', self.read(8))[0]
            message = json.loads(self.read(length))
            if message.get('id') == self.seq:
                if 'error' in message:
                    raise RuntimeError('CDP command failed: ' + method)
                return message.get('result', {})

    def evaluate(self, expression):
        result = self.call('Runtime.evaluate', {'expression': expression, 'returnByValue': True, 'awaitPromise': True})
        if 'exceptionDetails' in result:
            raise RuntimeError('Browser expression failed (details withheld)')
        return result.get('result', {}).get('value')

    def wait(self, expression):
        end = time.monotonic() + 25
        while time.monotonic() < end:
            try:
                if self.evaluate(expression):
                    return
            except RuntimeError:
                pass
            time.sleep(.1)
        raise RuntimeError('Browser condition timed out')


if __name__ == '__main__':
    action = sys.argv[1] if len(sys.argv) > 1 else 'inspect'
    browser = Browser('elements-inner' if action == 'card' else (sys.argv[2] if action == 'iframe' else None))
    if action == 'navigate':
        assert sys.argv[2].startswith('/') and not sys.argv[2].startswith('//')
        browser.call('Page.navigate', {'url': 'http://127.0.0.1:8097' + sys.argv[2]})
        print('Local navigation requested')
    elif action == 'leave-dialog':
        browser.call('Page.handleJavaScriptDialog', {'accept': True})
        print('Browser leave-page dialog accepted')
    elif action == 'evaluate':
        print(json.dumps(browser.evaluate(sys.argv[2]), ensure_ascii=False))
    elif action == 'iframe':
        print(json.dumps(browser.evaluate(sys.argv[3]), ensure_ascii=False))
    elif action == 'card':
        cards = {'success': '4242424242424242', 'decline': '4000000000000002', '3ds': '4000002500003155'}
        for name, value in [('number', cards[sys.argv[2]]), ('expiry', '1234'), ('cvc', '123')]:
            browser.evaluate('document.getElementsByName(%s)[0].focus(); true' % json.dumps(name))
            browser.call('Input.dispatchKeyEvent', {'type': 'keyDown', 'key': 'a', 'code': 'KeyA', 'modifiers': 2, 'windowsVirtualKeyCode': 65})
            browser.call('Input.dispatchKeyEvent', {'type': 'keyUp', 'key': 'a', 'code': 'KeyA', 'modifiers': 2, 'windowsVirtualKeyCode': 65})
            browser.call('Input.insertText', {'text': value})
        browser.evaluate('''(() => { const country=document.getElementsByName('country')[0]; country.value='FR'; country.dispatchEvent(new Event('change',{bubbles:true})); return true; })()''')
        print('Official TEST card entered in Stripe Element: ' + sys.argv[2])
    elif action == 'login':
        account = json.loads((STATE / 'accounts-private.json').read_text())[sys.argv[2]]
        browser.evaluate('''(() => { const a = %s; document.querySelector('[name="_username"]').value=a.email; document.querySelector('[name="_password"]').value=a.password; document.querySelector('form').requestSubmit(); return true; })()''' % json.dumps(account))
        print('Fictitious credentials submitted without display')
    elif action == 'account':
        browser.call('Network.clearBrowserCookies')
        browser.evaluate('window.__recetteOldPage=true')
        browser.call('Page.navigate', {'url': 'http://127.0.0.1:8097/login?target=/rendez-vous/list'})
        browser.wait('!window.__recetteOldPage && document.readyState === "complete" && !!document.getElementsByName("_username")[0]')
        account = json.loads((STATE / 'accounts-private.json').read_text())[sys.argv[2]]
        browser.evaluate('''(() => { const a = %s; document.querySelector('[name="_username"]').value=a.email; document.querySelector('[name="_password"]').value=a.password; document.querySelector('form').requestSubmit(); return true; })()''' % json.dumps(account))
        browser.wait('!!document.getElementsByName("_auth_code")[0] && document.readyState === "complete"')
        import re
        mails = [json.loads(p.read_text()) for p in sorted((STATE / 'mail').glob('*.json'), key=lambda p:p.stat().st_mtime)]
        mail = next(m for m in reversed(mails) if account['email'] in m['to'] and 'vérification' in m['subject'])
        code = re.findall(r'\b\d{6}\b', (mail.get('text') or '') + ' ' + re.sub('<[^>]+>', ' ', mail.get('html') or ''))[-1]
        browser.evaluate('''(() => { document.getElementsByName('_auth_code')[0].value=%s; document.querySelector('form').requestSubmit(); return true; })()''' % json.dumps(code))
        browser.wait('location.pathname === "/rendez-vous/list" && document.readyState === "complete"')
        print('Fictitious account authenticated with captured 2FA: ' + sys.argv[2])
    elif action == 'two-factor':
        files = sorted((STATE / 'mail').glob('*.json'), key=lambda p: p.stat().st_mtime)
        mail = json.loads(files[-1].read_text())
        import re
        codes = re.findall(r'\b\d{6}\b', (mail.get('text') or '') + ' ' + re.sub('<[^>]+>', ' ', mail.get('html') or ''))
        assert codes, 'No local captured authentication code'
        browser.evaluate('''(() => { document.querySelector('[name="_auth_code"]').value=%s; document.querySelector('form').requestSubmit(); return true; })()''' % json.dumps(codes[-1]))
        print('Captured local 2FA submitted without display')
    elif action in ('reserve', 'new-reservation'):
        if action == 'new-reservation':
            browser.evaluate('window.__recetteOldPage=true')
            browser.call('Page.navigate', {'url': 'http://127.0.0.1:8097/rendez-vous/types'})
            browser.wait('!window.__recetteOldPage && document.readyState === "complete"')
            browser.wait('!!document.querySelector("[data-url=\\"/rendez-vous/types/1\\"]")')
            browser.evaluate('document.querySelector("[data-url=\\"/rendez-vous/types/1\\"]").click(); true')
            browser.wait('!!document.querySelector("[data-url=\\"/rendez-vous/type/1/form\\"]")')
            browser.evaluate('document.querySelector("[data-url=\\"/rendez-vous/type/1/form\\"]").click(); true')
            browser.wait('!!document.getElementsByName("appointment_form[startAt]")[0]')
        values = {'appointment_form[startAt]': sys.argv[2], 'appointment_form[evaluatedPerson][firstname]': 'Alice',
                  'appointment_form[evaluatedPerson][lastname]': 'Fictif', 'appointment_form[evaluatedPerson][patronyms]': 'Alice Fictive',
                  'appointment_form[evaluatedPerson][birthdate]': '1990-01-01'}
        browser.evaluate('''(() => { for (const [name,value] of Object.entries(%s)) { document.getElementsByName(name)[0].value=value; } document.querySelector('form').requestSubmit(); return true; })()''' % json.dumps(values))
        browser.wait('location.pathname.startsWith("/rendez-vous/checkout/")')
        print('Fictitious reservation form submitted')
    elif action == 'checkout':
        browser.wait('!!document.querySelector("input[type=checkbox]")')
        browser.evaluate('document.querySelector("input[type=checkbox]").click(); document.querySelector("form").requestSubmit(); true')
        browser.wait('location.pathname.startsWith("/rendez-vous/pay/")')
        print('Acceptance submitted; payment page reached')
    elif action == 'cancel-amount-proof':
        browser.evaluate('window.__recetteOldPage=true')
        browser.call('Page.navigate', {'url': 'http://127.0.0.1:8097/rendez-vous/list'})
        browser.wait('!window.__recetteOldPage && document.readyState === "complete"')
        selector = '[data-appointment-cancel-appointment-id-value="1"]'
        browser.wait('!!document.querySelector(' + json.dumps(selector + ' [data-action="appointment-cancel#open"]') + ')')
        browser.evaluate('document.querySelector(' + json.dumps(selector + ' [data-action="appointment-cancel#open"]') + ').click(); true')
        browser.wait('!!document.querySelector(' + json.dumps(selector + ' .modal.show') + ')')
        proof = browser.evaluate('''(() => { const e=document.querySelector(%s); return {appointment:1,amountCents:Number(e.dataset.appointmentCancelAmountCentsValue),modalText:e.querySelector('[data-appointment-cancel-target="body"]').innerText}; })()''' % json.dumps(selector))
        shot = browser.call('Page.captureScreenshot', {'format': 'png'})
        (STATE / ('cancel-amount-' + str(proof['amountCents']) + '.png')).write_bytes(base64.b64decode(shot['data']))
        print(json.dumps(proof, ensure_ascii=False))
    elif action == 'ownership-proof':
        result = browser.evaluate('''(async () => { const before=await (await fetch('/_recette/status')).json(); const routes=[]; for (const p of ['/rendez-vous/checkout/2','/rendez-vous/pay/2','/rendez-vous/terminate/2']) { const r=await fetch(p); routes.push({requested:p,finalPath:new URL(r.url).pathname}); } const after=await (await fetch('/_recette/status')).json(); return {routes,appointmentsUnchanged:JSON.stringify(before.appointments)===JSON.stringify(after.appointments),mailUnchanged:before.captured_mail_count===after.captured_mail_count}; })()''')
        (STATE / 'proof-ownership.json').write_text(json.dumps(result, indent=2))
        print(json.dumps(result))
    elif action == 'canceled-return-proof':
        result = browser.evaluate('''(async () => { const before=await (await fetch('/_recette/status')).json(); const r=await fetch('/rendez-vous/terminate/3'); const html=await r.text(); const after=await (await fetch('/_recette/status')).json(); return {finalPath:new URL(r.url).pathname,cancellationMessage:html.includes('reste annulé'),appointmentsUnchanged:JSON.stringify(before.appointments)===JSON.stringify(after.appointments),mailUnchanged:before.captured_mail_count===after.captured_mail_count}; })()''')
        (STATE / 'proof-canceled-return.json').write_text(json.dumps(result, indent=2))
        print(json.dumps(result))
    elif action == 'mobile-review':
        browser.call('Emulation.setDeviceMetricsOverride', {'width': 390, 'height': 844, 'deviceScaleFactor': 1, 'mobile': True})
        results = []
        for name, path in [('list', '/rendez-vous/list'), ('checkout', '/rendez-vous/checkout/2'), ('payment', '/rendez-vous/pay/2')]:
            browser.evaluate('window.__recetteOldPage=true')
            browser.call('Page.navigate', {'url': 'http://127.0.0.1:8097' + path})
            browser.wait('!window.__recetteOldPage && document.readyState === "complete"')
            browser.wait('location.pathname === ' + json.dumps(path))
            if name == 'payment':
                browser.wait('!!document.querySelector("#payment-element iframe")')
                browser.evaluate('document.querySelector("#submit").scrollIntoView({block:"center"}); true')
            results.append(browser.evaluate('''(() => { const b=document.querySelector('#submit'); const r=b?.getBoundingClientRect(); const hit=r ? document.elementFromPoint(r.x+r.width/2,r.y+r.height/2) : null; return {path:location.pathname,width:innerWidth,clientWidth:document.documentElement.clientWidth,scrollWidth:document.documentElement.scrollWidth,paymentButtonVisible:r ? r.top>=0 && r.bottom<=innerHeight && b.contains(hit) : null,overflow:[...document.querySelectorAll('body *')].filter(e=>e.getBoundingClientRect().right>391).map(e=>({tag:e.tagName,class:e.className})).slice(0,12)}; })()'''))
            shot = browser.call('Page.captureScreenshot', {'format': 'png'})
            (STATE / ('mobile-' + name + '.png')).write_bytes(base64.b64decode(shot['data']))
        (STATE / 'proof-mobile.json').write_text(json.dumps(results, indent=2))
        print(json.dumps(results))
    elif action == 'mobile':
        browser.call('Emulation.setDeviceMetricsOverride', {'width': 390, 'height': 844, 'deviceScaleFactor': 1, 'mobile': True})
        result = browser.call('Page.captureScreenshot', {'format': 'png'})
        (STATE / 'mobile.png').write_bytes(base64.b64decode(result['data']))
        print(json.dumps(browser.evaluate('({path:location.pathname,width:innerWidth,scrollWidth:document.documentElement.scrollWidth})')))
    elif action == 'desktop':
        browser.call('Emulation.setDeviceMetricsOverride', {'width': 1440, 'height': 1000, 'deviceScaleFactor': 1, 'mobile': False})
        result = browser.call('Page.captureScreenshot', {'format': 'png'})
        (STATE / 'desktop.png').write_bytes(base64.b64decode(result['data']))
        print('Viewport 1440x1000 captured')
    elif action == 'frames':
        def visit(node):
            print(json.dumps({'id': node['frame']['id'], 'path': urlsplit(node['frame']['url']).path}))
            for child in node.get('childFrames', []):
                visit(child)
        visit(browser.call('Page.getFrameTree')['frameTree'])
    elif action == 'frame-evaluate':
        context = browser.call('Page.createIsolatedWorld', {'frameId': sys.argv[2], 'worldName': 'recette'})['executionContextId']
        result = browser.call('Runtime.evaluate', {'expression': sys.argv[3], 'contextId': context, 'returnByValue': True})
        print(json.dumps(result.get('result', {}).get('value'), ensure_ascii=False))
    elif action == 'screenshot':
        result = browser.call('Page.captureScreenshot', {'format': 'png'})
        (STATE / 'browser.png').write_bytes(base64.b64decode(result['data']))
        print(str(STATE / 'browser.png'))
    else:
        print(json.dumps(browser.evaluate('({path:location.pathname,title:document.title,inputs:[...document.querySelectorAll("input,select,button")].map(x=>({name:x.name,type:x.type,text:x.tagName==="BUTTON"?x.innerText:undefined})),width:innerWidth,scrollWidth:document.documentElement.scrollWidth})'), ensure_ascii=False))
