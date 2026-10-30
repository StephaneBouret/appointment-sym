// Shared by the coordinator and the disposable Google-tag browsing context.
export const CONSENT_KEY = 'audience-consent';
export const CONSENT_VERSION = 1;
export const serviceId = value => typeof value === 'string' && /^[1-9][0-9]{0,9}$/.test(value);

export function sixMonthsAfter(timestamp) {
    const date = new Date(timestamp);
    const day = date.getUTCDate();
    date.setUTCDate(1);
    date.setUTCMonth(date.getUTCMonth() + 6);
    const lastDay = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
    date.setUTCDate(Math.min(day, lastDay));
    return date.getTime();
}

export function readConsent(storage, now = Date.now()) {
    try {
        const value = JSON.parse((storage ?? window.localStorage).getItem(CONSENT_KEY));
        if (value?.version !== CONSENT_VERSION || !['accepted', 'refused'].includes(value.choice)
            || !Number.isSafeInteger(value.date) || value.date > now || value.date <= 0
            || value.expires !== sixMonthsAfter(value.date) || value.expires <= now) return null;
        return value;
    } catch { return null; }
}

export function configured(config) {
    return config?.enabled === true && /^G-[A-Z0-9]{10}$/.test(config.id || '');
}

export function safePage(config, path) {
    if (typeof path !== 'string' || !path.startsWith('/') || path.startsWith('//') || /[?#\\]/.test(path)) return null;
    if (Object.hasOwn(config.pages, path)) return { path, title: config.pages[path] };
    const [prefix, suffix] = config.detailTemplate.split('9876543210');
    if (suffix === undefined || !path.startsWith(prefix) || !path.endsWith(suffix)) return null;
    const id = path.slice(prefix.length, suffix ? -suffix.length : undefined);
    return serviceId(id) ? { path: `${prefix}${id}${suffix}`, title: 'Fiche prestation' } : null;
}

export function cookiePrefix(config) { return `audience_${config.id.replaceAll('-', '_')}`; }

export function clearCookies(config) {
    const names = new Set(document.cookie.split(';').map(c => c.trim().split('=')[0])
        .filter(name => /^audience_G_[A-Z0-9]+_ga(?:_|$)/.test(name)));
    // Include known names even when the current path cannot read the cookie.
    if (config?.id) {
        const prefix = `${cookiePrefix(config)}_ga`;
        names.add(prefix);
        names.add(`${prefix}_${config.id.slice(2)}`);
    }
    const domains = ['', ...location.hostname.split('.').map((_, i, parts) => parts.slice(i).join('.'))];
    const paths = new Set(['/']);
    for (const path of [location.pathname, config?.framePath || '/audience/frame']) {
        const parts = path.split('/');
        for (let i = 1; i <= parts.length; i++) {
            const candidate = parts.slice(0, i).join('/') || '/';
            paths.add(candidate);
            paths.add(candidate.endsWith('/') ? candidate : `${candidate}/`);
        }
    }
    for (const name of names) for (const path of paths) for (const domain of domains) {
        const base = `${name}=; Max-Age=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${path}; SameSite=Lax`;
        document.cookie = base + (domain ? `; domain=${domain}` : '');
        if (domain) document.cookie = `${base}; domain=.${domain}`;
    }
}
