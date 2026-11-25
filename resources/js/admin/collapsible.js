/*
 | A part of an edit screen that folds away: a form section, or one item of a
 | page list (a chapter, a figure, a card). `startOpen` sets how it first
 | shows; with `remember` the viewer's choice is kept per screen (and an
 | address pointing into it opens it on load), without it every visit starts
 | the same way (the page editor starts all folded, whatever the address).
 | Parts that share a `group` act as an accordion: opening one folds the rest.
 | The section nav only scrolls to a part and never opens it; a link that points
 | at a field inside a part (an error summary) opens it, and so does the keyboard
 | reaching a field inside its body.
 */
import { readSetting, writeSetting } from './storage';

const OPENED = 'admin-collapsible-opened';

export function collapsible(id, startOpen = true, remember = true, group = null) {
    const key = `admin.collapsed.${window.location.pathname}.${id}`;

    return {
        open: remember ? !readSetting(key, !startOpen) : startOpen,
        // Clipping is lifted once a panel has finished opening, so focus rings and pop-overs show.
        settled: remember ? !readSetting(key, !startOpen) : startOpen,

        init() {
            this.$watch('open', (open) => {
                this.settled = false;
                clearTimeout(this._settle);
                if (open) this._settle = setTimeout(() => { this.settled = this.open; }, 450);
                if (remember) writeSetting(key, !open);
                if (open && group) window.dispatchEvent(new CustomEvent(OPENED, { detail: { group, id } }));
            });

            if (remember) this.reveal(window.location.hash);

            this._onClick = (event) => {
                const link = event.target.closest('a[href^="#"]');
                if (link && !link.closest('.edit-layout__nav')) this.reveal(link.getAttribute('href'));
            };
            this._onOpened = (event) => {
                if (group && event.detail.group === group && event.detail.id !== id) this.open = false;
            };

            document.addEventListener('click', this._onClick);
            window.addEventListener(OPENED, this._onOpened);
        },

        destroy() {
            document.removeEventListener('click', this._onClick);
            window.removeEventListener(OPENED, this._onOpened);
        },

        toggle() {
            this.open = !this.open;
        },

        // A click anywhere on the part opens it, and another click anywhere on it folds it again.
        // Controls keep their own action (the toggle, fields, Remove, links, labels that focus a
        // field, the image focal-point picker), and a click that ends a text selection is left alone.
        toggleFromClick(event) {
            if (event.target.closest('button, a, input, select, textarea, label, [contenteditable], [role="button"], .focus-picker, [data-keep-open]')) {
                return;
            }

            if (String(window.getSelection?.() ?? '') !== '') {
                return;
            }

            this.toggle();
        },

        // Focus reaching a field inside the body opens the part (not focus on its own heading).
        // The body must be this part's own: a page-list item sits inside a form section's body,
        // and focus on the item's toggle (a click focuses it first) must not count as inside the
        // item, or the click's toggle would fold it again straight away.
        focusIn(event) {
            const body = this.$el.querySelector(':scope > [data-collapsible-body]');

            if (body && body.contains(event.target)) {
                this.open = true;
            }
        },

        // Open when a link or the address points at this part or at something inside it.
        reveal(hash) {
            const target = hash && hash.length > 1 ? document.getElementById(decodeURIComponent(hash.slice(1))) : null;

            if (target && (target === this.$el || this.$el.contains(target))) {
                this.open = true;
            }
        },
    };
}
