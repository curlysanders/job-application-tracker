import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list', 'template'];

    add(event) {
        event.preventDefault();
        const index = this.listTarget.children.length;
        this.listTarget.insertAdjacentHTML('beforeend', this.templateTarget.innerHTML.replaceAll('__name__', index));
    }

    remove(event) {
        event.preventDefault();
        event.currentTarget.closest('[data-source-url-collection-row]').remove();
    }
}
