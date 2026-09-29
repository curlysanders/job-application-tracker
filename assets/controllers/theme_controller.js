import { Controller } from '@hotwired/stimulus';

const storageKey = 'job-application-tracker-theme';

export default class extends Controller {
    connect() {
        this.updateLabel();
    }

    toggle() {
        document.documentElement.dataset.theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(storageKey, document.documentElement.dataset.theme);
        this.updateLabel();
    }

    updateLabel() {
        const dark = document.documentElement.dataset.theme === 'dark';
        this.element.setAttribute('aria-label', dark ? 'Use light colour theme' : 'Use dark colour theme');
        this.element.setAttribute('title', dark ? 'Use light colour theme' : 'Use dark colour theme');
        this.element.setAttribute('aria-pressed', String(dark));
    }
}
