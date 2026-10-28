/*
 | The admin list toolbar (resources/views/components/admin/list-toolbar.blade.php).
 |
 | listToolbar: the band itself. Filter buttons that do not fit on the line fold into
 | "Filters (n)". Arrow keys move between the view tabs (mirrored on
 | right-to-left pages), the filters sheet opens and closes and its Apply sends every
 | choice in one call, and after a filter change the band is kept in view.
 |
 | listPopover: one filter (or the Sort menu). It opens on its button (or Arrow Down),
 | focuses the chosen option, Arrow Up/Down, Home and End move through the options,
 | Space or Enter choose one (the menu stays open while the list updates behind it),
 | Escape closes it and returns focus to the button, Tab out closes it quietly.
 */
const rtl = (element) => getComputedStyle(element).direction === 'rtl';

export function listToolbar() {
    return {
        sheet: false,
        folded: false,

        init() {
            this.fit();
            new ResizeObserver(() => this.fit()).observe(this.$root);
            window.Livewire?.hook('commit', ({ succeed }) => succeed(() => this.$nextTick(() => this.fit())));
        },

        // Fold the filter buttons into "Filters (n)" when they do not fit on the line.
        fit() {
            const row = this.$refs.row;
            if (!row) return;
            row.classList.remove('is-folded');
            const overflows = row.scrollWidth > row.clientWidth + 1;
            row.classList.toggle('is-folded', overflows);
            this.folded = overflows;
        },

        tabKeys(event) {
            const tabs = [...this.$root.querySelectorAll('[role="tab"]')];
            const index = tabs.indexOf(document.activeElement);
            if (index === -1) return;
            const forward = rtl(this.$root) ? 'ArrowLeft' : 'ArrowRight';
            const backward = rtl(this.$root) ? 'ArrowRight' : 'ArrowLeft';
            const next = {
                [forward]: tabs[(index + 1) % tabs.length],
                [backward]: tabs[(index - 1 + tabs.length) % tabs.length],
                Home: tabs[0],
                End: tabs[tabs.length - 1],
            }[event.key];
            if (!next) return;
            event.preventDefault();
            tabs.forEach((tab) => tab.setAttribute('tabindex', tab === next ? '0' : '-1'));
            next.focus();
            next.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        },

        openSheet() {
            this.sheet = true;
        },

        closeSheet() {
            this.sheet = false;
            this.$nextTick(() => this.$root.querySelector('[data-filters-sheet-button]')?.focus());
        },

        // Untick everything in the sheet; nothing is sent until Apply.
        reset(form) {
            form.querySelectorAll('[data-sheet-filter] input').forEach((input) => {
                input.checked = input.type === 'radio' && input.value === '';
            });
        },

        apply(form) {
            const filters = {};
            form.querySelectorAll('[data-sheet-filter]').forEach((group) => {
                filters[group.dataset.sheetFilter] = [...group.querySelectorAll('input:checked')].map((input) => input.value).filter((value) => value !== '');
            });
            const data = new FormData(form);
            this.$wire.applyFilters(filters, data.has('sort') ? data.get('sort') : null, data.get('density'));
            this.closeSheet();
        },

        // A shorter list must not leave the editor scrolled past the band.
        keepInView() {
            const top = this.$root.getBoundingClientRect().top;
            if (top < 0) this.$root.scrollIntoView({ block: 'start' });
        },
    };
}

export function listPopover() {
    return {
        open: false,

        items() {
            return [...this.$refs.panel.querySelectorAll('[role^="menuitem"]')];
        },

        toggle() {
            if (this.open) this.close(true);
            else this.show();
        },

        show() {
            this.open = true;
            this.$nextTick(() => {
                const items = this.items();
                (items.find((item) => item.getAttribute('aria-checked') === 'true') ?? items[0])?.focus();
            });
        },

        close(returnFocus, event = null) {
            if (!this.open) return;
            event?.preventDefault();
            event?.stopPropagation();
            this.open = false;
            if (returnFocus) this.$refs.button.focus();
        },

        // Focus leaving the filter altogether (Tab) closes it without moving focus back.
        leave(event) {
            if (this.open && event.relatedTarget && !this.$root.contains(event.relatedTarget)) this.open = false;
        },

        nav(event) {
            const items = this.items();
            const index = items.indexOf(document.activeElement);
            const next = {
                ArrowDown: items[(index + 1) % items.length],
                ArrowUp: items[(index - 1 + items.length) % items.length],
                Home: items[0],
                End: items[items.length - 1],
            }[event.key];
            if (!next) return;
            event.preventDefault();
            next.focus();
        },
    };
}
