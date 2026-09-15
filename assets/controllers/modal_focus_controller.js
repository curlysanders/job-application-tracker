import { Controller } from '@hotwired/stimulus';

// Flowbite controls visibility; this controller keeps keyboard focus in the dialog.
export default class extends Controller {
    connect() {
        this.open = false;
        this.observer = new MutationObserver(() => this.syncFocus());
        this.observer.observe(this.element, { attributes: true, attributeFilter: ['aria-hidden'] });
        this.onKeydown = (event) => this.trapFocus(event);
        this.element.addEventListener('keydown', this.onKeydown);
    }

    disconnect() {
        this.observer.disconnect();
        this.element.removeEventListener('keydown', this.onKeydown);
    }

    syncFocus() {
        const open = this.element.getAttribute('aria-hidden') !== 'true';
        if (open === this.open) return;
        this.open = open;
        if (open) {
            this.trigger = document.activeElement;
            (this.focusableElements()[0] ?? this.element).focus();
        } else if (this.trigger?.isConnected) {
            this.trigger.focus();
        }
    }

    focusableElements() {
        return [...this.element.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
            .filter((element) => element.getClientRects().length > 0);
    }

    trapFocus(event) {
        if (event.key !== 'Tab' || !this.open) return;
        const elements = this.focusableElements();
        const first = elements[0] ?? this.element;
        const last = elements.at(-1) ?? this.element;
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
}
