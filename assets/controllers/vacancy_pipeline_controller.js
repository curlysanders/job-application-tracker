import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['results'];
    static values = { url: String };

    connect() {
        this.onPopState = this.onPopState.bind(this);
        window.addEventListener('popstate', this.onPopState);
    }

    disconnect() {
        window.removeEventListener('popstate', this.onPopState);
    }

    filter(event) {
        event.preventDefault();
        this.replaceResults(event.currentTarget.href, event.params.status);
    }

    clear(event) {
        event.preventDefault();
        this.replaceResults(event.currentTarget.href, null);
    }

    onPopState() {
        const url = new URL(window.location.href);
        this.replaceResults(url.href, url.searchParams.get('status'), false);
    }

    async replaceResults(fallbackUrl, status, pushState = true) {
        const fragmentUrl = new URL(this.urlValue, window.location.origin);
        if (status) {
            fragmentUrl.searchParams.set('status', status);
        }

        try {
            const response = await fetch(fragmentUrl, { headers: { Accept: 'text/html' } });
            if (!response.ok) {
                throw new Error(`Pipeline fragment request failed with ${response.status}`);
            }

            this.resultsTarget.innerHTML = await response.text();
            this.updateSelectedStatus(status);
            if (pushState) {
                window.history.pushState({}, '', fallbackUrl);
            }
        } catch {
            window.location.assign(fallbackUrl);
        }
    }

    updateSelectedStatus(status) {
        this.element.querySelectorAll('[data-vacancy-pipeline-status-param]').forEach((link) => {
            const selected = link.dataset.vacancyPipelineStatusParam === status;
            link.classList.toggle('vacancy-chevron-selected', selected);
            link.toggleAttribute('aria-current', selected);
        });

        const clearLink = this.element.querySelector('[data-action="vacancy-pipeline#clear"]');
        if (clearLink) {
            clearLink.hidden = !status;
        }
    }
}
