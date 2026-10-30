"""Explicit real-GA recipe transport. Importing this module never starts a browser."""
import base64
import json
import os
import re
import socket
import struct
import time
from urllib.parse import parse_qs, urlsplit

MEASUREMENT_ID = 'G-7ZGZR4L2F8'
ORIGIN = 'http://localhost:8021'
COLLECT_HOSTS = {'www.google-analytics.com', 'region1.google-analytics.com'}


def parameters(url, body=''):
    common = parse_qs(urlsplit(url).query, keep_blank_values=True)
    return [{**common, **parse_qs(line, keep_blank_values=True)} for line in body.splitlines()] if body else [common]


def allowed_external(url, method, body=''):
    """No wildcard Google host permissions; every destination ID must match."""
    parsed = urlsplit(url)
    if parsed.scheme != 'https' or parsed.port not in (None, 443) or parsed.username or parsed.password:
        return None
    ids = re.findall(r'(?:G|GT|AW|DC)-[A-Z0-9]+', url + body)
    if not ids or any(value != MEASUREMENT_ID for value in ids):
        return None
    query = parse_qs(parsed.query)
    if parsed.hostname == 'www.googletagmanager.com' and parsed.path == '/gtag/js' and method == 'GET':
        return 'library' if query.get('id') == [MEASUREMENT_ID] else None
    if parsed.hostname in COLLECT_HOSTS and parsed.path == '/g/collect' and method in ('GET', 'POST'):
        # Query and each body line are checked independently (no override can conceal an ID).
        for part in [parsed.query, *body.splitlines()]:
            if 'tid' in parse_qs(part) and parse_qs(part)['tid'] != [MEASUREMENT_ID]:
                return None
        return 'collect' if all(p.get('tid') == [MEASUREMENT_ID] for p in parameters(url, body)) else None
    return None


# Observe queued commands without adding commands or altering consent/configuration.
OBSERVER = r"""
(() => {
  const seen = new WeakSet();
  const notify = data => { try { window.recipeObserve(JSON.stringify({time:(performance.timeOrigin+performance.now())/1000,...data})); } catch {} };
  const decorateRecipeCommand = item => {}; // Optional test-only decorator; ordinary recipes leave commands unchanged.
  // Observe native calls BEFORE the application wraps transports. Never gate or delay them.
  // Report after delegation, preserving the timestamp just before entering the native API.
  const address = value => typeof value === 'string' ? value : value instanceof Request ? value.url : '';
  const nativeCounts = {};
  const observeNative = (transport, fn, receiver, args, url) => {
    const time=(performance.timeOrigin+performance.now())/1000;
    nativeCounts[transport]=(nativeCounts[transport]||0)+1;
    try { return Reflect.apply(fn,receiver,args); }
    finally { notify({kind:'native-transport',transport,time,url,
      ...(transport==='fetch' ? {keepalive:args[1]?.keepalive===true || (args[0] instanceof Request && args[0].keepalive)} : {})}); }
  };
  const beacon=Navigator.prototype.sendBeacon;
  Navigator.prototype.sendBeacon=function(...args) { return observeNative('beacon',beacon,this,args,address(args[0])); };
  const fetch=window.fetch;
  window.fetch=function(...args) { return observeNative('fetch',fetch,this,args,address(args[0])); };
  const urls=new WeakMap(), open=XMLHttpRequest.prototype.open, send=XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open=function(...args) { const result=Reflect.apply(open,this,args); urls.set(this,address(args[1])); return result; };
  XMLHttpRequest.prototype.send=function(...args) { return observeNative('xhr',send,this,args,urls.get(this)||''); };
  const src=Object.getOwnPropertyDescriptor(HTMLImageElement.prototype,'src');
  Object.defineProperty(HTMLImageElement.prototype,'src',{...src,set(value) {
    return observeNative('image',src.set,this,[value],address(value));
  }});
  // Observe calls into a subsequently installed application guard as well as delegation.
  // No consent read or transport decision is performed by this observer.
  for (const [target,key,transport] of [[window,'fetch','fetch'],[Navigator.prototype,'sendBeacon','beacon'],[XMLHttpRequest.prototype,'send','xhr']]) {
    let current=target[key];
    Object.defineProperty(target,key,{configurable:true,get:()=>current,set(fn) {
      current=function(...args) {
        const time=(performance.timeOrigin+performance.now())/1000, before=nativeCounts[transport]||0;
        try { return Reflect.apply(fn,this,args); }
        finally { notify({kind:'guarded-transport-attempt',transport,time,
          url:transport==='xhr' ? urls.get(this)||'' : address(args[0]),
          delegated:(nativeCounts[transport]||0)>before}); }
      };
    }});
  }
  const setItem = Storage.prototype.setItem;
  Storage.prototype.setItem = function(key,value) {
    const result=Reflect.apply(setItem,this,[key,value]);
    if (key==='audience-consent') {
      try { notify({kind:'consent-written',choice:JSON.parse(value).choice}); } catch {}
    }
    return result;
  };
  let layer;
  Object.defineProperty(window, 'dataLayer', {configurable:true, get:()=>layer, set(value) {
    layer=value;
    if (!Array.isArray(value)) return;
    const wrap = fn => function(...items) {
      for (const item of items) {
        if (item && typeof item === 'object' && !seen.has(item)) {
          seen.add(item);
          decorateRecipeCommand(item);
          const command=Array.from(item);
          if (['consent','set','js','config','event'].includes(command[0])) notify({kind:'queue',command});
        }
      }
      return Reflect.apply(fn,this,items);
    };
    let push=wrap(value.push);
    Object.defineProperty(value,'push',{configurable:true,get:()=>push,set(fn){push=wrap(fn);}});
  }});
  addEventListener('pageshow', event => notify({kind:'pageshow',persisted:event.persisted,path:location.pathname}));
  addEventListener('click', event => {
    if (event.target.closest?.('[data-analytics-choice="refused"]')) notify({kind:'withdrawal'});
  },true);
})();
"""


class Driver:
    def __init__(self, websocket_url, observer_script=OBSERVER):
        self.observer_script = observer_script
        url = urlsplit(websocket_url)
        self.sock = socket.create_connection((url.hostname, url.port), timeout=25)
        key = base64.b64encode(os.urandom(16)).decode()
        self.sock.sendall((f'GET {url.path} HTTP/1.1\r\nHost: {url.netloc}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n').encode())
        response = b''
        while not response.endswith(b'\r\n\r\n'):
            response += self.sock.recv(1)
        assert b'101' in response.split(b'\r\n', 1)[0]
        self.seq = 0
        self.pending = {}
        self.requests = {}
        self.observations = []
        self.errors = []
        self.phase = 'setup'
        self.tag_mode = 'normal'
        self.held = []
        self.sessions = []

    def protect(self, session):
        for method in ['Page.enable','Runtime.enable','Network.enable']:
            self.call(method, session=session)
        self.call('Runtime.addBinding', {'name':'recipeObserve'}, session)
        self.call('Page.addScriptToEvaluateOnNewDocument', {'source':self.observer_script}, session)
        # A single Request pattern: overlapping Response patterns can bypass Request interception.
        # Opt into Response interception only after the request passes the destination guard.
        self.call('Fetch.enable', {'patterns':[{'urlPattern':'*','requestStage':'Request'}]}, session)
        self.call('Target.setAutoAttach', {'autoAttach':True,'waitForDebuggerOnStart':True,'flatten':True}, session)

    def read(self, size):
        data = b''
        while len(data) < size:
            chunk = self.sock.recv(size - len(data))
            if not chunk:
                raise RuntimeError('CDP disconnected')
            data += chunk
        return data

    def send(self, method, params=None, session=None):
        self.seq += 1
        message = {'id':self.seq, 'method':method, 'params':params or {}}
        if session:
            message['sessionId'] = session
        data = json.dumps(message).encode()
        mask = os.urandom(4)
        header = bytes([0x81, 0x80 | len(data)]) if len(data) < 126 else bytes([0x81, 0xFE]) + struct.pack('!H', len(data))
        self.sock.sendall(header + mask + bytes(c ^ mask[i % 4] for i, c in enumerate(data)))
        return self.seq

    def call(self, method, params=None, session=None):
        seq = self.send(method, params, session)
        while seq not in self.pending:
            _, second = self.read(2)
            length = second & 127
            if length == 126:
                length = struct.unpack('!H', self.read(2))[0]
            elif length == 127:
                length = struct.unpack('!Q', self.read(8))[0]
            message = json.loads(self.read(length))
            if 'id' in message:
                self.pending[message['id']] = message
            else:
                self.event(message)
        result = self.pending.pop(seq)
        if 'error' in result:
            raise RuntimeError(f'{method}: {result["error"]}')
        return result.get('result', {})

    def event(self, message):
        name, p, session = message['method'], message.get('params', {}), message.get('sessionId')
        key = (session, p.get('requestId'))
        if name == 'Target.attachedToTarget':
            child, info = p['sessionId'], p['targetInfo']
            if info['type'] == 'page' and not p.get('waitingForDebugger'):
                return # Our explicit attachment to an about:blank tab; protected by tab().
            if info['type'] == 'iframe':
                self.protect(child)
                self.call('Runtime.runIfWaitingForDebugger', session=child)
            else:
                self.observations.append({'kind':'blocked-target','type':info['type'],'url':info.get('url'),'phase':self.phase})
                self.send('Target.closeTarget', {'targetId':info['targetId']})
        elif name == 'Network.requestWillBeSent':
            req = p['request']
            if urlsplit(req['url']).scheme in ('http','https') and not req['url'].startswith(ORIGIN + '/'):
                existing = self.requests.setdefault(key, {'kind':None, 'decision':'pending', 'status':None})
                existing.update({'url':req['url'], 'method':req['method'], 'body':req.get('postData',existing.get('body','')),
                    'time':p.get('wallTime', time.time()), 'phase':self.phase, 'session':session,
                    'time_source':'Network.requestWillBeSent',
                    'network_id':p['requestId'],
                    'referer_header':next((v for k,v in req.get('headers',{}).items() if k.lower() == 'referer'),'')})
        elif name == 'Fetch.requestPaused':
            req, fetch_id = p['request'], p['requestId']
            url, method, body = req['url'], req['method'], req.get('postData','')
            # Unload/beacon requests may lack networkId. Never merge unrelated requests under None.
            network_key = (session, p.get('networkId') or 'fetch:'+fetch_id)
            record = self.requests.get(network_key)
            if record is None and not url.startswith(ORIGIN + '/'):
                record = self.requests.setdefault(network_key, {'url':url,'method':method,'body':body,
                    'time':time.time(),'time_source':'Fetch.requestPaused','phase':self.phase,'session':session,'kind':None,
                    'decision':'pending','status':None,'referer_header':''})
            if 'responseStatusCode' in p:
                if record is not None:
                    record['response_intercept_status'] = p['responseStatusCode']
                if self.tag_mode == 'slow' and allowed_external(url, method, body) == 'library':
                    self.held.append((fetch_id, session))
                    return
                self.send('Fetch.continueResponse', {'requestId':fetch_id}, session)
                return
            if url.startswith(ORIGIN + '/') and method in ('GET','HEAD'):
                self.send('Fetch.continueRequest', {'requestId':fetch_id}, session)
                return
            # Existing Maps dependency is unrelated to this recipe; keep the same local double.
            if urlsplit(url).hostname == 'unpkg.com' and urlsplit(url).path.startswith('/@googlemaps/'):
                source = 'export const APILoader={importLibrary(){throw Error("Maps disabled in recipe");}};'
                self.send('Fetch.fulfillRequest', {'requestId':fetch_id,'responseCode':200,
                    'responseHeaders':[{'name':'Content-Type','value':'application/javascript'},{'name':'Access-Control-Allow-Origin','value':'*'}],
                    'body':base64.b64encode(source.encode()).decode()}, session)
                if record is not None:
                    record['decision'] = 'local-double'
                return
            kind = allowed_external(url, method, body)
            readable_body = not req.get('hasPostData') or 'postData' in req
            allow = kind is not None and readable_body and not (kind == 'library' and self.tag_mode == 'fail')
            if record is not None:
                record.update(kind=kind, decision='continue' if allow else 'block', body=body,
                    fetch_id=fetch_id, fetch_time=time.time(), network_id=p.get('networkId'))
            self.send('Fetch.continueRequest' if allow else 'Fetch.failRequest',
                {'requestId':fetch_id, 'interceptResponse':kind=='library'} if allow else {'requestId':fetch_id,'errorReason':'BlockedByClient'}, session)
        elif name == 'Network.responseReceived' and key in self.requests:
            self.requests[key]['status'] = p['response']['status']
            self.requests[key]['response_time'] = time.time()
            self.requests[key]['protocol'] = p['response'].get('protocol')
            self.requests[key]['fromDiskCache'] = p['response'].get('fromDiskCache',False)
        elif name == 'Network.requestServedFromCache' and key in self.requests:
            record = self.requests[key]
            record['fromMemoryCache'] = True
            if record['decision']=='pending':
                record.update(kind=allowed_external(record['url'],record['method'],record['body']), decision='cached')
        elif name == 'Network.loadingFailed' and key in self.requests:
            self.requests[key]['error'] = p.get('errorText')
            self.requests[key]['failure_time'] = time.time()
        elif name == 'Runtime.bindingCalled' and p['name'] == 'recipeObserve':
            self.observations.append({**json.loads(p['payload']), 'phase':self.phase, 'session':session})
        elif name == 'Runtime.exceptionThrown':
            self.errors.append({'phase':self.phase,'detail':p['exceptionDetails'].get('text'),
                'description':p['exceptionDetails'].get('exception',{}).get('description')})
        elif name == 'Page.backForwardCacheNotUsed':
            self.observations.append({'kind':'bfcache-not-used','phase':self.phase,
                'reasons':p.get('notRestoredExplanations',[])})

    def tab(self):
        target = self.call('Target.createTarget', {'url':'about:blank'})['targetId']
        session = self.call('Target.attachToTarget', {'targetId':target,'flatten':True})['sessionId']
        self.sessions.append(session)
        self.protect(session)
        return session

    def evaluate(self, session, expression):
        result = self.call('Runtime.evaluate', {'expression':expression,'returnByValue':True,'awaitPromise':True}, session)
        if 'exceptionDetails' in result:
            raise RuntimeError(result['exceptionDetails'].get('text','JavaScript error'))
        return result.get('result',{}).get('value')

    def pump(self, seconds=1):
        end = time.monotonic() + seconds
        while time.monotonic() < end:
            self.call('Browser.getVersion')
            time.sleep(.05)
        self.call('Browser.getVersion')

    def wait(self, session, expression, seconds=15):
        end = time.monotonic() + seconds
        while time.monotonic() < end:
            if self.evaluate(session, expression):
                return True
            self.pump(.15)
        return False

    def go(self, session, path):
        assert path.startswith('/') and not path.startswith('//')
        self.call('Page.bringToFront', session=session)
        self.call('Page.navigate', {'url':ORIGIN+path}, session)
        if not self.wait(session, '!!document.querySelector("[data-analytics-config]") && document.readyState !== "loading"'):
            raise RuntimeError('Local page did not load')
        self.pump(1)

    def click(self, session, selector):
        self.evaluate(session, f'document.querySelector({json.dumps(selector)}).click()')
        self.pump(.6)

    def cookies(self, session):
        return [c for c in self.call('Network.getAllCookies', session=session)['cookies'] if c['name'].startswith(('audience_','_ga','_gid','_gat','_gcl'))]

    def collect(self, phase=None):
        return [r for r in self.requests.values() if r['kind']=='collect' and r['decision']=='continue'
            and (phase is None or r['phase']==phase)]

    def events(self, phase=None):
        return [{'params':p,'request':r} for r in self.collect(phase) for p in parameters(r['url'],r['body']) if 'en' in p]
