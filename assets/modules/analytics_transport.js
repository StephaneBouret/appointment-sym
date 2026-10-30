// Installed only inside the disposable Analytics frame, before any Google code.
// This is a lifecycle guard for the known tag, not a sandbox for hostile scripts.
export function installTransportGuard(permitted, realm = window) {
    // Keep constructors usable even if a caller retains a function after frame destruction.
    const PromiseClass = realm.Promise, AbortException = realm.DOMException;
    const ImageClass = realm.HTMLImageElement;
    let closed = false;
    const allowed = () => {
        if (closed) return false;
        try { if (permitted()) return true; } catch { /* Fail closed, including storage errors. */ }
        closed = true; // This context must never replay its queue after a later agreement.
        return false;
    };
    const close = () => { closed = true; };
    const beacon = realm.Navigator.prototype.sendBeacon;
    realm.Navigator.prototype.sendBeacon = function (...args) {
        return allowed() ? Reflect.apply(beacon, this, args) : false;
    };
    const fetch = realm.fetch;
    realm.fetch = function (...args) {
        return allowed() ? Reflect.apply(fetch, this, args)
            : PromiseClass.reject(new AbortException('Analytics consent withdrawn', 'AbortError'));
    };
    const xhr = realm.XMLHttpRequest.prototype;
    const send = xhr.send, abort = xhr.abort;
    xhr.send = function (...args) {
        if (allowed()) return Reflect.apply(send, this, args);
        Reflect.apply(abort, this, []);
        return undefined;
    };
    const image = realm.HTMLImageElement.prototype;
    const element = realm.Element.prototype;
    const remove = element.removeAttribute;
    const clearImage = target => {
        Reflect.apply(remove, target, ['srcset']);
        Reflect.apply(remove, target, ['src']);
    };
    for (const key of ['src', 'srcset']) {
        const descriptor = Object.getOwnPropertyDescriptor(image, key);
        Object.defineProperty(image, key, { ...descriptor, set(value) {
            if (allowed()) return Reflect.apply(descriptor.set, this, [value]);
            clearImage(this);
        } });
    }
    for (const key of ['setAttribute', 'setAttributeNS']) {
        const original = element[key];
        element[key] = function (...args) {
            const name = args[key === 'setAttributeNS' ? 1 : 0];
            if (this instanceof ImageClass && typeof name === 'string'
                && /^(src|srcset)$/i.test(name) && !allowed()) {
                clearImage(this);
                return undefined;
            }
            return Reflect.apply(original, this, args);
        };
    }
    return close;
}
