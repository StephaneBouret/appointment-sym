"""Read-only local browser checks. Start an isolated Chrome CDP on port 9231 first.

Uses only Python's standard library. Blocks Google, Stripe, the production host,
and every non-GET/HEAD request. Never submits a reservation or authentication form.
"""
import base64
import json
import os
from pathlib import Path
import socket
import struct
import time
import urllib.request
from urllib.parse import urlsplit

ORIGIN = 'http://127.0.0.1:8000'
ARTIFACTS = Path(__file__).resolve().parents[2] / 'var/prestations-tests'


class Browser:
    def __init__(self, allowed_origin=None):
        targets = json.load(urllib.request.urlopen('http://127.0.0.1:9231/json'))
        target = next(t for t in targets if t['type'] == 'page')
        url = urlsplit(target['webSocketDebuggerUrl'])
        self.sock = socket.create_connection((url.hostname, url.port), timeout=20)
        key = base64.b64encode(os.urandom(16)).decode()
        self.sock.sendall((f'GET {url.path} HTTP/1.1\r\nHost: {url.netloc}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n').encode())
        response = b''
        while not response.endswith(b'\r\n\r\n'):
            response += self.sock.recv(1)
        assert b'101' in response.split(b'\r\n', 1)[0]
        self.seq = 0
        self.blocked_writes = 0
        self.allowed_origin = allowed_origin
        self.blocked_external = 0

    def read(self, size):
        data = b''
        while len(data) < size:
            chunk = self.sock.recv(size - len(data))
            if not chunk:
                raise RuntimeError('CDP disconnected')
            data += chunk
        return data

    def send(self, method, params):
        self.seq += 1
        data = json.dumps({'id': self.seq, 'method': method, 'params': params}).encode()
        mask = os.urandom(4)
        header = bytes([0x81, 0x80 | len(data)]) if len(data) < 126 else bytes([0x81, 0xFE]) + struct.pack('!H', len(data))
        self.sock.sendall(header + mask + bytes(c ^ mask[i % 4] for i, c in enumerate(data)))
        return self.seq

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
                request = message['params']
                if 'unpkg.com/@googlemaps/' in request['request']['url']:
                    # This eagerly imported module would otherwise prevent all
                    # Stimulus controllers from loading when Google is blocked.
                    body = b'export const APILoader = { importLibrary() { throw new Error("Google disabled in local browser checks"); } };'
                    self.send('Fetch.fulfillRequest', {'requestId': request['requestId'], 'responseCode': 200,
                        'responseHeaders': [{'name': 'Content-Type', 'value': 'application/javascript'},
                            {'name': 'Access-Control-Allow-Origin', 'value': '*'}], 'body': base64.b64encode(body).decode()})
                elif request['request']['method'] not in ('GET', 'HEAD'):
                    self.blocked_writes += 1
                    self.send('Fetch.failRequest', {'requestId': request['requestId'], 'errorReason': 'BlockedByClient'})
                elif self.allowed_origin and not request['request']['url'].startswith(self.allowed_origin + '/'):
                    self.blocked_external += 1
                    self.send('Fetch.failRequest', {'requestId': request['requestId'], 'errorReason': 'BlockedByClient'})
                else:
                    self.send('Fetch.continueRequest', {'requestId': request['requestId']})
            if message.get('id') == seq:
                if 'error' in message:
                    raise RuntimeError(message['error'])
                return message.get('result', {})

    def evaluate(self, expression):
        result = self.call('Runtime.evaluate', {'expression': expression, 'returnByValue': True, 'awaitPromise': True})
        if 'exceptionDetails' in result:
            raise RuntimeError(result['exceptionDetails'].get('text', 'JavaScript error'))
        return result.get('result', {}).get('value')

    def wait(self, expression):
        end = time.monotonic() + 30
        while time.monotonic() < end:
            if self.evaluate(expression):
                return
            time.sleep(.1)
        raise RuntimeError('Browser condition timed out: ' + expression)

    def navigate(self, path):
        assert path.startswith('/') and not path.startswith('//')
        self.call('Page.navigate', {'url': ORIGIN + path})
        self.wait('document.readyState === "complete"')

    def screenshot(self, name):
        result = self.call('Page.captureScreenshot', {'format': 'png', 'captureBeyondViewport': True})
        (ARTIFACTS / name).write_bytes(base64.b64decode(result['data']))


if __name__ == '__main__':
    browser = Browser()
    browser.call('Network.enable')
    browser.call('Network.setBlockedURLs', {'urls': ['*googleapis.com*', '*google.com*', '*gstatic.com*', '*stripe*', '*francetuncq.fr*']})
    browser.call('Fetch.enable', {'patterns': [{'urlPattern': '*', 'requestStage': 'Request'}]})
    browser.call('Emulation.setDeviceMetricsOverride', {'width': 1365, 'height': 900, 'deviceScaleFactor': 1, 'mobile': False})
    browser.navigate('/rendez-vous/types/1')
    browser.wait('!!document.querySelector(".skhvxm")')
    details = browser.evaluate('''({title: document.title.trim(), heading: document.querySelector('h1').innerText,
        canonical: document.querySelector('link[rel="canonical"]').href,
        reserve: document.querySelector('a.tj8cbo').getAttribute('href'),
        imageLoaded: document.querySelector('.skhvxm img').complete && document.querySelector('.skhvxm img').naturalWidth > 0,
        overflow: document.documentElement.scrollWidth > innerWidth})''')
    assert 'CAP SUR VOTRE IDENTIT' in details['heading'], details
    assert details['imageLoaded'], details
    browser.screenshot('detail-desktop.png')
    browser.call('Emulation.setDeviceMetricsOverride', {'width': 390, 'height': 844, 'deviceScaleFactor': 1, 'mobile': True})
    browser.navigate('/rendez-vous/types/1')
    assert browser.evaluate('document.documentElement.scrollWidth <= 390'), 'Mobile overflow on detail'
    browser.screenshot('detail-mobile.png')
    browser.navigate('/rendez-vous/types')
    browser.wait('!!document.querySelector("a[data-action*=showDetail]")')
    assert browser.evaluate(r'!!document.querySelector("a[href=\"/rendez-vous/types/1\"]")')
    browser.evaluate("import('app').then(() => new Promise(resolve => setTimeout(() => resolve(true), 100)))")
    # Test modified-click behavior without actually opening an uncontrolled tab.
    modifiers = browser.evaluate('''(async () => {
        const url = performance.getEntriesByType('resource').find(r => r.name.includes('appointment_type_controller')).name;
        const {default: Controller} = await import(url);
        for (const variant of [{ctrlKey:true}, {metaKey:true}, {shiftKey:true}, {altKey:true}, {button:1}, {target:'_blank'}]) {
            let intercepted = false;
            const event = {button:0, currentTarget:{href:'/rendez-vous/types/1',target:variant.target || ''},
                preventDefault(){intercepted = true;}, ...variant};
            Controller.prototype.followLink.call({fetchIntoContainer(){intercepted = true;}}, event);
            if (intercepted) return false;
        }
        return true;
    })()''')
    assert modifiers, 'Modified clicks must retain native browser behavior'
    browser.evaluate(r'document.querySelector("a[href=\"/rendez-vous/types/1\"]").focus()')
    browser.call('Input.dispatchKeyEvent', {'type': 'keyDown', 'key': 'Enter', 'code': 'Enter', 'windowsVirtualKeyCode': 13})
    browser.call('Input.dispatchKeyEvent', {'type': 'keyUp', 'key': 'Enter', 'code': 'Enter', 'windowsVirtualKeyCode': 13})
    browser.wait('!!document.querySelector(".skhvxm")')
    assert browser.evaluate('location.pathname') == '/rendez-vous/types', 'Catalogue AJAX not preserved'
    assert browser.evaluate('document.activeElement.tagName') == 'H1', 'Focus not moved to detail'
    browser.evaluate('document.querySelector("a.tj8cbo").click()')
    browser.wait('location.pathname === "/login" && !!document.querySelector("#login_form")')
    browser.evaluate(r'document.querySelector("a[href=\"/inscription\"]").click()')
    browser.wait('location.pathname === "/inscription" && !!document.querySelector("#registration_form")')
    # No submission: account creation and email verification are covered by isolated PHP tests.
    browser.navigate('/rendez-vous/types')
    assert browser.evaluate('document.documentElement.scrollWidth <= 390'), 'Mobile overflow on catalogue'
    browser.screenshot('catalogue-mobile.png')
    assert browser.blocked_writes == 0, 'An unexpected write was attempted (and blocked)'
    print(json.dumps({'detail': details, 'mobile': 'passed', 'catalogue_ajax_keyboard': 'passed', 'modified_links': 'passed',
        'anonymous_booking_to_login_to_registration': 'passed', 'write_requests': 0}, ensure_ascii=True))
