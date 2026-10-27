/*
 | The command palette: screens and "New ..." actions filtered in the browser,
 | content titles from the admin.search JSON route (debounced, the previous
 | request aborted). A combobox over a grouped listbox; navigation goes through
 | the leave guard (admin-navigate) so unsaved changes are never lost silently.
 */
const normalise = (text) => String(text ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

import { t } from '../i18n';

export function commandPalette({ entries = [], endpoint = '', min = 2 } = {}) {
    let timer = null;
    let controller = null;

    return {
        open: false,
        query: '',
        results: [],
        loading: false,
        activeId: null,
        announcement: '',

        show() {
            this.open = true;
            this.query = '';
            this.results = [];
            this.$nextTick(() => {
                this.$refs.input?.focus();
                this.activateFirst();
            });
        },

        close() {
            this.open = false;
            controller?.abort();
        },

        get groups() {
            const words = normalise(this.query).split(/\s+/).filter(Boolean);
            const matches = entries.filter((entry) => {
                const haystack = normalise(`${entry.label} ${entry.hint} ${entry.keywords}`);
                return words.every((word) => haystack.includes(word));
            });
            const items = [...matches, ...this.results];
            const groups = [];

            items.forEach((item, index) => {
                let group = groups.find((candidate) => candidate.name === item.group);
                if (!group) {
                    group = { name: item.group, id: `palette-group-${groups.length}`, items: [] };
                    groups.push(group);
                }
                group.items.push({ ...item, id: `palette-option-${index}` });
            });

            return groups;
        },

        get flat() {
            return this.groups.flatMap((group) => group.items);
        },

        activateFirst() {
            this.activeId = this.flat[0]?.id ?? null;
        },

        search() {
            this.activateFirst();
            clearTimeout(timer);
            controller?.abort();
            const term = this.query.trim();

            if (term.length < min) {
                this.results = [];
                this.loading = false;
                this.announce();
                return;
            }

            this.loading = true;
            timer = setTimeout(async () => {
                controller = new AbortController();
                try {
                    const response = await fetch(`${endpoint}?q=${encodeURIComponent(term)}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller.signal,
                    });
                    const data = response.ok ? await response.json() : { results: [] };
                    this.results = Array.isArray(data.results) ? data.results : [];
                } catch (error) {
                    if (error.name !== 'AbortError') this.results = [];
                } finally {
                    this.loading = false;
                    this.activateFirst();
                    this.announce();
                }
            }, 200);
        },

        announce() {
            const count = this.flat.length;
            this.announcement = count === 0 ? t('No results') : (count === 1 ? t('1 result') : t(':count results', { count }));
        },

        move(step) {
            const list = this.flat;
            if (list.length === 0) return;
            const index = list.findIndex((item) => item.id === this.activeId);
            const next = list[(index + step + list.length) % list.length];
            this.activeId = next.id;
            this.$nextTick(() => document.getElementById(next.id)?.scrollIntoView({ block: 'nearest' }));
        },

        choose() {
            const item = this.flat.find((candidate) => candidate.id === this.activeId) ?? this.flat[0];
            if (item) this.go(item);
        },

        go(item) {
            this.close();
            window.dispatchEvent(new CustomEvent('admin-navigate', { detail: { url: item.url } }));
        },
    };
}
