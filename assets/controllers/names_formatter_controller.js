import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input"];

    connect() {
        // Normalise au blur
        this.inputTargets.forEach((el) => {
            el.addEventListener("blur", () => this.formatOne(el));
        });

        // Sécurité : normaliser tous les champs avant submit
        this.element
            ?.closest("form")
            ?.addEventListener("submit", () => this.formatAll());
    }

    formatAll() {
        this.inputTargets.forEach((el) => this.formatOne(el));
    }

    formatOne(el) {
        const raw = (el.value || "").trim();
        if (!raw) return;

        el.value = this.normalizeNames(raw);
    }

    normalizeNames(str) {
        // 1) on enlève , . ; :
        let s = str.replace(/[.,;:]+/g, " ");

        // 2) espaces propres
        s = s.replace(/\s+/g, " ").trim();

        // 3) On met en capitale la 1ère lettre de chaque "mot",
        //    en respectant les prénoms composés (Jean-Pierre).
        //    on ne casse pas les apostrophes (D'Angelo => D'Angelo)
        const words = s.split(" ");
        const cap = words
            .map((w) =>
                w
                    .split("-")
                    .map((part) =>
                        this.ucfirst(part.toLocaleLowerCase("fr-FR"))
                    )
                    .join("-")
            )
            .join(" ");

        return cap;
    }

    ucfirst(part) {
        if (!part) return;
        // Garde les apostrophes intérieures
        return part.charAt(0).toLocaleUpperCase("fr-FR") + part.slice(1);
    }
}
