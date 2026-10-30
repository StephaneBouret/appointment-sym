// Executed with the actual policy/guard source and native spies, never a real collector.
const results = [];
const check = (name, condition) => { if (!condition) throw Error(name); results.push(name); };
function fixture() {
    const calls = [];
    class Element {
        removeAttribute(name) { delete this[name + 'Value']; }
        setAttribute(...args) { calls.push(['attribute', this, args]); }
        setAttributeNS(...args) { calls.push(['attributeNS', this, args]); }
    }
    class Image extends Element {
        set src(value) { calls.push(['image', this, [value]]); this.srcValue = value; }
        get src() { return this.srcValue; }
        set srcset(value) { calls.push(['srcset', this, [value]]); }
        get srcset() { return ''; }
    }
    class Navigator { sendBeacon(...args) { calls.push(['beacon', this, args]); return true; } }
    class XHR {
        send(...args) { calls.push(['xhr', this, args]); }
        abort() { this.aborted = true; }
    }
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    const realm = { Element, HTMLImageElement: Image, Navigator, XMLHttpRequest: XHR, Promise, DOMException,
        fetch(...args) { calls.push(['fetch', this, args]); return promise; } };
    const date = Date.now() - 100;
    let record = { version: 1, choice: 'accepted', date, expires: sixMonthsAfter(date) }, unreadable = false;
    const storage = { getItem() { if (unreadable) throw Error('storage denied'); return JSON.stringify(record); } };
    const close = installTransportGuard(() => readConsent(storage)?.choice === 'accepted', realm);
    return { realm, calls, close, promise, resolve, change(value) { record = value; }, denyStorage() { unreadable = true; } };
}
const f = fixture(), r = f.realm, nav = new r.Navigator(), xhr = new r.XMLHttpRequest(), img = new r.HTMLImageElement();
const body = new Blob(['synthetic']), options = { method: 'POST', body, keepalive: true };
check('beacon return value preserved', nav.sendBeacon('/collect', body) === true);
check('fetch native Promise identity preserved', r.fetch('/collect', options) === f.promise);
check('XHR return value preserved', xhr.send(body) === undefined);
img.src = '/image'; img.srcset = '/image 1x'; img.setAttribute('src', '/image'); img.setAttributeNS(null, 'src', '/image');
check('native receiver and argument identity preserved', f.calls[0][1] === nav && f.calls[0][2][1] === body
    && f.calls[1][1] === r && f.calls[1][2][1] === options && f.calls[2][1] === xhr && f.calls[2][2][0] === body);
check('image property getter preserved', img.src === '/image');
check('all supported paths delegate once when allowed', f.calls.length === 7);
// The same wrapper is called after shared storage changes, without any storage event.
f.change({ choice: 'refused' });
const oldCount = f.calls.length;
check('beacon refuses synchronously', nav.sendBeacon('/collect', body) === false);
try { await r.fetch('/collect', options); throw Error('fetch resolved'); }
catch (e) { check('fetch rejects with AbortError', e.name === 'AbortError'); }
check('XHR aborts and returns undefined', xhr.send(body) === undefined && xhr.aborted);
img.src = '/blocked'; img.srcset = '/blocked 1x'; img.setAttribute('SRC', '/blocked'); img.setAttributeNS(null, 'src', '/blocked');
check('all supported paths block without delegation', f.calls.length === oldCount);
check('denied image assignment clears prior sources', img.src === undefined);
f.resolve('response-to-call-before-withdrawal');
check('response to pre-withdrawal call is distinct from a new emission',
    await f.promise === 'response-to-call-before-withdrawal' && f.calls.length === oldCount);
const date = Date.now() - 1;
f.change({ version: 1, choice: 'accepted', date, expires: sixMonthsAfter(date) });
check('old context stays closed after new agreement', nav.sendBeacon('/old-queue', body) === false && f.calls.length === oldCount);
for (const reason of ['absent', 'refused', 'invalid', 'expired', 'unreadable']) {
    const x = fixture();
    if (reason === 'unreadable') x.denyStorage();
    else x.change(reason === 'absent' ? null : reason === 'expired'
        ? { version: 1, choice: 'accepted', date: 1, expires: sixMonthsAfter(1) }
        : reason === 'refused' ? { version: 1, choice: 'refused', date, expires: sixMonthsAfter(date) } : { version: 99 });
    check(reason + ' storage fails closed', new x.realm.Navigator().sendBeacon('/collect') === false && x.calls.length === 0);
}
const stopped = fixture(); stopped.close();
check('explicit stop blocks even with accepted storage', new stopped.realm.Navigator().sendBeacon('/collect') === false);
const fresh = fixture();
check('new context can send after new agreement', new fresh.realm.Navigator().sendBeacon('/collect') === true);
return results;
