import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modalOpener', 'token', 'transition', 'note', 'description'];

    open(event) {
        event.preventDefault();

        const form = event.currentTarget;
        this.tokenTarget.value = form.querySelector('input[name="_token"]').value;
        this.transitionTarget.value = form.querySelector('input[name="transition"]').value;
        this.noteTarget.value = '';
        this.descriptionTarget.textContent = `Change status to ${form.dataset.transitionLabel}. You can add a note to this transition.`;
        this.modalOpenerTarget.click();
    }
}
