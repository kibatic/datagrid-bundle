import { Controller } from '@hotwired/stimulus';
import {debounce} from "lodash/function";

export default class extends Controller {
    static values = {
        events: { type: Array, default: ['change', 'input'] },
        turboFrameIds: { type: Array, default: [] },
    };

    lastUsedUrl = null;

    connect() {
        this.handleChange = this.handleChange.bind(this);
        this.handleChangeThrottled = debounce(this.handleChange, 250);

        this.eventsValue.forEach(event => {
            this.element.addEventListener(event, this.handleChangeThrottled);
        });
    }

    disconnect() {
        this.eventsValue.forEach(event => {
            this.element.removeEventListener(event, this.handleChangeThrottled);
        });
    }

    handleChange() {
        if (this.element.tagName !== 'FORM' || this.element.method.toLowerCase() !== 'get') {
            return
        }

        const url = new URL(this.element.action);
        url.search = new URLSearchParams(new FormData(this.element)).toString();

        const urlString = url.toString();

        if (urlString === this.lastUsedUrl) {
            return
        }

        this.lastUsedUrl = urlString;

        this.turboFrameIdsValue.forEach((id, index) => {
            const frame = document.querySelector(id);

            if (frame) {
                const urlWithCacheBuster = new URL(urlString);
                urlWithCacheBuster.searchParams.set('_v', index + Date.now());
                const finalUrl = urlWithCacheBuster.toString();

                console.log('[auto-submit-form] Updating frame : ' + id + ' with URL: ' + finalUrl + '')
                frame.setAttribute('src', finalUrl);
            } else {
                console.log('[auto-submit-form] Frame not found : "' + id +'".');
            }
        });
    }
}
