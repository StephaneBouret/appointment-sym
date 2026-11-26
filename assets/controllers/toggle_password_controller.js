import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'button', 'showLabel', 'hideLabel'];

    connect() {
        this.hide();
        this.buttonTarget.hidden = false;
    }

    toggle() {
        this.setVisible(this.inputTarget.type === 'password');
    }

    hide() {
        this.setVisible(false);
    }

    setVisible(visible) {
        this.inputTarget.type = visible ? 'text' : 'password';
        this.showLabelTarget.hidden = visible;
        this.hideLabelTarget.hidden = !visible;
    }
}
