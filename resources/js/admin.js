// Admin and community-chat layouts. Livewire ships its own Alpine build via
// @livewireScripts; the components below register on alpine:init.
import './i18n';
import './theme';
import { initForms, registerForms } from './forms';
import { adminShell } from './admin/shell';
import { commandPalette } from './admin/palette';
import { adminToasts } from './admin/toasts';
import { editScreen, leaveGuard, sectionNav } from './admin/editing';
import { pagePreview } from './admin/page-preview';
import { watchPlaceholders } from './admin/placeholders';
import { initShortcuts } from './admin/shortcuts';

initForms();
initShortcuts();
// The marker and the flag's wording come from App\Constants\Placeholders (on <body>).
watchPlaceholders(document.body.dataset);

document.addEventListener('alpine:init', () => {
    registerForms(Alpine);

    Alpine.data('adminShell', adminShell);
    Alpine.data('commandPalette', commandPalette);
    Alpine.data('adminToasts', adminToasts);
    Alpine.data('editScreen', editScreen);
    Alpine.data('leaveGuard', leaveGuard);
    Alpine.data('sectionNav', sectionNav);
    Alpine.data('pagePreview', pagePreview);

    /*
     | Drag-and-drop reordering of table rows (native HTML5 drag events). Rows are
     | moved in the DOM while dragging; on drop the new id order is sent to the
     | component's reorder() method. Move up/down buttons remain the keyboard path.
     */
    Alpine.data('sortableRows', () => ({
        draggingId: null,
        dragged: null,
        moved: false,

        start(event, id) {
            this.draggingId = id;
            this.dragged = event.currentTarget;
            this.moved = false;
            event.dataTransfer.effectAllowed = 'move';
            try { event.dataTransfer.setData('text/plain', String(id)); } catch (e) { /* older browsers */ }
            this.dragged.classList.add('is-dragging');
        },

        over(event) {
            const target = event.currentTarget;
            if (!this.dragged || target === this.dragged || target.parentNode !== this.dragged.parentNode) return;
            const rect = target.getBoundingClientRect();
            const after = event.clientY > rect.top + rect.height / 2;
            target.parentNode.insertBefore(this.dragged, after ? target.nextSibling : target);
            this.moved = true;
        },

        ids() {
            // $root is the <tbody> carrying x-data; $el would be the row whose
            // dragend handler is running, which contains no rows at all.
            return Array.from(this.$root.querySelectorAll('tr[data-id]')).map((row) => Number(row.dataset.id));
        },

        finish(event) {
            // Drop handler on the body; order is sent from dragend so a cancelled drag sends nothing.
        },

        end(event) {
            event.currentTarget.classList.remove('is-dragging');
            if (this.moved && this.draggingId !== null) {
                this.$wire.reorder(this.ids());
            }
            this.draggingId = null;
            this.dragged = null;
            this.moved = false;
        },
    }));
});
