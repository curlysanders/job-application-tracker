import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.timer = window.setTimeout(() => window.location.reload(), 5000);
    }

    disconnect() {
        window.clearTimeout(this.timer);
    }
}
