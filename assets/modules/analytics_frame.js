import { configured, readConsent, safePage, serviceId, cookiePrefix, clearCookies } from './analytics_policy.js';
import { installTransportGuard } from './analytics_transport.js';

const config = JSON.parse(document.body.dataset.analyticsConfig);
const agreement = readConsent();
// Re-read shared storage on EVERY transport call, not just on asynchronous storage events.
// Pin the agreement date so a queue from an old agreement cannot survive a rapid reacceptance.
const closeTransport = installTransportGuard(() => {
    const current = readConsent();
    return configured(config) && current?.choice === 'accepted' && agreement?.choice === 'accepted'
        && current.date === agreement.date && current.expires === agreement.expires;
});
window.stopAudienceTransport = closeTransport;
let started = false;
let ready = false;
let stopped = false;
const allowed = () => !stopped && configured(config) && readConsent()?.choice === 'accepted';
function send() { if (allowed()) window.dataLayer.push(arguments); }

// Removing a script tag alone does not cancel its execution. The parent destroys
// this entire browsing context, including pending messages, timers and requests.
function stop(erase = true) {
    if (erase) closeTransport();
    stopped = true;
    ready = false;
    window[`ga-disable-${config.id}`] = true;
    if (window.dataLayer) window.dataLayer.length = 0;
    if (erase) clearCookies(config);
}
window.addEventListener('storage', () => { if (!allowed()) stop(); });
window.addEventListener('pagehide', () => stop(readConsent()?.choice !== 'accepted'));

window.addEventListener('message', event => {
    if (event.source !== parent || event.origin !== location.origin || !allowed()) return;
    const message = event.data;
    const page = safePage(config, message?.path);
    if (!page) return;
    // Never read parent.location, document.referrer, a DOM title, or an arbitrary URL.
    const metadata = { page_location: location.origin + page.path, page_title: page.title, page_referrer: '' };
    if (message.type === 'audience:init' && !started) {
        started = true;
        window.dataLayer = [];
        window[`ga-disable-${config.id}`] = false;
        send('consent', 'default', {
            analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied',
        });
        send('set', { ...metadata, allow_google_signals: false, allow_ad_personalization_signals: false,
            ads_data_redaction: true, url_passthrough: false });
        send('js', new Date());
        send('config', config.id, {
            ...metadata, send_page_view: false, ignore_referrer: true,
            allow_google_signals: false, allow_ad_personalization_signals: false,
            cookie_prefix: cookiePrefix(config), cookie_domain: 'none', cookie_path: '/',
            cookie_expires: Math.max(1, Math.floor((readConsent().expires - Date.now()) / 1000)),
            cookie_update: false, cookie_flags: 'SameSite=Lax;Secure',
        });
        const script = document.createElement('script');
        script.async = true;
        script.referrerPolicy = 'no-referrer';
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(config.id)}`;
        script.onload = () => {
            if (!allowed()) { stop(); return; }
            ready = true;
            parent.postMessage({ type: 'audience:ready' }, location.origin);
        };
        script.onerror = stop;
        document.head.append(script);
        // Revalidate expiration while a visitor leaves a page open.
        setInterval(() => { if (!allowed()) stop(); }, 1000);
    } else if (message.type === 'audience:event' && ready) {
        const { name, id, context } = message;
        const contexts = { discover_session_click: ['home_featured'], view_service: ['service_page', 'catalogue_ajax'],
            reserve_click: ['service_page', 'catalogue_ajax'] };
        if (name !== 'page_view' && (!Object.hasOwn(contexts, name) || !contexts[name].includes(context) || !serviceId(id))) return;
        send('set', metadata); // Also sanitize metadata for any later Google-generated event.
        send('event', name, { ...metadata, send_to: config.id,
            ...(name === 'page_view' ? {} : { service_id: id, display_context: context }) });
    }
});

if (parent !== window && allowed()) parent.postMessage({ type: 'audience:frame' }, location.origin);
