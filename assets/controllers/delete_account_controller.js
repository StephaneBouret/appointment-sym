import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["confirm", "button"];

    connect() {
        this.toggle();
    }

    toggle() {
        // checkbox -> active / désactive le bouton
        this.buttonTarget.disabled = !this.confirmTarget.checked;
    }
}
