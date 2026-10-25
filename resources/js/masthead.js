/*
 | The masthead's behaviour. Registered as the Alpine component `masthead`
 | (App\Constants\Masthead holds the values the constants below mirror):
 |
 |   - the bar compacts once the page has scrolled past the threshold and the
 |     reader is scrolling down; scrolling up restores it (classes drive the CSS).
 |     A scroll the browser makes to bring newly focused content into view (Tab)
 |     leaves the bar as it is, so the bar never grows over what was just
 |     focused; focus moving into the bar itself restores it
 |   - over the home hero ([data-masthead-hero]) the bar is transparent; the glass
 |     returns once the hero has scrolled past
 |   - the chapter disclosures: click, Enter or Space toggle a panel; ArrowDown
 |     opens it and focuses its first link; Left and Right move between chapters;
 |     Up, Down, Home and End move between links; Escape closes and returns focus
 |     to the button; focus leaving the chapter, a click outside or scrolling
 |     closes it; on fine pointers hover opens a panel after a short delay
 |   - the full-screen menu (tablet and mobile): the toggle button reflects state
 |     through aria-expanded, focus moves into the panel on open and is trapped
 |     there, Escape closes and focus returns to the button, page scrolling is
 |     locked while open, and resizing to desktop closes it
 */

import { focusablesIn, trapTab } from './focus-trap';

const DESKTOP = '(min-width: 1024px)'; // Tailwind `lg`: the chapter bar takes over.
const FINE_POINTER = '(hover: hover) and (pointer: fine)';
const COMPACT_AFTER = 40;
const SCROLL_JITTER = 4;
const FOCUS_SCROLL_MS = 250; // a scroll this soon after focus moved is the browser revealing the focused element
const SCROLL_CLOSES_AFTER = 24;
const HOVER_OPEN_DELAY = 150;
const HOVER_CLOSE_DELAY = 300;
const COMPACT_CLASS = 'header-compact';
const OVER_HERO_CLASS = 'over-hero';
const MENU_OPEN_CLASS = 'menu-open';
const HERO_SELECTOR = '[data-masthead-hero]';

export default function masthead() {
    return {
        open: false,
        shown: false,
        compact: false,
        overHero: false,
        openChapter: null,
        ticking: false,
        lastY: 0,
        hero: null,
        hoverTimer: null,
        leaveTimer: null,
        focusedAt: 0,

        init() {
            this.hero = document.querySelector(HERO_SELECTOR);
            this.lastY = window.scrollY;
            document.addEventListener('focusin', (event) => {
                this.focusedAt = performance.now();
                if (this.$el.contains(event.target)) this.setCompact(false);
            });
            this.update();
        },

        onScroll() {
            if (this.ticking) return;
            this.ticking = true;

            window.requestAnimationFrame(() => {
                this.ticking = false;
                this.update();
            });
        },

        update() {
            const y = window.scrollY;
            const delta = y - this.lastY;

            const revealingFocus = performance.now() - this.focusedAt < FOCUS_SCROLL_MS;

            if (y <= COMPACT_AFTER) {
                this.setCompact(false);
            } else if (revealingFocus) {
                // Keep the bar's size: the browser placed the focused element just below it.
            } else if (delta > SCROLL_JITTER) {
                this.setCompact(true);
            } else if (delta < -SCROLL_JITTER) {
                this.setCompact(false);
            }

            if (Math.abs(delta) > SCROLL_JITTER || y <= COMPACT_AFTER) {
                this.lastY = y;
            }

            if (this.openChapter !== null && Math.abs(delta) > SCROLL_CLOSES_AFTER) {
                this.closeChapter();
            }

            this.setOverHero(this.hero !== null && this.hero.getBoundingClientRect().bottom > this.$el.offsetHeight);
        },

        setCompact(compact) {
            if (compact === this.compact) return;
            this.compact = compact;
            document.documentElement.classList.toggle(COMPACT_CLASS, compact);
        },

        setOverHero(over) {
            if (over === this.overHero) return;
            this.overHero = over;
            document.documentElement.classList.toggle(OVER_HERO_CLASS, over);
        },

        /* ------------------------------------------------------------ chapters */
        isOpen(key) {
            return this.openChapter === key;
        },

        toggleChapter(key) {
            this.isOpen(key) ? this.closeChapter() : this.showChapter(key);
        },

        showChapter(key) {
            this.clearTimers();
            this.openChapter = key;
        },

        closeChapter(returnFocus = false) {
            const key = this.openChapter;
            if (key === null) return;

            this.clearTimers();
            this.openChapter = null;

            if (returnFocus) {
                this.button(key)?.focus();
            }
        },

        openAndFocus(key) {
            this.showChapter(key);
            this.$nextTick(() => this.links(key)[0]?.focus());
        },

        hoverOpen(key) {
            if (!this.hoverOpens()) return;
            this.clearTimers();
            if (this.isOpen(key)) return;

            this.hoverTimer = window.setTimeout(() => this.showChapter(key), HOVER_OPEN_DELAY);
        },

        hoverClose(key) {
            this.clearTimers();
            if (!this.hoverOpens() || !this.isOpen(key)) return;

            this.leaveTimer = window.setTimeout(() => {
                if (this.isOpen(key) && !this.chapter(key)?.contains(document.activeElement)) {
                    this.closeChapter();
                }
            }, HOVER_CLOSE_DELAY);
        },

        hoverOpens() {
            return window.matchMedia(FINE_POINTER).matches && window.matchMedia(DESKTOP).matches;
        },

        onFocusOut(event, key) {
            if (!this.isOpen(key)) return;
            const next = event.relatedTarget;
            if (next && this.chapter(key)?.contains(next)) return;

            this.closeChapter();
        },

        onClickOutside(key) {
            if (this.isOpen(key)) this.closeChapter();
        },

        focusSibling(event, direction) {
            const buttons = this.buttons();
            const index = buttons.indexOf(event.currentTarget);
            if (index < 0) return;

            const next = buttons[(index + direction + buttons.length) % buttons.length];
            const wasOpen = this.openChapter !== null;
            next.focus();

            if (wasOpen) {
                this.showChapter(next.closest('[data-chapter]').dataset.chapter);
            }
        },

        moveFocus(event, direction) {
            const key = this.openChapter;
            if (key === null) return;

            const links = this.links(key);
            if (links.length === 0) return;

            let index;
            if (direction === 'first') {
                index = 0;
            } else if (direction === 'last') {
                index = links.length - 1;
            } else {
                const current = links.indexOf(document.activeElement);
                index = current < 0 ? (direction > 0 ? 0 : links.length - 1) : (current + direction + links.length) % links.length;
            }

            links[index].focus();
        },

        onEscape() {
            if (this.openChapter !== null) {
                this.closeChapter(true);
                return;
            }

            if (this.open) this.closeMenu();
        },

        buttons() {
            return Array.from(this.$refs.chapters?.querySelectorAll('button[aria-controls]') ?? []);
        },

        button(key) {
            return this.chapter(key)?.querySelector('button[aria-controls]') ?? null;
        },

        chapter(key) {
            return this.$refs.chapters?.querySelector(`[data-chapter="${key}"]`) ?? null;
        },

        links(key) {
            return Array.from(this.chapter(key)?.querySelectorAll('a[href]') ?? []);
        },

        clearTimers() {
            window.clearTimeout(this.hoverTimer);
            window.clearTimeout(this.leaveTimer);
            this.hoverTimer = null;
            this.leaveTimer = null;
        },

        /* ------------------------------------------------------------ full-screen menu */
        toggleMenu() {
            this.open ? this.closeMenu() : this.showMenu();
        },

        showMenu() {
            this.closeChapter();
            this.open = true;
            document.documentElement.classList.add(MENU_OPEN_CLASS);

            this.$nextTick(() => {
                // Add the class one frame after display changes so the item transitions run.
                window.requestAnimationFrame(() => {
                    this.shown = true;
                });
                (this.$refs.close || this.focusables()[0] || this.$refs.panel)?.focus();
            });
        },

        closeMenu(returnFocus = true) {
            this.shown = false;
            this.open = false;
            document.documentElement.classList.remove(MENU_OPEN_CLASS);

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
            const desktop = window.matchMedia(DESKTOP).matches;

            if (this.open && desktop) this.closeMenu(false);
            if (this.openChapter !== null && !desktop) this.closeChapter();
        },
    };
}
