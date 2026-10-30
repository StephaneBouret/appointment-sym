import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["container"];

    disconnect() {
        this.pendingRequest?.abort();
        this.requestVersion = (this.requestVersion || 0) + 1;
    }

    showDetail(event) {
        this.followLink(event);
    }

    showForm(event) {
        // Private forms always get their own document, even for authenticated users.
        // Retained for compatibility with older cached markup.
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        window.location.assign(event.currentTarget.href);
    }

    reloadRecap(event) {
        this.followLink(event);
    }

    followLink(event) {
        // Leave modified clicks and new tabs to the browser, including keyboard use.
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.currentTarget;
        if (link.target && link.target !== '_self') return;
        const url = link.href;
        if (!url) return;
        event.preventDefault();
        this.fetchIntoContainer(url);
    }

    // --- utilitaire commun ---
    fetchIntoContainer(url) {
        this.pendingRequest?.abort();
        this.pendingRequest = new AbortController();
        const version = this.requestVersion = (this.requestVersion || 0) + 1;
        const current = () => version === this.requestVersion && this.element.isConnected;
        fetch(url, {
            signal: this.pendingRequest.signal,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json, text/html"
            }
        })
        .then(async (response) => {
            if (!current()) return;
            // A normal navigation lets Symfony save the protected URL if the session expired.
            if (response.redirected) {
                window.location.assign(url);
                return;
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const ct = (response.headers.get("Content-Type") || "").toLowerCase();

            // JSON : on attend potentiellement { redirect: ... }
            if (ct.includes("application/json")) {
                const payload = await response.json();
                if (!current()) return;
                if (payload.redirect) {
                    window.location.href = payload.redirect;
                    return;
                }
            }

            // HTML : fragment à injecter
            const html = await response.text();
            if (!current()) return;

            // Sécurité login (comme avant)
            if (html.includes('id="login_form"')) {
                window.location.assign(url);
                return;
            }

            this.containerTarget.innerHTML = html;
            this.containerTarget.querySelectorAll('[data-analytics-service]').forEach(element => {
                element.dataset.displayContext = 'catalogue_ajax';
            });
            document.dispatchEvent(new Event('analytics:service-displayed'));
            const heading = this.containerTarget.querySelector('h1, h2, h5');
            if (heading) {
                heading.tabIndex = -1;
                heading.focus();
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError' && current()) window.location.assign(url);
        });
    }
}
