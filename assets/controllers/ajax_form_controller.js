import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    submit(event) {
        console.log('ajax-form SUBMIT intercepted');

        event.preventDefault();
        const form = event.target;
        const url = form.action;
        const data = new FormData(form);

        fetch(url, {
            method: "POST",
            body: data,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                // On accepte JSON (redir) ou HTML (fragment erreurs)
                "Accept": "application/json, text/html"
            }
        })
        .then(async (response) => {
            const ct = (response.headers.get("Content-Type") || "").toLowerCase();

            if (ct.includes("application/json")) {
                const payload = await response.json();
                if (payload.redirect) {
                    window.location.href = payload.redirect;
                    return;
                }
            }

            const html = await response.text();
            const container =
                form.closest('[data-controller="appointment-type"]') || this.element;
            if (container) container.innerHTML = html;
        })
        .catch((e) => console.error("Erreur AJAX formulaire RDV:", e));
    }
}
