/*
 | The header's behaviour. Registered as the Alpine component `siteMenu`:
 |
 |   - the glass bar condenses gently once the page has scrolled past the
 |     threshold (the class drives a CSS height change; nothing is animated here)
 |   - the full-screen menu (tablet and mobile): the toggle button reflects state
 |     through aria-expanded, focus moves into the panel on open and is trapped
 |     there (Tab / Shift+Tab wrap), Escape closes and focus returns to the
 |     toggle button, page scrolling is locked while open, and resizing to
 |     desktop closes it
 */

import { focusablesIn, trapTab } from './focus-trap';

const DESKTOP = '(min-width: 1024px)'; // Tailwind `lg`: the inline navigation takes over.
// Scroll distance after which the header condenses (App\Constants\Motion::HEADER_CONDENSE_AFTER_PX).
const CONDENSE_AFTER = 40;
const CONDENSED_CLASS = 'header-condensed';

export default function siteMenu() {
    return {
        open: false,
        shown: false,
        condensed: false,
        ticking: false,

        init() {
            this.onScroll();
        },

        onScroll() {
            if (this.ticking) return;
            this.ticking = true;

            window.requestAnimationFrame(() => {
                this.ticking = false;
                const condensed = (window.scrollY || window.pageYOffset) > CONDENSE_AFTER;

                if (condensed !== this.condensed) {
                    this.condensed = condensed;
                    document.documentElement.classList.toggle(CONDENSED_CLASS, condensed);
                }
            });
        },

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
            return focusablesIn(this.$refs.panel);
        },

        trap(event) {
            if (!this.open) return;
            trapTab(event, this.$refs.panel);
        },

        onResize() {
            if (this.open && window.matchMedia(DESKTOP).matches) {
                this.close(false);
            }
        },
    };
}
