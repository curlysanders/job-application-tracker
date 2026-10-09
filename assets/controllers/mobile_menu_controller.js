import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.sidebar = document.getElementById(this.element.getAttribute('aria-controls'));
        if (!this.sidebar) return;

        this.onDocumentClick = (event) => {
            if (
                !window.matchMedia('(max-width: 767px)').matches
                || this.sidebar.classList.contains('hidden')
                || this.sidebar.contains(event.target)
                || this.element.contains(event.target)
            ) {
                return;
            }

            this.element.click();
        };
        document.addEventListener('click', this.onDocumentClick);
    }

    disconnect() {
        if (this.onDocumentClick) document.removeEventListener('click', this.onDocumentClick);
    }
}
