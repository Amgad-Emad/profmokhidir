/*
 | Publications catalogue behaviour (Alpine components):
 |
 |   catalogueSheet   below the `lg` breakpoint the filters become a sheet that rises from
 |                    the bottom of the screen: a modal dialog while open, focus moved in
 |                    and trapped, Escape or the backdrop closes it and focus returns to
 |                    the "Refine" button. Without JavaScript the filters stay inline.
 */

const DESKTOP = '(min-width: 1024px)'; // Tailwind `lg`: the filters sit beside the results.
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

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
