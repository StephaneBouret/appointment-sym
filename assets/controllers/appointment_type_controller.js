import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["container"];

    showDetail(event) {
        event.preventDefault();
        const url = event.currentTarget.dataset.url;
        if (!url) return;
        this.fetchIntoContainer(url);
    }

    showForm(event) {
        event.preventDefault();
        const url = event.currentTarget.dataset.url;
        if (!url) return;
        this.fetchIntoContainer(url);
    }

    reloadRecap(event) {
        event.preventDefault();
        this.fetchIntoContainer('/rendez-vous/types');
    }

    // --- utilitaire commun ---
    fetchIntoContainer(url) {
        fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json, text/html"
            }
        })
        .then(async (response) => {
            const ct = (response.headers.get("Content-Type") || "").toLowerCase();

            // JSON : on attend potentiellement { redirect: ... }
            if (ct.includes("application/json")) {
                const payload = await response.json();
                if (payload.redirect) {
                    window.location.href = payload.redirect;
                    return;
                }
            }

            // HTML : fragment à injecter
            const html = await response.text();

            // Sécurité login (comme avant)
            if (html.includes('id="login_form"')) {
                window.location.href = "/login";
                return;
            }

            this.containerTarget.innerHTML = html;
        })
        .catch((e) => console.error("Erreur AJAX RDV:", e));
    }
}
