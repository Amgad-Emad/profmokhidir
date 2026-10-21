/*
 | Publications catalogue behaviour (Alpine components):
 |
 |   catalogueSheet   below the `lg` breakpoint the filters become a sheet that rises from
 |                    the bottom of the screen: a modal dialog while open, focus moved in
 |                    and trapped, Escape or the backdrop closes it and focus returns to
 |                    the "Refine" button. Without JavaScript the filters stay inline.
 |   citePanel        the "Cite" panel: one tab per citation style (arrow keys move between
 |                    them) and a copy button that confirms quietly with "Copied".
 */

const DESKTOP = '(min-width: 1024px)'; // Tailwind `lg`: the filters sit beside the results.
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
const COPIED_MS = 2400;

export function catalogueSheet() {
    return {
        open: false,
        enhanced: false,

        init() {
            this.enhanced = true;
            this.desktop = window.matchMedia(DESKTOP);
            this.desktop.addEventListener('change', () => {
                if (this.desktop.matches && this.open) this.close(false);
            });
        },

        show() {
            this.open = true;
            document.documentElement.classList.add('menu-open');
            this.$nextTick(() => (this.$refs.close || this.$refs.panel).focus());
        },

        close(returnFocus = true) {
            this.open = false;
            document.documentElement.classList.remove('menu-open');
            if (returnFocus) this.$refs.toggle?.focus();
        },

        trap(event) {
            if (!this.open) return;

            const items = Array.from(this.$refs.panel.querySelectorAll(FOCUSABLE)).filter((el) => el.offsetParent !== null);
            if (items.length === 0) return;

            const first = items[0];
            const last = items[items.length - 1];

            if (event.shiftKey && (document.activeElement === first || document.activeElement === this.$refs.panel)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}

export function citePanel() {
    return {
        style: null,
        copied: false,
        timer: null,

        init() {
            this.style = this.$el.dataset.defaultStyle;
            // The tabs now carry the style names, so the headings inside the panels can go.
            this.$el.setAttribute('data-enhanced', '');
        },

        select(style) {
            this.style = style;
            this.copied = false;
        },

        // Left/Right (and Home/End) move between the style tabs, selecting as they go.
        move(event) {
            const tabs = Array.from(event.currentTarget.closest('[role="tablist"]').querySelectorAll('[role="tab"]'));
            const index = tabs.indexOf(event.currentTarget);
            const next = {
                ArrowRight: (index + 1) % tabs.length,
                ArrowLeft: (index - 1 + tabs.length) % tabs.length,
                Home: 0,
                End: tabs.length - 1,
            }[event.key];

            if (next === undefined) return;

            event.preventDefault();
            tabs[next].focus();
            this.select(tabs[next].dataset.style);
        },

        copy() {
            const text = this.$root.querySelector(`[data-format="${this.style}"] [data-cite-text]`)?.textContent.trim();
            if (!text || !navigator.clipboard) return;

            navigator.clipboard.writeText(text).then(() => {
                this.copied = true;
                window.clearTimeout(this.timer);
                this.timer = window.setTimeout(() => (this.copied = false), COPIED_MS);
            });
        },
    };
}
