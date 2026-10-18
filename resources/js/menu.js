/*
 | Full-screen site menu (tablet and mobile). Registered as the Alpine component
 | `siteMenu` on the header. Accessible behaviour:
 |   - the toggle button reflects state through aria-expanded
 |   - focus moves into the panel on open and is trapped there (Tab / Shift+Tab wrap)
 |   - Escape closes and focus returns to the toggle button
 |   - page scrolling is locked while open; resizing to desktop closes it
 */

const DESKTOP = '(min-width: 1024px)'; // Tailwind `lg`: the inline navigation takes over.
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export default function siteMenu() {
    return {
        open: false,
        shown: false,

        toggle() {
            this.open ? this.close() : this.show();
        },

        show() {
            this.open = true;
            document.documentElement.classList.add('menu-open');

            this.$nextTick(() => {
                // Add the class one frame after display changes so the item transitions run.
                window.requestAnimationFrame(() => {
                    this.shown = true;
                });
                (this.$refs.close || this.focusables()[0] || this.$refs.panel)?.focus();
            });
        },

        close(returnFocus = true) {
            this.shown = false;
            this.open = false;
            document.documentElement.classList.remove('menu-open');

            if (returnFocus) {
                this.$refs.toggle?.focus();
            }
        },

        focusables() {
            return Array.from(this.$refs.panel.querySelectorAll(FOCUSABLE)).filter(
                (el) => el.offsetParent !== null || el === document.activeElement,
            );
        },

        trap(event) {
            if (!this.open || event.key !== 'Tab') return;

            const items = this.focusables();
            if (items.length === 0) {
                event.preventDefault();
                return;
            }

            const first = items[0];
            const last = items[items.length - 1];
            const active = document.activeElement;

            if (event.shiftKey && (active === first || active === this.$refs.panel)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }
        },

        onResize() {
            if (this.open && window.matchMedia(DESKTOP).matches) {
                this.close(false);
            }
        },
    };
}
