/*
 | Site-wide search dialog. Registered as the Alpine component `siteSearch` on
 | the full-screen panel in the header.
 |
 |   - opens from the header button, "/" (outside a field) or Cmd/Ctrl+K
 |   - focus moves to the field on open and is trapped in the panel; Escape
 |     closes and returns focus to whatever opened it
 |   - results come from GET /search with Accept: application/json, which
 |     returns the rendered results fragment, so the page and the dialog share
 |     one template; the summary line is announced through a live region
 |   - Enter submits the form to the plain /search page (the no-JavaScript
 |     route); arrow keys move through the results
 */

import { focusablesIn, trapTab } from './focus-trap';
import { t } from './i18n';

// App\Constants\Search::MIN_LENGTH and the debounce the field uses (ms).
const MIN_LENGTH = 2;
const OPEN_CLASS = 'search-open';
const SEARCHING = 'Searching…';

function isTyping(target) {
    if (!target || !(target instanceof Element)) return false;
    if (target.isContentEditable) return true;

    return ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName);
}

export default function siteSearch() {
    return {
        open: false,
        shown: false,
        loading: false,
        query: '',
        status: '',
        opener: null,
        controller: null,
        prompt: '',

        init() {
            this.prompt = this.$refs.results?.innerHTML ?? '';
            window.addEventListener('keydown', (event) => this.onKey(event));
        },

        onKey(event) {
            if ((event.key === 'k' || event.key === 'K') && (event.metaKey || event.ctrlKey) && !event.altKey) {
                event.preventDefault();
                this.toggle();
                return;
            }

            if (event.key === '/' && !this.open && !isTyping(event.target) && !event.metaKey && !event.ctrlKey && !event.altKey) {
                event.preventDefault();
                this.show();
            }
        },

        toggle(opener = null) {
            this.open ? this.close() : this.show(opener);
        },

        show(opener = null) {
            if (this.open) return;

            this.opener = opener instanceof Element ? opener : document.activeElement;
            this.open = true;
            document.documentElement.classList.add(OPEN_CLASS);

            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    this.shown = true;
                });
                this.$refs.input?.focus();
                this.$refs.input?.select();
            });
        },

        close(returnFocus = true) {
            if (!this.open) return;

            this.shown = false;
            this.open = false;
            this.abort();
            document.documentElement.classList.remove(OPEN_CLASS);

            if (returnFocus) {
                (this.opener instanceof Element ? this.opener : this.$refs.opener)?.focus();
            }

            this.opener = null;
        },

        abort() {
            this.controller?.abort();
            this.controller = null;
            this.loading = false;
        },

        lookup() {
            const query = this.query.trim();

            this.abort();

            if (query.length < MIN_LENGTH) {
                this.$refs.results.innerHTML = this.prompt;
                this.status = '';
                return;
            }

            const url = new URL(this.$refs.form.action, window.location.href);
            url.searchParams.set(this.$refs.input.name, query);

            this.controller = new AbortController();
            this.loading = true;
            this.status = t(SEARCHING);

            fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: this.controller.signal,
            })
                .then((response) => (response.ok ? response.json() : Promise.reject(new Error(String(response.status)))))
                .then((data) => {
                    if (data.query !== query) return; // A newer query has already replaced this one.
                    this.$refs.results.innerHTML = data.html;
                    this.status = data.summary || '';
                    this.loading = false;
                })
                .catch((error) => {
                    if (error.name === 'AbortError') return;
                    this.loading = false;
                    this.status = '';
                });
        },

        // Enter follows the focused result; otherwise the form submits to the plain page.
        submit(event) {
            const active = document.activeElement;

            if (active instanceof HTMLAnchorElement && this.$refs.results.contains(active)) {
                event.preventDefault();
                active.click();
            }
        },

        results() {
            return Array.from(this.$refs.results.querySelectorAll('a[href]'));
        },

        move(step) {
            const links = this.results();
            if (links.length === 0) return;

            const index = links.indexOf(document.activeElement);
            const next = index === -1 ? (step > 0 ? 0 : links.length - 1) : (index + step + links.length) % links.length;

            links[next].focus();
        },

        trap(event) {
            if (!this.open) return;
            trapTab(event, this.$refs.panel);
        },

        focusables() {
            return focusablesIn(this.$refs.panel);
        },
    };
}
