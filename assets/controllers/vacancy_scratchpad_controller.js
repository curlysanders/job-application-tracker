import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['notes', 'token', 'status', 'preview'];
    static values = { url: String };

    connect() {
        this.requestSequence = 0;
    }

    disconnect() {
        window.clearTimeout(this.timeout);
        this.abortController?.abort();
    }

    schedule() {
        window.clearTimeout(this.timeout);
        this.timeout = window.setTimeout(() => this.save(), 700);
    }

    format(event) {
        const textarea = this.notesTarget;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.slice(start, end);
        const format = event.currentTarget.dataset.markdownFormat;
        const replacement = this.formattedText(format, selected);

        textarea.setRangeText(replacement, start, end, 'select');
        textarea.focus();
        this.schedule();
    }

    async save() {
        window.clearTimeout(this.timeout);
        const sequence = ++this.requestSequence;
        this.abortController?.abort();
        this.abortController = new AbortController();
        this.statusTarget.textContent = 'Saving…';
        this.statusTarget.className = 'scratchpad-status';

        const payload = new URLSearchParams({ _token: this.tokenTarget.value, notes: this.notesTarget.value });
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: payload,
                signal: this.abortController.signal,
            });
            if (!response.ok) throw new Error(`Scratchpad save failed with ${response.status}`);
            const result = await response.json();
            if (sequence !== this.requestSequence) return;
            this.previewTarget.innerHTML = result.preview;
            this.statusTarget.textContent = 'Saved';
            this.statusTarget.className = 'scratchpad-status scratchpad-status-saved';
        } catch (error) {
            if (error.name === 'AbortError' || sequence !== this.requestSequence) return;
            this.statusTarget.textContent = 'Could not save. Edit the notes to retry.';
            this.statusTarget.className = 'scratchpad-status scratchpad-status-error';
        }
    }

    formattedText(format, selected) {
        const text = selected || 'text';

        switch (format) {
            case 'heading':
                return text.split('\n').map((line) => `# ${line}`).join('\n');
            case 'bold':
                return `**${text}**`;
            case 'italic':
                return `*${text}*`;
            case 'list':
                return text.split('\n').map((line) => `- ${line}`).join('\n');
            case 'link':
                return `[${text}](https://)`;
            case 'code':
                return `\`${text}\``;
            default:
                return text;
        }
    }
}
