import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["agree", "submit"];

    connect() {
        this.toggle();
    }

    toggle() {
        this.submitTarget.disabled = !this.agreeTarget.checked;
    }
}
