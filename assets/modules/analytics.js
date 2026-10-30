import { CONSENT_KEY, CONSENT_VERSION, sixMonthsAfter, readConsent, configured, safePage, serviceId, clearCookies } from './analytics_policy.js';

let config;
let frame;
let ready = false;
let body;
let pageSent = false;
let servicesSeen = new WeakSet();
let navigating = false;
let hadValidConsent = false;
let agreementDate;
let focusReturn;
const panel = () => document.getElementById('analytics-consent');
const consent = () => readConsent();
const consentAllowsAnalytics = (candidate = config) => configured(candidate) && consent()?.choice === 'accepted';
const permitted = () => consentAllowsAnalytics() && config.page && !navigating;
const visible = element => element?.isConnected && element.getClientRects().length > 0
    && getComputedStyle(element).visibility !== 'hidden' && document.visibilityState === 'visible';

function stop(erase = true) {
    ready = false;
    if (frame) {
        // Synchronous opt-out before destroying the context (including an in-flight tag).
        try {
            if (erase) frame.contentWindow.stopAudienceTransport?.();
            frame.contentWindow[`ga-disable-${config.id}`] = true;
            if (frame.contentWindow.dataLayer) frame.contentWindow.dataLayer.length = 0;
        } catch { /* A failed/blocked frame is already unable to collect. */ }
        frame.remove();
        frame = null;
    }
    if (erase) clearCookies(config);
}

function openPanel(manual = false) {
    if (!panel()) return;
    panel().hidden = false;
    document.querySelector('[data-analytics-manage]')?.setAttribute('aria-expanded', 'true');
    if (manual) {
        focusReturn = document.activeElement;
        document.getElementById('analytics-consent-title').focus();
    }
}
function closePanel() {
    if (panel()) panel().hidden = true;
    document.querySelector('[data-analytics-manage]')?.setAttribute('aria-expanded', 'false');
    if (panel()?.contains(document.activeElement)) (focusReturn?.isConnected ? focusReturn : document.querySelector('[data-analytics-manage]'))?.focus();
    focusReturn = null;
}

function updatePanel() {
    const active = configured(config);
    document.querySelectorAll('[data-analytics-choice]').forEach(button => { button.hidden = !active; });
    const information = document.querySelector('[data-analytics-information]');
    if (!active && information) information.textContent = 'La mesure d’audience Google Analytics est désactivée sur ce site. Aucun consentement Analytics n’est demandé.';
    const status = document.querySelector('[data-analytics-status]');
    if (status) status.textContent = !active ? '' : consent()?.choice === 'accepted' ? 'Choix actuel : accepté.'
        : consent()?.choice === 'refused' ? 'Choix actuel : refusé.' : 'Aucun accord en cours.';
}

function start() {
    if (!permitted() || frame) return;
    frame = document.createElement('iframe');
    frame.hidden = true;
    frame.tabIndex = -1;
    frame.setAttribute('aria-hidden', 'true');
    frame.title = 'Mesure d’audience';
    frame.referrerPolicy = 'no-referrer';
    frame.src = config.framePath;
    // Outside the Turbo-replaced body: no iframe or third-party script in snapshots.
    document.documentElement.append(frame);
}

function emit(name, id, context) {
    if (!permitted() || !ready || !frame || !visible(document.body)) return false;
    frame.contentWindow.postMessage({ type: 'audience:event', name, path: config.page.path, id, context }, location.origin);
    return true;
}

function displayed() {
    const currentBody = document.body;
    // Two frames let insertion paint; discarded/hidden/preview content is never counted.
    requestAnimationFrame(() => requestAnimationFrame(() => {
        if (currentBody !== body || currentBody !== document.body || document.documentElement.hasAttribute('data-turbo-preview') || !visible(currentBody)) return;
        if (!pageSent && emit('page_view')) pageSent = true;
        document.querySelectorAll('[data-analytics-service]').forEach(element => {
            if (!servicesSeen.has(element) && visible(element) && serviceId(element.dataset.analyticsService)) {
                const context = element.dataset.displayContext === 'catalogue_ajax' ? 'catalogue_ajax' : 'service_page';
                if (emit('view_service', element.dataset.analyticsService, context)) servicesSeen.add(element);
            }
        });
    }));
}

function sync() {
    if (document.documentElement.hasAttribute('data-turbo-preview')) return;
    const node = document.querySelector('[data-analytics-config]');
    if (!node) { stop(); return; }
    try { config = JSON.parse(node.dataset.analyticsConfig); } catch { stop(); return; }
    config.page = config.page && safePage(config, config.page.path);
    if (body !== document.body) {
        body = document.body;
        pageSent = false;
        servicesSeen = new WeakSet();
        navigating = false;
        closePanel();
        if (configured(config) && !consent()) openPanel();
    }
    updatePanel();
    hadValidConsent = consentAllowsAnalytics();
    const nextAgreement = hadValidConsent ? consent()?.date : undefined;
    if (nextAgreement !== agreementDate) {
        stop(!hadValidConsent);
        pageSent = false;
        servicesSeen = new WeakSet();
        agreementDate = nextAgreement;
    }
    if (!hadValidConsent) {
        stop(); // Disabled integration or missing/refused/invalid/expired consent: erase cookies.
    } else if (!config.page || navigating) {
        stop(false); // An excluded page stops collection, not the visitor's valid consent.
    } else {
        start();
        displayed();
    }
}

window.addEventListener('message', event => {
    if (!frame || event.source !== frame.contentWindow || event.origin !== location.origin || !permitted()) return;
    if (event.data?.type === 'audience:frame') {
        frame.contentWindow.postMessage({ type: 'audience:init', path: config.page.path }, location.origin);
    } else if (event.data?.type === 'audience:ready') {
        ready = true;
        displayed(); // Only the currently displayed page; no interaction backlog.
    }
});

document.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    if (event.target.closest('[data-analytics-manage]')) { updatePanel(); openPanel(true); return; }
    if (event.target.closest('[data-analytics-close]')) { closePanel(); return; }
    const choice = event.target.closest('[data-analytics-choice]')?.dataset.analyticsChoice;
    if (configured(config) && ['accepted', 'refused'].includes(choice)) {
        const date = Date.now();
        try {
            localStorage.setItem(CONSENT_KEY, JSON.stringify({ version: CONSENT_VERSION, choice, date, expires: sixMonthsAfter(date) }));
        } catch {
            stop();
            document.querySelector('[data-analytics-status]').textContent = 'Le choix ne peut pas être mémorisé. La mesure d’audience reste désactivée.';
            return;
        }
        if (choice === 'refused') stop();
        closePanel();
        sync();
        return;
    }
    const link = event.target.closest('[data-analytics-event]');
    if (!link || !serviceId(link.dataset.serviceId)) return;
    const name = link.dataset.analyticsEvent;
    if (name === 'discover_session_click' && link.dataset.displayContext === 'home_featured') {
        emit(name, link.dataset.serviceId, 'home_featured');
    } else if (name === 'reserve_click') {
        emit(name, link.dataset.serviceId, link.closest('[data-analytics-service]')?.dataset.displayContext === 'catalogue_ajax' ? 'catalogue_ajax' : 'service_page');
    }
    // No preventDefault, timeout, event_callback or dependency on Google for links.
}, true);
document.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel()?.hidden) closePanel(); });

document.addEventListener('turbo:before-visit', event => {
    const target = new URL(event.detail.url, location.origin);
    if (!config?.page || target.origin !== location.origin || !safePage(config, target.pathname)) {
        event.preventDefault();
        navigating = true;
        stop(!consentAllowsAnalytics());
        location.assign(target.href);
    }
});
document.addEventListener('turbo:before-render', event => {
    const node = event.detail.newBody.querySelector('[data-analytics-config]');
    let next;
    try { next = JSON.parse(node?.dataset.analyticsConfig || '{}'); } catch { /* Fail closed. */ }
    if (!next?.page || !configured(next)) stop(!consentAllowsAnalytics(next ?? null));
    navigating = true;
});
document.addEventListener('turbo:load', sync);
document.addEventListener('DOMContentLoaded', sync);
document.addEventListener('analytics:service-displayed', displayed);
document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') sync(); });
window.addEventListener('storage', event => {
    if (event.key === CONSENT_KEY || event.key === null) {
        sync();
        if (configured(config) && !consent()) openPanel();
    }
});
window.addEventListener('pagehide', () => { navigating = true; stop(!consentAllowsAnalytics()); });
window.addEventListener('pageshow', event => {
    if (event.persisted) { body = null; navigating = false; sync(); }
});
setInterval(() => {
    // Consent can expire on a private page where cookies remain but no frame exists.
    if (config && hadValidConsent !== consentAllowsAnalytics()) {
        sync();
        if (!consent()) openPanel();
    } else if (frame && !permitted()) {
        stop(!consentAllowsAnalytics());
    }
}, 1000);
if (document.readyState !== 'loading') sync();
