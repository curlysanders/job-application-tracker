import { Controller } from '@hotwired/stimulus';

// Keep Flowbite's disclosure state available to keyboard and screen-reader users.
export default class extends Controller {
    connect() {
        this.dropdown = document.getElementById(this.element.getAttribute('aria-controls'));
        if (!this.dropdown) return;
        this.syncExpanded = () => this.element.setAttribute('aria-expanded', String(!this.dropdown.classList.contains('hidden')));
        this.observer = new MutationObserver(this.syncExpanded);
        this.observer.observe(this.dropdown, { attributes: true, attributeFilter: ['class'] });
        this.syncExpanded();
        this.onKeydown = (event) => {
            if (event.key === 'Escape' && !this.dropdown.classList.contains('hidden')) {
                this.element.click();
                this.element.focus();
            }
        };
        document.addEventListener('keydown', this.onKeydown);
    }

    disconnect() {
        this.observer?.disconnect();
        if (this.onKeydown) document.removeEventListener('keydown', this.onKeydown);
    }
}
