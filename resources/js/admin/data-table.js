/*
 | The shared admin data table (x-admin.data-table): the soft edge shadows while columns overflow,
 | a click anywhere on a row opening its edit screen (the title stays a real link for the keyboard
 | and middle-click), shift-click ranges of checkboxes, the rows' detail disclosures, and Reorder
 | mode (drag and drop, or the arrow buttons, then Save).
 */
const INTERACTIVE = 'a, button, input, label, select, textarea, summary, [role="switch"], [data-row-menu], [data-row-actions], [data-row-detail]';
const CARDS = '(width < 48rem)';

export function dataTable() {
    return {
        last: null,
        opened: {},
        dragged: null,

        init() {
            const media = window.matchMedia(CARDS);
            const layout = () => {
                this.$root.dataset.layout = media.matches ? 'cards' : 'table';
                this.edges();
            };
            media.addEventListener('change', layout);
            layout();
            if (this.$refs.scroll && 'ResizeObserver' in window) {
                new ResizeObserver(() => this.edges()).observe(this.$refs.scroll);
            }
        },

        // Shadows at the edges that still hide columns (scrollLeft runs negative right to left).
        edges() {
            const scroll = this.$refs.scroll;
            if (!scroll) return;
            const offset = Math.abs(scroll.scrollLeft);
            this.$root.toggleAttribute('data-more-start', offset > 1);
            this.$root.toggleAttribute('data-more-end', offset + scroll.clientWidth < scroll.scrollWidth - 1);
        },

        open(event) {
            if (this.$root.hasAttribute('data-reordering') || event.target.closest(INTERACTIVE)) return;
            if (window.getSelection()?.toString()) return;
            const row = event.target.closest('tr[data-href]');
            if (!row) return;
            if (event.metaKey || event.ctrlKey) window.open(row.dataset.href, '_blank', 'noopener');
            else window.location.assign(row.dataset.href);
        },

        ids() {
            return [...this.$refs.rows.querySelectorAll(':scope > tr[data-row]')].map((row) => Number(row.dataset.id));
        },

        // A checkbox: one row, or with Shift every row since the last one ticked.
        tick(event, id) {
            const on = event.target.checked;
            if (event.shiftKey && this.last !== null && this.last !== id) {
                this.$wire.selectRange(this.last, id, on, this.ids());
            } else {
                this.$wire.selectRows([id], on);
            }
            this.last = id;
        },

        isOpen(id, fromServer) {
            return id in this.opened ? this.opened[id] : fromServer;
        },

        toggle(id) {
            const button = this.$root.querySelector(`[aria-controls$="-detail-${CSS.escape(id)}"]`);
            this.opened[id] = !(button?.getAttribute('aria-expanded') === 'true');
        },

        start(event) {
            this.dragged = event.currentTarget;
            event.dataTransfer.effectAllowed = 'move';
            try { event.dataTransfer.setData('text/plain', this.dragged.dataset.id); } catch (e) { /* older browsers */ }
            this.dragged.classList.add('is-dragging');
        },

        over(event) {
            const target = event.target.closest('tr[data-row]');
            if (!this.dragged || !target || target === this.dragged) return;
            const rect = target.getBoundingClientRect();
            target.parentNode.insertBefore(this.dragged, event.clientY > rect.top + rect.height / 2 ? target.nextSibling : target);
        },

        drop() {},

        end(event) {
            event.currentTarget.classList.remove('is-dragging');
            this.dragged = null;
        },

        // The arrow buttons: the keyboard way to move a row one place.
        move(event, offset) {
            const button = event.currentTarget;
            const row = button.closest('tr[data-row]');
            const sibling = offset < 0 ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling?.matches('tr[data-row]')) return;
            row.parentNode.insertBefore(row, offset < 0 ? sibling : sibling.nextSibling);
            button.focus();
        },

        saveOrder() {
            this.$wire.saveOrder(this.ids());
        },
    };
}
