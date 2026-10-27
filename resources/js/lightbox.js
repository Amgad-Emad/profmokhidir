/*
 | Object view (album lightbox). Registered as the Alpine component `lightbox` on the gallery
 | wrapper; the <dialog> markup is <x-lightbox />. Each <x-photo :lightbox="true">
 | renders a link carrying data-lightbox-* attributes, which are read once here.
 |
 |   - opens as a modal <dialog>: the page behind is inert, Escape closes
 |   - Tab / Shift+Tab wrap inside; focus returns to the opening link on close
 |   - Left / Right (and Home / End) move between photographs; swipe does the same. On a
 |     right-to-left page the sequence runs from the right, so Left is next and Right is previous
 |   - photographs cross-fade only, never zoom; reduced motion swaps at once
 */

import { isRtl, t } from './i18n';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
const FADE_MS = 240;
const SWIPE_MIN_PX = 48;
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function itemFrom(link) {
    const data = link.dataset;

    return {
        src: data.lightboxSrc || link.getAttribute('href'),
        srcset: data.lightboxSrcset || '',
        alt: data.lightboxAlt || '',
        caption: data.lightboxCaption || '',
        meta: data.lightboxMeta || '',
        note: data.lightboxNote || '',
        credit: data.lightboxCredit || '',
        treatment: data.lightboxTreatment || '',
        width: Number(data.lightboxWidth) || null,
        height: Number(data.lightboxHeight) || null,
    };
}

export default function lightbox() {
    return {
        items: [],
        index: 0,
        open: false,
        loaded: false,
        current: {},
        returnTo: null,
        swapTimer: null,
        touchStart: null,

        init() {
            this.items = Array.from(this.$root.querySelectorAll('[data-lightbox-item]')).map((link, index) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    this.show(index, link);
                });

                return itemFrom(link);
            });
        },

        show(index, trigger = null) {
            if (this.items.length === 0) return;

            this.returnTo = trigger || document.activeElement;
            this.open = true;
            this.setCurrent(index);

            const dialog = this.$refs.dialog;
            if (typeof dialog.showModal === 'function') {
                if (!dialog.open) dialog.showModal();
            } else {
                dialog.setAttribute('open', '');
            }

            document.documentElement.classList.add('lightbox-open');
            this.$nextTick(() => this.$refs.close?.focus());
        },

        close() {
            const dialog = this.$refs.dialog;
            if (typeof dialog.close === 'function' && dialog.open) {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
                this.onClose();
            }
        },

        // Runs for both close() and the native Escape handling of <dialog>.
        onClose() {
            window.clearTimeout(this.swapTimer);
            this.open = false;
            this.loaded = false;
            this.current = {};
            document.documentElement.classList.remove('lightbox-open');

            const target = this.returnTo;
            this.returnTo = null;
            if (target && typeof target.focus === 'function') target.focus();
        },

        setCurrent(index) {
            const count = this.items.length;
            this.index = ((index % count) + count) % count;
            this.loaded = false;
            this.current = this.items[this.index];
        },

        go(index) {
            if (this.items.length < 2 || !this.open) return;

            window.clearTimeout(this.swapTimer);

            if (reducedMotion.matches) {
                this.setCurrent(index);
                return;
            }

            // Fade the current photograph out, then swap; the new one fades in on load.
            this.loaded = false;
            this.swapTimer = window.setTimeout(() => this.setCurrent(index), FADE_MS);
        },

        next() {
            this.go(this.index + 1);
        },

        prev() {
            this.go(this.index - 1);
        },

        counter() {
            return this.items.length > 0 ? t(':current of :total', { current: this.index + 1, total: this.items.length }) : '';
        },

        onKeydown(event) {
            if (!this.open) return;

            switch (event.key) {
                case 'ArrowRight':
                    event.preventDefault();
                    isRtl() ? this.prev() : this.next();
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    isRtl() ? this.next() : this.prev();
                    break;
                case 'Home':
                    event.preventDefault();
                    this.go(0);
                    break;
                case 'End':
                    event.preventDefault();
                    this.go(this.items.length - 1);
                    break;
                case 'Tab':
                    this.trap(event);
                    break;
                case 'Escape':
                    // Native <dialog> closes itself; the fallback needs a hand.
                    event.preventDefault();
                    this.close();
                    break;
                default:
                    break;
            }
        },

        focusables() {
            return Array.from(this.$refs.inner.querySelectorAll(FOCUSABLE)).filter((el) => el.offsetParent !== null);
        },

        trap(event) {
            const items = this.focusables();
            if (items.length === 0) {
                event.preventDefault();
                return;
            }

            const first = items[0];
            const last = items[items.length - 1];
            const active = document.activeElement;

            if (event.shiftKey && (active === first || !this.$refs.inner.contains(active))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (active === last || !this.$refs.inner.contains(active))) {
                event.preventDefault();
                first.focus();
            }
        },

        onBackdropClick(event) {
            if (event.target === this.$refs.dialog) this.close();
        },

        onTouchStart(event) {
            const touch = event.changedTouches?.[0];
            this.touchStart = touch ? { x: touch.clientX, y: touch.clientY } : null;
        },

        onTouchEnd(event) {
            const touch = event.changedTouches?.[0];
            if (!this.touchStart || !touch) return;

            const dx = touch.clientX - this.touchStart.x;
            const dy = touch.clientY - this.touchStart.y;
            this.touchStart = null;

            if (Math.abs(dx) < SWIPE_MIN_PX || Math.abs(dx) < Math.abs(dy)) return;

            // Swiping towards the start of the reading direction brings the next photograph in.
            const towardsStart = isRtl() ? dx > 0 : dx < 0;
            towardsStart ? this.next() : this.prev();
        },
    };
}
