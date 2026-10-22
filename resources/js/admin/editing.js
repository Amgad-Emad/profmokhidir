import { eventDetail } from './storage';

/*
 | Edit screens. `editScreen` sits on the form: it notices changes (inputs,
 | the rich text editor, uploads), autosaves drafts every N seconds and when a
 | field is left, shows "Saved 2 minutes ago" / "Unsaved changes" in the save
 | bar, opens the preview after saving the draft, and tells the leave guard
 | whether there is anything to lose. `leaveGuard` asks "Leave without
 | saving?" before following a link, and the browser asks before a reload.
 */
const relative = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

export function timeAgo(iso, now = Date.now()) {
    const seconds = Math.round((new Date(iso).getTime() - now) / 1000);
    if (Number.isNaN(seconds)) return '';
    if (Math.abs(seconds) < 45) return 'just now';
    const minutes = Math.round(seconds / 60);
    if (Math.abs(minutes) < 60) return relative.format(minutes, 'minute');
    const hours = Math.round(minutes / 60);
    if (Math.abs(hours) < 24) return relative.format(hours, 'hour');
    return relative.format(Math.round(hours / 24), 'day');
}

function announceDirty(dirty) {
    window.dispatchEvent(new CustomEvent('admin-dirty', { detail: { dirty } }));
}

export function editScreen({ seconds = 30 } = {}) {
    let interval = null;
    let blurTimer = null;
    const listeners = [];

    return {
        dirty: false,
        saving: false,
        blocked: '',
        savedAt: null,
        now: Date.now(),
        autosaveOn: false,

        init() {
            // Read once; the server re-renders these attributes, the component keeps its own state.
            this.savedAt = this.$el.dataset.savedAt || null;
            this.autosaveOn = this.$el.dataset.autosave === 'true';

            const mark = (event) => {
                // Uploads save themselves, and some controls (image details) save on their own.
                if (event.target?.type === 'file' || event.target?.closest?.('[data-not-content]')) return;
                this.setDirty(true);
            };
            ['input', 'change', 'form-editor-change'].forEach((name) => this.$el.addEventListener(name, mark));
            this.$el.addEventListener('focusout', () => {
                if (!this.dirty || !this.autosaveOn) return;
                clearTimeout(blurTimer);
                blurTimer = setTimeout(() => this.autosave(), 800);
            });

            const on = (name, handler) => {
                window.addEventListener(name, handler);
                listeners.push([name, handler]);
            };
            on('admin-saved', (event) => {
                const detail = eventDetail(event);
                this.saving = false;
                this.blocked = detail.blocked || '';
                if (detail.autosave !== undefined) this.autosaveOn = Boolean(detail.autosave);
                if (!detail.blocked) {
                    this.savedAt = detail.at || new Date().toISOString();
                    this.setDirty(false);
                }
            });
            on('admin-changed', () => this.setDirty(true));
            // The first autosave of a new record: its address and name replace "New ...".
            on('admin-address', (event) => {
                const { url, crumb } = eventDetail(event);
                if (url) window.history.replaceState(window.history.state, '', url);
                const current = document.querySelector('[data-admin-breadcrumbs] [aria-current="page"]');
                if (crumb && current) current.textContent = crumb;
            });

            interval = setInterval(() => {
                this.now = Date.now();
                if (this.dirty && this.autosaveOn && !this.saving) this.autosave();
            }, seconds * 1000);
        },

        destroy() {
            clearInterval(interval);
            clearTimeout(blurTimer);
            listeners.forEach(([name, handler]) => window.removeEventListener(name, handler));
            announceDirty(false);
        },

        setDirty(dirty) {
            this.dirty = dirty;
            if (dirty) this.blocked = '';
            announceDirty(dirty);
        },

        autosave() {
            if (this.saving || !this.autosaveOn) return Promise.resolve();
            this.saving = true;
            return Promise.resolve(this.$wire.autosave()).finally(() => {
                this.saving = false;
            });
        },

        get stateLabel() {
            if (this.saving) return 'Saving…';
            if (this.blocked) return this.blocked;
            if (this.dirty) return 'Unsaved changes';
            if (!this.savedAt) return 'Not saved yet';
            return `Saved ${timeAgo(this.savedAt, this.now)}`;
        },

        get stateTone() {
            if (this.blocked || this.dirty) return 'pending';
            return this.savedAt ? 'saved' : 'new';
        },

        // The preview shows what is saved: save the draft first, in a tab
        // opened during the click so it is not blocked as a pop-up.
        preview(event) {
            if (!this.dirty || !this.autosaveOn) return;
            event.preventDefault();
            const url = event.currentTarget.href;
            const tab = window.open('', '_blank');
            this.autosave().then(() => {
                if (tab) tab.location.href = url;
                else window.open(url, '_blank', 'noopener');
            });
        },
    };
}

export function leaveGuard() {
    return {
        dirty: false,
        asking: false,
        leaving: false,
        target: null,

        init() {
            window.addEventListener('admin-dirty', (event) => {
                this.dirty = Boolean(eventDetail(event).dirty);
            });

            document.addEventListener('click', (event) => {
                if (!this.dirty || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                const link = event.target.closest('a[href]');
                if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
                const url = new URL(link.href, window.location.href);
                if (url.origin !== window.location.origin) return;
                if (url.pathname === window.location.pathname && url.hash) return;
                event.preventDefault();
                this.ask(url.href);
            });

            window.addEventListener('admin-navigate', (event) => {
                const url = eventDetail(event).url;
                if (!url) return;
                if (this.dirty) this.ask(url);
                else window.location.href = url;
            });

            window.addEventListener('beforeunload', (event) => {
                if (!this.dirty || this.leaving) return;
                event.preventDefault();
                event.returnValue = '';
            });
        },

        ask(url) {
            this.target = url;
            this.asking = true;
        },

        stay() {
            this.asking = false;
            this.target = null;
        },

        leave() {
            this.leaving = true;
            this.asking = false;
            window.location.href = this.target;
        },
    };
}

/*
 | The "On this page" nav of a sectioned form: marks the section in view
 | (aria-current) as the page scrolls.
 */
export function sectionNav() {
    let observer = null;

    return {
        current: null,

        init() {
            const sections = Array.from(document.querySelectorAll('[data-form-section]'));
            if (sections.length === 0 || !('IntersectionObserver' in window)) return;
            this.current = sections[0].dataset.formSection;
            observer = new IntersectionObserver((entries) => {
                const visible = entries.filter((entry) => entry.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                if (visible[0]) this.current = visible[0].target.dataset.formSection;
            }, { rootMargin: '-20% 0px -60% 0px' });
            sections.forEach((section) => observer.observe(section));
        },

        destroy() {
            observer?.disconnect();
        },
    };
}
