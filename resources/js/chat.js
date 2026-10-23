/*
 | The correspondence room (members only). Livewire carries requests and ships
 | Alpine; this script renders the conversation and handles the client half of
 | the reliability rules: optimistic sends with a client uuid, a keyset cursor
 | (last seen id) plus a "changed since" cursor, scroll preservation, unread
 | state, tab visibility, and reconnecting with back-off after 419/401/network
 | failures. The server (ChatService) is the source of truth for ordering.
 | On top of that: runs of one author within five minutes, replies, permalinks,
 | pins, search, a saved draft, keyboard travel between messages, the side panel
 | (About, Pinned, Members), attachments (staged with a progress bar, then sent
 | with the message under the same client uuid) and KaTeX typesetting fetched
 | only when a message carries maths.
 |
 | The draft lives only in Alpine state: the shell is wire:ignore and the
 | component never re-renders on a poll, so nothing replaces what is typed.
 */
import './theme';
import { initForms, registerForms } from './forms';
import lightbox from './lightbox';

initForms();

const STATE_SENDING = 'sending';
const STATE_FAILED = 'failed';
const POLL_EXPRESSION = '$js.tick';
const BOTTOM_THRESHOLD = 48;
const TOP_THRESHOLD = 80;
const WIDE_QUERY = '(min-width: 1024px)';
const PANEL_STORAGE_KEY = 'chat-panel';
const STATE_UPLOADING = 'uploading';
const STATE_READY = 'ready';
/** How long a new message keeps its arrival class (the rise itself is 300ms in chat.css). */
const ARRIVAL_MS = 600;

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function uuid() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
    // RFC 4122 v4 fallback for very old browsers.
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

function fileSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(bytes < 10240 ? 1 : 0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function extensionOf(name) {
    const match = /\.([a-z0-9]+)$/i.exec(name || '');
    return match ? match[1].toLowerCase() : '';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/** A HEIC photo the browser itself can decode (Safari) becomes a JPEG before it leaves the device. */
async function heicToJpeg(file) {
    if (typeof createImageBitmap !== 'function') return file;
    try {
        const bitmap = await createImageBitmap(file);
        const canvas = document.createElement('canvas');
        canvas.width = bitmap.width;
        canvas.height = bitmap.height;
        canvas.getContext('2d').drawImage(bitmap, 0, 0);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
        if (!blob) return file;
        return new File([blob], file.name.replace(/\.(heic|heif)$/i, '.jpg'), { type: 'image/jpeg' });
    } catch {
        return file; // the server converts it instead
    }
}

function reducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
}

function storage() {
    try {
        return window.localStorage;
    } catch {
        return null;
    }
}

const timeFormat = new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' });
const dayFormat = new Intl.DateTimeFormat(undefined, { weekday: 'long', day: 'numeric', month: 'long' });
const dayFormatWithYear = new Intl.DateTimeFormat(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const dateTimeFormat = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });

function localDayKey(iso) {
    const d = new Date(iso);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function dayLabel(iso) {
    const key = localDayKey(iso);
    const today = localDayKey(new Date().toISOString());
    const yesterday = localDayKey(new Date(Date.now() - 86400000).toISOString());
    if (key === today) return 'Today';
    if (key === yesterday) return 'Yesterday';
    const date = new Date(iso);
    return date.getFullYear() === new Date().getFullYear() ? dayFormat.format(date) : dayFormatWithYear.format(date);
}

/* ---------------------------------------------------------------- KaTeX */

let katexPromise = null;

function loadKatex(stylesheet) {
    if (katexPromise) return katexPromise;
    if (stylesheet && !document.querySelector(`link[href="${stylesheet}"]`)) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = stylesheet;
        document.head.appendChild(link);
    }
    katexPromise = import('katex').then((module) => module.default || module);
    return katexPromise;
}

function typesetMath(root, config) {
    const nodes = root.querySelectorAll(`[${config.mathAttribute}]:not([data-typeset])`);
    if (nodes.length === 0) return null;

    return loadKatex(config.katexStylesheet).then((katex) => {
        nodes.forEach((el) => {
            if (el.hasAttribute('data-typeset')) return;
            const tex = el.textContent;
            el.setAttribute('data-typeset', '1');
            try {
                katex.render(tex, el, {
                    displayMode: el.getAttribute(config.mathAttribute) === 'display',
                    throwOnError: false,
                    errorColor: 'var(--danger-tone)',
                    strict: 'ignore',
                    trust: false,
                });
            } catch {
                el.textContent = tex;
            }
        });
    }).catch(() => {
        // Without KaTeX the TeX source stays readable as text.
    });
}

document.addEventListener('alpine:init', () => {
    registerForms(Alpine);

    // The site's lightbox (resources/js/lightbox.js), fed by the room instead of reading links from the page.
    Alpine.data('chatLightbox', () => ({
        ...lightbox(),
        init() {},
        openWith({ items, index, trigger }) {
            if (!items || items.length === 0) return;
            this.items = items;
            this.show(index, trigger);
        },
    }));

    Alpine.data('chatRoom', (options) => ({
        channelId: options.channelId,
        channelSlug: options.channelSlug,
        config: options.config,
        viewer: options.viewer,
        notices: options.notices,
        canModerate: Boolean(options.canModerate),

        messages: [],
        channels: [],
        panelTab: 'about',
        memberSearch: '',
        memberMenu: null,
        suspending: null,
        attachments: [],
        dragging: false,
        dragDepth: 0,
        pins: [],
        members: [],
        online: [],
        cursor: 0,
        since: null,
        hasOlder: false,
        unreadFrom: null,
        lastReadSent: 0,

        draft: '',
        replyTo: null,
        sending: false,
        sendError: '',
        notice: '',
        noticeTimer: null,
        localSeq: 0,

        editing: null,
        deleting: null,
        openKey: null,
        hoverKey: null,
        focusKey: null,
        pressTimer: null,
        sentCount: 0,
        formattingOpen: false,
        reporting: false,
        reportTarget: null,
        reportReason: '',
        reportError: '',
        reportSending: false,

        panelOpen: false,
        isWide: false,
        wideQuery: null,
        searchOpen: false,
        searchTerm: '',
        searchResults: [],
        searchStatus: '',
        searchSeq: 0,

        syncing: false,
        loadingOlder: false,
        paused: false,
        offline: false,
        expired: false,
        accessLost: Boolean(options.accessRevoked),
        attempt: 0,
        retryTimer: null,
        pollAttribute: null,
        atBottom: true,
        newCount: 0,
        observer: null,
        typesetFrame: null,
        focusPending: null,
        settlingUntil: 0,

        init() {
            if (this.accessLost) return;

            const initial = options.initial || {};
            this.messages = (initial.messages || []).map((m) => this.normalise(m));
            this.channels = initial.channels || [];
            this.pins = initial.pins || [];
            this.members = initial.members || [];
            this.online = initial.online || [];
            this.sentCount = Number(storage()?.getItem(this.config.sentCountKey)) || 0;
            this.cursor = initial.cursor || 0;
            this.since = initial.since || new Date().toISOString();
            this.hasOlder = Boolean(initial.hasOlder);
            this.lastReadSent = initial.lastRead || 0;

            const firstUnread = this.messages.find((m) => m.id > (initial.lastRead || 0) && !m.mine);
            this.unreadFrom = firstUnread ? firstUnread.id : null;

            this.setupLayout();
            this.restoreDraft();

            // wire:poll runs this Livewire JS action (a plain Alpine method would be sent
            // to the server as a component method instead).
            this.$wire.$js('tick', () => this.tick());
            this.pollAttribute = Array.from(this.$el.attributes).map((a) => a.name).find((name) => name.startsWith('wire:poll')) || null;

            this.observer = new MutationObserver(() => this.scheduleTypeset());
            this.observer.observe(this.$refs.scroller, { childList: true, subtree: true });

            this.$nextTick(() => {
                this.grow();
                this.scheduleTypeset();

                if (initial.focus) {
                    // A permalink: settle on the linked message, then catch up on anything
                    // newer without the poll pulling the reader down to the bottom.
                    this.atBottom = false;
                    this.jumpTo(initial.focus, { replaceUrl: true, instant: true });
                    this.sync();
                    return;
                }

                if (this.unreadFrom) {
                    const divider = this.$refs.scroller.querySelector('[data-unread-divider]');
                    if (divider) {
                        divider.scrollIntoView({ block: 'center' });
                        this.onScroll();
                        return;
                    }
                }
                this.scrollToBottom(false);
            });

            this.listenForBroadcasts();
        },

        destroy() {
            clearTimeout(this.retryTimer);
            clearTimeout(this.noticeTimer);
            clearTimeout(this.pressTimer);
            this.observer?.disconnect();
        },

        /* ---------------------------------------------------------- layout */

        setupLayout() {
            this.wideQuery = window.matchMedia(WIDE_QUERY);
            this.isWide = this.wideQuery.matches;
            this.panelOpen = this.isWide && storage()?.getItem(PANEL_STORAGE_KEY) !== 'closed';
            this.wideQuery.addEventListener('change', (event) => {
                this.isWide = event.matches;
                this.panelOpen = this.isWide && storage()?.getItem(PANEL_STORAGE_KEY) !== 'closed';
            });
        },

        get currentChannel() {
            return this.channels.find((c) => c.current) || null;
        },

        get remaining() {
            return this.config.bodyMaxLength - this.draft.length;
        },

        /** Something to send, nothing still uploading, and within the length limit (checked again on the server). */
        get canSend() {
            if (this.sending || this.expired || this.accessLost) return false;
            if (this.attachments.some((a) => a.state === STATE_UPLOADING)) return false;
            const ready = this.attachments.filter((a) => a.state === STATE_READY).length;
            return (this.draft.trim() !== '' || ready > 0) && this.remaining >= 0;
        },

        /** "200 characters left" from 1,800 characters, "12 characters over" past the limit. */
        get countText() {
            const left = this.remaining;
            const n = Math.abs(left).toLocaleString();
            return (left < 0 ? this.config.copy.countOver : this.config.copy.countLeft).replace(':count', n);
        },

        /** The Enter / Shift+Enter hint, until this browser has sent a few messages. */
        get hintShown() {
            return this.sentCount < this.config.hintHiddenAfterSends;
        },

        /** Members whose read cursor moved in the last five minutes (the poll keeps it fresh). */
        get onlineMembers() {
            const ids = new Set(this.online);
            return this.members.filter((member) => ids.has(member.id));
        },

        isOnline(member) {
            return this.online.includes(member.id);
        },

        /** The Members tab: everyone, or those whose name or affiliation matches the search. */
        get filteredMembers() {
            const term = this.memberSearch.trim().toLowerCase();
            if (term === '') return this.members;
            return this.members.filter((m) => `${m.name} ${m.affiliation || ''}`.toLowerCase().includes(term));
        },

        togglePanel() {
            this.panelOpen = !this.panelOpen;
            if (this.isWide) storage()?.setItem(PANEL_STORAGE_KEY, this.panelOpen ? 'open' : 'closed');
            if (this.panelOpen) this.$nextTick(() => this.$root.querySelector('[data-panel-close]')?.focus());
        },

        selectTab(tab, { focus = false } = {}) {
            if (!this.config.tabs.includes(tab)) return;
            this.panelTab = tab;
            this.memberMenu = null;
            if (focus) this.$nextTick(() => this.$root.querySelector(`[data-tab="${tab}"]`)?.focus());
        },

        /** Arrow keys, Home and End move between the panel's tabs (automatic activation). */
        onTabKeydown(event) {
            const tabs = this.config.tabs;
            const index = tabs.indexOf(this.panelTab);
            const next = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: tabs.length - 1 }[event.key];
            if (next === undefined) return;
            event.preventDefault();
            this.selectTab(tabs[(next + tabs.length) % tabs.length], { focus: true });
        },

        closePanel() {
            if (!this.panelOpen) return;
            this.panelOpen = false;
            if (this.isWide) storage()?.setItem(PANEL_STORAGE_KEY, 'closed');
            this.$root.querySelector('[data-panel-toggle]')?.focus();
        },

        /**
         * "Members" in the header: open the side panel (a drawer below 1024px) on its Members
         * tab and put focus on that tab. Nothing scrolls but the panel's own body.
         */
        showMembers() {
            this.panelOpen = true;
            if (this.isWide) storage()?.setItem(PANEL_STORAGE_KEY, 'open');
            this.selectTab('members', { focus: true });
        },

        toggleMemberMenu(member) {
            this.suspending = null;
            this.memberMenu = this.memberMenu === member.id ? null : member.id;
            if (this.memberMenu) this.$nextTick(() => this.$root.querySelector(`#chat-member-menu-${member.id} a:not([style*="display: none"]), #chat-member-menu-${member.id} button:not([style*="display: none"])`)?.focus());
        },

        closeMemberMenu(member) {
            this.memberMenu = null;
            this.suspending = null;
            this.$root.querySelector(`[data-member-id="${member.id}"] [data-member-menu-button]`)?.focus();
        },

        askSuspend(member) {
            this.memberMenu = null;
            this.suspending = member.id;
            this.$nextTick(() => this.$root.querySelector(`[data-member-id="${member.id}"] [data-suspend-yes]`)?.focus());
        },

        cancelSuspend(member) {
            this.closeMemberMenu(member);
        },

        async suspend(member) {
            try {
                const result = await this.$wire.suspendMember(member.id);
                if (result && result.ok) {
                    this.members = result.members || this.members.filter((m) => m.id !== member.id);
                    this.suspending = null;
                    this.say(result.notice);
                } else {
                    this.handleResult(result, () => {});
                    if (result?.message) this.say(result.message);
                }
            } catch (error) {
                this.handleFailure(error);
            }
        },

        toggleFormatting() {
            this.formattingOpen = !this.formattingOpen;
        },

        onEscape() {
            if (this.reporting) return; // the dialog handles its own escape
            if (document.querySelector('dialog[open]')) return; // the lightbox closes itself
            if (this.memberMenu || this.suspending) {
                this.memberMenu = null;
                this.suspending = null;
                return;
            }
            if (this.formattingOpen) {
                this.formattingOpen = false;
                this.$root.querySelector('[data-formatting-toggle]')?.focus();
                return;
            }
            if (this.searchOpen) {
                this.closeSearch();
                return;
            }
            if (this.panelOpen && !this.isWide) this.closePanel();
        },

        /* ---------------------------------------------------------- rendering */

        get days() {
            const sorted = [...this.messages].sort((a, b) => {
                if (a.id && b.id) return a.id - b.id;
                if (a.id && !b.id) return -1;
                if (!a.id && b.id) return 1;
                return a.local - b.local;
            });

            const days = [];
            let day = null;
            let run = null;
            let previous = null;

            for (const m of sorted) {
                const dayKey = localDayKey(m.created_at);
                if (!day || day.key !== dayKey) {
                    day = { key: dayKey, label: dayLabel(m.created_at), runs: [] };
                    days.push(day);
                    run = null;
                }

                const gap = previous ? (new Date(m.created_at) - new Date(previous.created_at)) / 1000 : Infinity;
                const sameRun = run && previous && previous.user_id === m.user_id && gap <= this.config.groupWindowSeconds && m.id !== this.unreadFrom;

                if (!sameRun) {
                    run = { key: m.key, mine: m.mine, author: m.author, affiliation: m.affiliation, initials: m.initials || '', messages: [] };
                    day.runs.push(run);
                }

                run.messages.push(m);
                previous = m;
            }

            return days;
        },

        formatTime(iso) {
            return timeFormat.format(new Date(iso));
        },

        formatDateTime(iso) {
            return dateTimeFormat.format(new Date(iso));
        },

        permalink(id) {
            const url = new URL(window.location.href);
            url.search = '';
            url.hash = '';
            url.searchParams.set(this.config.messageParam, String(id));
            return url.toString();
        },

        normalise(message, { arriving = false } = {}) {
            return { attachments: [], ...message, key: message.client_uuid || String(message.id), state: null, error: null, local: ++this.localSeq, arriving };
        },

        /** Let a just-arrived message rise into place once, then drop the class so a re-render never replays it. */
        settleArrival(message) {
            setTimeout(() => {
                message.arriving = false;
            }, ARRIVAL_MS);
        },

        scheduleTypeset() {
            cancelAnimationFrame(this.typesetFrame);
            this.typesetFrame = requestAnimationFrame(() => {
                const typeset = typesetMath(this.$refs.scroller, this.config);
                // Typeset maths changes heights: keep a just-linked message centred.
                if (typeset && this.focusPending) typeset.then(() => this.recentre());
            });
        },

        recentre() {
            if (!this.focusPending) return;
            this.$refs.scroller.querySelector(`#message-${this.focusPending}`)?.scrollIntoView({ block: 'center', behavior: 'auto' });
        },

        say(text) {
            this.notice = text;
            clearTimeout(this.noticeTimer);
            this.noticeTimer = setTimeout(() => {
                this.notice = '';
            }, 4000);
        },

        /* ---------------------------------------------------------- jumping */

        jumpTo(id, { replaceUrl = false, instant = false } = {}) {
            const el = this.$refs.scroller.querySelector(`#message-${id}`);

            if (!el) {
                window.location.assign(this.permalink(id));
                return;
            }

            this.closeSearch();
            this.atBottom = false; // the scroll event recomputes this once the message is in view
            // While the scroller travels, passing the top must not pull in earlier history.
            this.settlingUntil = Date.now() + 1500;
            el.scrollIntoView({ block: 'center', behavior: instant || reducedMotion() ? 'auto' : 'smooth' });
            el.focus({ preventScroll: true });
            this.highlight(el);
            this.focusPending = id;
            setTimeout(() => {
                if (this.focusPending === id) this.focusPending = null;
            }, this.config.highlightMs);

            if (replaceUrl) {
                try {
                    window.history.replaceState(window.history.state, '', window.location.pathname);
                } catch {
                    // Leave the permalink in place.
                }
            }
        },

        highlight(el) {
            el.classList.remove('is-fading');
            el.classList.add('is-highlighted');

            if (reducedMotion()) {
                setTimeout(() => el.classList.remove('is-highlighted'), this.config.highlightMs);
                return;
            }

            setTimeout(() => {
                el.classList.add('is-fading');
                el.classList.remove('is-highlighted');
            }, 600);
            setTimeout(() => el.classList.remove('is-fading'), this.config.highlightMs);
        },

        async copyLink(message) {
            const link = this.permalink(message.id);
            try {
                await navigator.clipboard.writeText(link);
                this.say(this.config.copy.linkCopied);
            } catch {
                window.prompt('Copy this link', link);
            }
            this.closeActions(message);
        },

        /* ---------------------------------------------------------- keyboard and touch */

        onKeydown(event) {
            const target = event.target;
            if (target.matches('textarea, input, select')) return;

            const items = Array.from(this.$refs.scroller.querySelectorAll('[data-chat-message]'));
            const current = target.closest('[data-chat-message]');

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                const down = event.key === 'ArrowDown';
                const index = current ? items.indexOf(current) : (down ? -1 : items.length);
                const next = items[index + (down ? 1 : -1)];
                if (next) {
                    event.preventDefault();
                    next.focus();
                    next.scrollIntoView({ block: 'nearest' });
                }
                return;
            }

            if (event.key === 'Enter' && current && target === current) {
                event.preventDefault();
                this.openActions(current.dataset.clientUuid || current.dataset.messageId, current);
                return;
            }

            if (event.key === 'Escape' && current) {
                event.preventDefault();
                event.stopPropagation();
                this.openKey = null;
                this.deleting = null;
                current.focus();
            }
        },

        toolbarShown(message) {
            if (message.deleted || !message.id || this.expired || this.accessLost) return false;
            if (this.editing && this.editing.key === message.key) return false;
            return this.openKey === message.key || this.hoverKey === message.key || this.focusKey === message.key;
        },

        openActions(key, element) {
            const message = this.messages.find((m) => m.key === key || String(m.id) === String(key));
            if (!message || message.deleted || !message.id) return;
            this.openKey = message.key;
            // Wait a frame after Alpine renders: the toolbar may only just have been shown.
            this.$nextTick(() => requestAnimationFrame(() => {
                const toolbar = (element || this.$refs.scroller.querySelector(`#message-${message.id}`))?.querySelector('[data-toolbar] button:not([style*="display: none"])');
                toolbar?.focus();
            }));
        },

        /** Left and Right move between the visible buttons of a message's toolbar. */
        toolbarStep(event, step) {
            const buttons = Array.from(event.currentTarget.querySelectorAll('button')).filter((b) => b.offsetParent !== null);
            const index = buttons.indexOf(document.activeElement);
            buttons[(index + step + buttons.length) % buttons.length]?.focus();
        },

        closeActions(message) {
            if (this.openKey !== message?.key) return;
            this.openKey = null;
        },

        pressStart(message) {
            clearTimeout(this.pressTimer);
            this.pressTimer = setTimeout(() => {
                this.openKey = message.key;
            }, this.config.longPressMs);
        },

        pressEnd() {
            clearTimeout(this.pressTimer);
        },

        /* ---------------------------------------------------------- search */

        toggleSearch() {
            this.searchOpen ? this.closeSearch() : this.openSearch();
        },

        openSearch() {
            this.searchOpen = true;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        closeSearch() {
            if (!this.searchOpen) return;
            this.searchOpen = false;
            this.searchResults = [];
            this.searchStatus = '';
            this.$root.querySelector('[data-search-toggle]')?.focus();
        },

        async runSearch() {
            const term = this.searchTerm.trim();
            const seq = ++this.searchSeq;

            if (term.length < this.config.searchMinLength) {
                this.searchResults = [];
                this.searchStatus = term === '' ? '' : this.config.copy.searchTooShort;
                return;
            }

            try {
                const result = await this.$wire.search(term);
                if (seq !== this.searchSeq) return;
                this.handleResult(result, () => {
                    this.searchResults = result.messages || [];
                    this.searchStatus = this.searchResults.length === 0
                        ? this.config.copy.searchNone
                        : `${this.searchResults.length} ${this.searchResults.length === 1 ? 'message' : 'messages'}`;
                });
            } catch (error) {
                this.handleFailure(error);
            }
        },

        /* ---------------------------------------------------------- polling */

        tick() {
            if (this.paused || this.offline || this.expired || this.accessLost) return;
            return this.sync();
        },

        async sync() {
            if (this.syncing || this.expired || this.accessLost) return;
            this.syncing = true;

            try {
                const result = await this.$wire.sync(this.cursor, this.since);
                this.handleResult(result, () => this.applySync(result));
                this.recovered();
            } catch (error) {
                this.handleFailure(error);
            } finally {
                this.syncing = false;
            }
        },

        applySync(payload) {
            if (!payload) return;

            const wasAtBottom = this.atBottom;
            let added = 0;

            for (const incoming of payload.messages || []) {
                if (this.merge(incoming, { arriving: true })) added++;
            }

            for (const incoming of payload.changed || []) {
                this.merge(incoming);
            }

            if (payload.cursor) this.cursor = Math.max(this.cursor, payload.cursor);
            if (payload.since) this.since = payload.since;
            if (payload.channels) this.channels = payload.channels;
            if (payload.pins) this.applyPins(payload.pins);
            if (payload.online) this.online = payload.online;

            if (added > 0) {
                this.$nextTick(() => {
                    if (wasAtBottom) {
                        this.scrollToBottom(false);
                    } else {
                        this.newCount += added;
                    }
                });
            }

            this.markReadIfVisible();
        },

        applyPins(pins) {
            this.pins = pins;
            const pinned = new Set(pins.map((p) => p.id));
            for (const m of this.messages) {
                if (m.id) m.pinned = pinned.has(m.id);
            }
        },

        /**
         * Insert or update a message. Returns true when it was new to this client.
         * Matching is by id, then by client uuid (confirming an optimistic send).
         */
        merge(incoming, { arriving = false } = {}) {
            const byId = incoming.id ? this.messages.find((m) => m.id === incoming.id) : null;
            const target = byId || this.messages.find((m) => m.client_uuid && m.client_uuid === incoming.client_uuid);

            if (!target) {
                this.messages.push(this.normalise(incoming, { arriving }));
                if (arriving) this.settleArrival(this.messages[this.messages.length - 1]);
                return true;
            }

            // Never let an older copy overwrite a newer one; deletion always wins.
            if (target.deleted && !incoming.deleted) return false;
            if (!incoming.deleted && target.version > incoming.version) return false;

            // The server's copy of an optimistic send carries signed links: let go of the local previews.
            if (incoming.attachments && target.attachments?.some((a) => a.preview)) {
                target.attachments.forEach((a) => a.preview && URL.revokeObjectURL(a.preview));
            }

            Object.assign(target, incoming, { key: target.key, local: target.local, state: null, error: null });
            return false;
        },

        /* ---------------------------------------------------------- composer */

        draftKey() {
            return `${this.config.draftPrefix}${this.channelId}`;
        },

        restoreDraft() {
            const saved = storage()?.getItem(this.draftKey());
            if (saved) this.draft = saved;
            this.$watch('draft', (value) => {
                const store = storage();
                if (!store) return;
                if (value.trim() === '') store.removeItem(this.draftKey());
                else store.setItem(this.draftKey(), value);
            });
        },

        /**
         * Browsers with CSS field-sizing grow the field themselves; elsewhere set its height
         * from the text, up to 40% of the viewport (the CSS max-height), then it scrolls.
         */
        grow() {
            const el = this.$refs.draft;
            if (!el || CSS.supports?.('field-sizing', 'content')) return;
            const max = window.innerHeight * (this.config.composerMaxViewportPercent / 100);
            el.style.height = 'auto';
            el.style.height = `${Math.min(el.scrollHeight + 2, max)}px`;
        },

        onEnter(event) {
            if (event.shiftKey) return; // Shift+Enter: newline (default behaviour)
            event.preventDefault();
            this.send();
        },

        insertNewline(event) {
            const el = event.target;
            const start = el.selectionStart;
            const value = el.value;
            el.value = value.slice(0, start) + '\n' + value.slice(el.selectionEnd);
            el.selectionStart = el.selectionEnd = start + 1;
            el.dispatchEvent(new Event('input'));
        },

        startReply(message) {
            this.replyTo = { id: message.id, author: message.author, excerpt: message.body.replace(/\s+/g, ' ').slice(0, 140) };
            this.closeActions(message);
            this.$refs.draft?.focus();
        },

        cancelReply() {
            this.replyTo = null;
            this.$refs.draft?.focus();
        },

        async send() {
            const body = this.draft.trim();
            if (this.sending || this.expired || this.accessLost) return;
            if (this.attachments.some((a) => a.state === STATE_UPLOADING)) {
                this.sendError = this.config.copy.attachWait;
                return;
            }

            const files = this.attachments.filter((a) => a.state === STATE_READY);
            if (body === '' && files.length === 0) return;
            if (body.length > this.config.bodyMaxLength) {
                this.sendError = `Messages can be at most ${this.config.bodyMaxLength} characters.`;
                return;
            }

            const undescribed = files.find((a) => a.kind === 'image' && a.alt.trim() === '');
            if (undescribed) {
                undescribed.altMissing = true;
                this.sendError = this.config.copy.attachDescribe;
                this.$nextTick(() => this.$root.querySelector(`#chat-alt-${undescribed.key}`)?.focus());
                return;
            }

            this.sendError = '';
            const reply = this.replyTo;
            const message = this.normalise({
                id: null,
                client_uuid: uuid(),
                user_id: this.viewer.id,
                author: this.viewer.name,
                affiliation: this.viewer.affiliation,
                initials: this.viewer.initials,
                body,
                html: escapeHtml(body),
                created_at: new Date().toISOString(),
                edited_at: null,
                version: 1,
                deleted: false,
                mine: true,
                can_edit: false,
                can_delete: false,
                can_report: false,
                can_pin: false,
                pinned: false,
                reply_to: reply ? { id: reply.id, author: reply.author, excerpt: reply.excerpt, deleted: false } : null,
                // Local previews until the server's copy (with signed links) replaces them.
                attachments: files.map((a) => ({
                    key: a.key, kind: a.kind, name: a.name, type: a.type, extension: a.extension,
                    size_label: a.sizeLabel, alt: a.alt.trim(), preview: a.preview, thumb: null, url: null,
                })),
            });
            message.state = STATE_SENDING;
            message.replyToId = reply ? reply.id : null;
            // The same uuids go with every retry of this message, so files are attached once.
            message.attachmentPayload = files.map((a) => ({ id: a.id, alt: a.alt.trim() }));
            message.arriving = true;
            this.messages.push(message);
            // Work on the reactive copy from here on: a state change on the plain
            // object would not re-render the "sending" / "not sent" flags.
            const tracked = this.messages[this.messages.length - 1];
            this.settleArrival(tracked);
            this.formattingOpen = false;
            this.draft = '';
            this.replyTo = null;
            this.attachments = this.attachments.filter((a) => !files.includes(a));
            this.$nextTick(() => {
                this.grow();
                this.scrollToBottom(false);
                this.$refs.draft?.focus();
            });

            await this.deliver(tracked);
        },

        async retry(message) {
            if (this.sending) return;
            message.state = STATE_SENDING;
            message.error = null;
            await this.deliver(message);
        },

        async deliver(message) {
            this.sending = true;

            try {
                const result = await this.$wire.send(message.body, message.client_uuid, this.cursor, this.since, message.replyToId ?? null, message.attachmentPayload || []);

                if (result && result.ok) {
                    this.applySync(result.sync);
                    this.merge(result.message); // confirms the optimistic entry by client uuid
                    this.countSent();
                    this.recovered();
                } else {
                    this.failSend(message, result);
                }
            } catch (error) {
                message.state = STATE_FAILED;
                message.error = null;
                this.handleFailure(error);
            } finally {
                this.sending = false;
            }
        },

        countSent() {
            this.sentCount += 1;
            try {
                storage()?.setItem(this.config.sentCountKey, String(this.sentCount));
            } catch {
                // The hint simply stays a little longer.
            }
        },

        failSend(message, result) {
            if (result?.error === 'access') {
                this.loseAccess();
                return;
            }

            message.state = STATE_FAILED;
            message.error = result?.message || '';

            if (result?.error === 'rate_limit') {
                this.sendError = result.message;
            }
        },

        /* ---------------------------------------------------------- attachments */

        openPicker() {
            if (!this.config.attachments.enabled) return;
            this.$refs.fileInput?.click();
        },

        pickFiles(event) {
            this.addFiles(Array.from(event.target.files || []));
            event.target.value = '';
        },

        onPaste(event) {
            if (!this.config.attachments.enabled) return;
            const files = Array.from(event.clipboardData?.files || []).filter((f) => f.type.startsWith('image/'));
            if (files.length === 0) return; // plain text pastes as usual
            event.preventDefault();
            this.addFiles(files.map((f, i) => (f.name && f.name !== 'image.png' ? f : new File([f], `pasted-image-${Date.now()}-${i + 1}.${extensionOf(f.type.replace('image/', 'x.')) || 'png'}`, { type: f.type }))));
        },

        hasFiles(event) {
            return this.config.attachments.enabled && Array.from(event.dataTransfer?.types || []).includes('Files');
        },

        onDragEnter(event) {
            if (!this.hasFiles(event)) return;
            event.preventDefault();
            this.dragDepth++;
            this.dragging = true;
        },

        onDragOver(event) {
            if (!this.hasFiles(event)) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        },

        onDragLeave(event) {
            if (!this.hasFiles(event)) return;
            this.dragDepth = Math.max(0, this.dragDepth - 1);
            if (this.dragDepth === 0) this.dragging = false;
        },

        onDrop(event) {
            if (!this.hasFiles(event)) return;
            event.preventDefault();
            this.dragDepth = 0;
            this.dragging = false;
            this.addFiles(Array.from(event.dataTransfer.files || []));
            this.$refs.draft?.focus();
        },

        /** Check type, size and count in the browser (the server checks again, by content), then upload each. */
        async addFiles(files) {
            const limits = this.config.attachments;
            const copy = this.config.copy;
            this.sendError = '';

            for (let file of files) {
                if (this.attachments.length >= limits.maxFiles) {
                    this.sendError = copy.attachTooMany.replace(':max', limits.maxFiles);
                    break;
                }

                let extension = extensionOf(file.name);
                const image = limits.imageExtensions.includes(extension);
                if (!image && !limits.documentExtensions.includes(extension)) {
                    this.sendError = copy.attachUnsupported.replace(':name', file.name);
                    continue;
                }

                const max = image ? limits.imageMaxBytes : limits.documentMaxBytes;
                if (file.size > max) {
                    this.sendError = copy.attachTooLarge.replace(':name', file.name).replace(':max', image ? limits.imageMaxMb : limits.documentMaxMb);
                    continue;
                }

                if (extension === 'heic' || extension === 'heif') {
                    file = await heicToJpeg(file);
                    extension = extensionOf(file.name);
                }

                const canPreview = image && extension !== 'heic' && extension !== 'heif';
                this.attachments.push({
                    key: uuid(),
                    id: null,
                    kind: image ? 'image' : 'document',
                    name: file.name,
                    extension,
                    type: extension.toUpperCase(),
                    sizeLabel: fileSize(file.size),
                    preview: canPreview ? URL.createObjectURL(file) : null,
                    alt: '',
                    altMissing: false,
                    progress: 0,
                    state: STATE_UPLOADING,
                    error: '',
                    xhr: null,
                });
                this.upload(this.attachments[this.attachments.length - 1], file);
            }
        },

        /** Stage one file in the member's temporary area, with progress. */
        upload(item, file) {
            const form = new FormData();
            form.append('file', file);
            const xhr = new XMLHttpRequest();
            item.xhr = xhr;
            xhr.open('POST', this.config.attachments.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) item.progress = Math.min(99, Math.round((event.loaded / event.total) * 100));
            });
            xhr.addEventListener('load', () => {
                item.xhr = null;
                let data = {};
                try {
                    data = JSON.parse(xhr.responseText || '{}');
                } catch {
                    data = {};
                }
                if (xhr.status === 201 && data.upload) {
                    item.id = data.upload.id;
                    item.name = data.upload.name;
                    item.progress = 100;
                    item.state = STATE_READY;
                    return;
                }
                if (xhr.status === 419 || xhr.status === 401) this.onRequestError({ status: xhr.status });
                if (xhr.status === 403) this.loseAccess();
                item.state = STATE_FAILED;
                item.error = data.errors?.file?.[0] || data.message || this.config.copy.attachFailed;
            });
            xhr.addEventListener('error', () => {
                item.xhr = null;
                item.state = STATE_FAILED;
                item.error = this.config.copy.attachFailed;
            });
            xhr.send(form);
        },

        removeAttachment(item) {
            item.xhr?.abort();
            if (item.preview) URL.revokeObjectURL(item.preview);
            this.attachments = this.attachments.filter((a) => a.key !== item.key);
            if (item.id) {
                fetch(this.config.attachments.discardUrl.replace('__uuid__', item.id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                }).catch(() => {
                    // Anything left behind is pruned by the scheduler after a day.
                });
            }
            this.$refs.draft?.focus();
        },

        imagesOf(message) {
            return (message.attachments || []).filter((a) => a.kind === 'image');
        },

        documentsOf(message) {
            return (message.attachments || []).filter((a) => a.kind === 'document');
        },

        /** The site's lightbox, with the message's images (the medium size; the original on demand). */
        openLightbox(message, index, trigger) {
            const items = this.imagesOf(message).map((a) => ({
                src: a.medium || a.preview,
                srcset: '',
                alt: a.alt,
                caption: a.alt,
                meta: `${message.author} · ${this.formatDateTime(message.created_at)}`,
                note: '',
                credit: '',
                treatment: '',
                width: a.width || null,
                height: a.height || null,
            }));
            window.dispatchEvent(new CustomEvent('chat-lightbox', { detail: { items, index, trigger } }));
        },

        /* ---------------------------------------------------------- editing */

        startEdit(message) {
            this.deleting = null;
            this.openKey = null;
            this.editing = { key: message.key, id: message.id, body: message.body, version: message.version, error: '', stale: false, saving: false };
        },

        cancelEdit() {
            const key = this.editing?.key;
            this.editing = null;
            if (key) this.$nextTick(() => this.$refs.scroller.querySelector(`[data-client-uuid="${key}"], #message-${key}`)?.focus());
        },

        async saveEdit() {
            if (!this.editing || this.editing.saving) return;
            const body = this.editing.body.trim();
            if (body === '') {
                this.editing.error = 'A message cannot be empty.';
                return;
            }

            this.editing.saving = true;
            this.editing.error = '';

            try {
                const result = await this.$wire.edit(this.editing.id, body, this.editing.version);

                if (result && result.ok) {
                    this.merge(result.message);
                    this.editing = null;
                } else if (result?.error === 'conflict') {
                    this.editing.error = result.message;
                    this.editing.stale = true;
                } else if (result?.error === 'removed') {
                    const target = this.messages.find((m) => m.key === this.editing.key);
                    if (target) Object.assign(target, { deleted: true, body: '', html: '', can_edit: false, can_delete: false, can_report: false, can_pin: false, pinned: false });
                    this.editing = null;
                    this.say(result.message);
                } else {
                    this.handleResult(result, () => {});
                    if (this.editing) this.editing.error = result?.message || 'The change could not be saved.';
                }
            } catch (error) {
                if (this.editing) this.editing.error = 'The change could not be saved. Check your connection and try again.';
                this.handleFailure(error);
            } finally {
                if (this.editing) this.editing.saving = false;
            }
        },

        async reloadMessage(message) {
            try {
                const result = await this.$wire.reload(message.id);
                if (result && result.ok) {
                    const target = this.messages.find((m) => m.key === message.key);
                    if (target) Object.assign(target, result.message, { key: target.key, local: target.local, state: null, error: null });
                    if (this.editing && this.editing.key === message.key) {
                        this.editing = { ...this.editing, body: result.message.body, version: result.message.version, error: '', stale: false };
                    }
                } else {
                    this.handleResult(result, () => {});
                }
            } catch (error) {
                this.handleFailure(error);
            }
        },

        /** The trash button asks first, inside the toolbar, which stays open while it asks. */
        confirmDelete(message) {
            this.openKey = message.key;
            this.deleting = message.key;
        },

        cancelDelete(message) {
            this.deleting = null;
            this.$nextTick(() => this.$refs.scroller.querySelector(`#message-${message.id} [data-delete]`)?.focus());
        },

        async remove(message) {
            this.deleting = null;
            this.openKey = null;
            try {
                const result = await this.$wire.remove(message.id);
                if (result && result.ok) {
                    this.merge(result.message);
                    this.pins = this.pins.filter((p) => p.id !== message.id);
                } else {
                    this.handleResult(result, () => {});
                }
            } catch (error) {
                this.handleFailure(error);
            }
        },

        /* ---------------------------------------------------------- pins */

        async pin(message) {
            await this.setPinned(message, true);
        },

        async unpin(message) {
            await this.setPinned(message, false);
        },

        async setPinned(message, pinned) {
            this.openKey = null;
            try {
                const result = pinned ? await this.$wire.pin(message.id) : await this.$wire.unpin(message.id);
                if (result && result.ok) {
                    this.merge(result.message);
                    this.applyPins(result.pins || []);
                } else {
                    this.handleResult(result, () => {});
                    if (result?.message) this.say(result.message);
                }
            } catch (error) {
                this.handleFailure(error);
            }
        },

        /* ---------------------------------------------------------- reports */

        startReport(message) {
            this.openKey = null;
            this.reportTarget = message;
            this.reportReason = '';
            this.reportError = '';
            this.reporting = true;
        },

        cancelReport() {
            this.reporting = false;
            this.reportTarget = null;
        },

        async submitReport() {
            if (!this.reportTarget || this.reportSending) return;
            const reason = this.reportReason.trim();
            if (reason === '') {
                this.reportError = 'Please say briefly why you are reporting this message.';
                return;
            }

            this.reportSending = true;
            this.reportError = '';

            try {
                const result = await this.$wire.report(this.reportTarget.id, reason);
                if (result && result.ok) {
                    this.say(result.notice || this.config.copy.reportThanks);
                    this.cancelReport();
                } else if (result?.error === 'removed') {
                    this.reportError = result.message;
                } else {
                    this.handleResult(result, () => {});
                    this.reportError = result?.message || 'The report could not be sent.';
                }
            } catch (error) {
                this.reportError = 'The report could not be sent. Check your connection and try again.';
                this.handleFailure(error);
            } finally {
                this.reportSending = false;
            }
        },

        /* ---------------------------------------------------------- scrolling */

        onScroll() {
            const el = this.$refs.scroller;
            if (!el) return;

            this.atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < BOTTOM_THRESHOLD;

            if (this.atBottom) {
                this.newCount = 0;
                this.markReadIfVisible();
            }

            if (el.scrollTop < TOP_THRESHOLD && this.hasOlder && !this.loadingOlder && Date.now() > this.settlingUntil) {
                this.loadOlder();
            }
        },

        scrollToBottom(smooth) {
            const el = this.$refs.scroller;
            if (!el) return;
            el.scrollTo({ top: el.scrollHeight, behavior: smooth && !reducedMotion() ? 'smooth' : 'auto' });
            this.atBottom = true;
            this.newCount = 0;
            this.markReadIfVisible();
        },

        async loadOlder() {
            const first = this.messages.filter((m) => m.id).sort((a, b) => a.id - b.id)[0];
            if (!first || this.loadingOlder) return;

            this.loadingOlder = true;
            const el = this.$refs.scroller;
            const previousHeight = el.scrollHeight;
            const previousTop = el.scrollTop;

            try {
                const result = await this.$wire.loadOlder(first.id);
                if (result && result.ok) {
                    for (const incoming of result.messages || []) this.merge(incoming);
                    this.hasOlder = Boolean(result.hasOlder);
                    // Keep the reader's place: shift by however much was prepended.
                    this.$nextTick(() => {
                        el.scrollTop = previousTop + (el.scrollHeight - previousHeight);
                    });
                } else {
                    this.handleResult(result, () => {});
                }
            } catch (error) {
                this.handleFailure(error);
            } finally {
                this.loadingOlder = false;
            }
        },

        markReadIfVisible() {
            if (!this.atBottom || this.expired || this.accessLost) return;
            const newest = this.messages.filter((m) => m.id).reduce((max, m) => Math.max(max, m.id), 0);
            if (newest <= this.lastReadSent) return;

            this.lastReadSent = newest;
            this.$wire.markRead(newest).then((result) => this.handleResult(result, () => {})).catch(() => {
                // The cursor only ever moves forward; a lost call is retried by the next one.
            });
        },

        /* ---------------------------------------------------------- failures */

        handleResult(result, onOk) {
            if (!result) return;
            if (result.ok) {
                onOk();
                return;
            }
            if (result.error === 'access') {
                this.loseAccess();
                return;
            }
            if (result.error === 'rate_limit') {
                this.sendError = result.message;
            }
        },

        /**
         * A rejected Livewire promise: `status` is null when Livewire skipped the
         * request (another was in flight), 419/401 when the session is gone, 403
         * when access was taken away, anything else is a network or server fault.
         */
        handleFailure(error) {
            const status = error && typeof error === 'object' ? error.status : undefined;

            if (status === null || status === undefined) {
                if (error instanceof Error) console.error(error);
                return; // a superseded request, not a failure
            }

            this.onRequestError({ status });
        },

        onRequestError({ status }) {
            if (status === 419 || status === 401) {
                this.expired = true;
                this.pausePolling();
                return;
            }

            if (status === 403) {
                this.loseAccess();
                return;
            }

            // Network error or server fault: pause the regular poll and retry with back-off.
            this.offline = true;
            this.pausePolling();
            const delays = this.config.backoff || [5, 10, 30];
            const delay = delays[Math.min(this.attempt, delays.length - 1)];
            this.attempt++;
            clearTimeout(this.retryTimer);
            this.retryTimer = setTimeout(() => this.reconnect(), delay * 1000);
        },

        async reconnect() {
            if (this.expired || this.accessLost) return;
            this.offline = false; // allow sync() to run; a failure sets it again with a longer delay
            await this.sync();
        },

        recovered() {
            if (this.attempt === 0 && !this.offline) return;
            this.attempt = 0;
            this.offline = false;
            this.resumePolling();
        },

        loseAccess() {
            this.accessLost = true;
            this.pausePolling();
            clearTimeout(this.retryTimer);
        },

        pausePolling() {
            if (this.pollAttribute && this.$el.hasAttribute(this.pollAttribute)) {
                this.$el.removeAttribute(this.pollAttribute);
            }
        },

        resumePolling() {
            if (this.pollAttribute && !this.$el.hasAttribute(this.pollAttribute) && !this.expired && !this.accessLost) {
                this.$el.setAttribute(this.pollAttribute, POLL_EXPRESSION);
            }
        },

        onVisibility() {
            this.paused = document.hidden;
            if (!this.paused && !this.expired && !this.accessLost) {
                this.sync(); // catch up from the cursor as soon as the tab returns
            }
        },

        /* ---------------------------------------------------------- Reverb */

        listenForBroadcasts() {
            if (this.config.transport !== 'broadcast' || !window.Echo) return;

            window.Echo.private(`community.chat.${this.channelId}`)
                .listen('.message.sent', () => this.sync())
                .listen('.message.edited', () => this.sync())
                .listen('.message.deleted', () => this.sync())
                .listen('.message.pinned', () => this.sync())
                .listen('.message.unpinned', () => this.sync());
        },
    }));
});

/*
 | Keep Livewire's own "page expired" / error modals out of the chat: the room
 | shows "Reconnecting…" or "Your session has expired" instead. Every request
 | failure is also announced so the room can react even to failures of calls
 | whose promise it is not awaiting.
 */
document.addEventListener('livewire:init', () => {
    Livewire.interceptRequest(({ onError, onFailure }) => {
        onError(({ response, preventDefault }) => {
            if (!document.querySelector('[data-chat-room]')) return;
            if ([401, 403, 419].includes(response.status) || response.status >= 500) {
                preventDefault();
                window.dispatchEvent(new CustomEvent('chat-request-error', { detail: { status: response.status } }));
            }
        });

        // A network failure (Livewire 4 passes only the error here and rejects the
        // action promise with a null status) counts as 503: pause and reconnect.
        onFailure(() => {
            if (!document.querySelector('[data-chat-room]')) return;
            window.dispatchEvent(new CustomEvent('chat-request-error', { detail: { status: 503 } }));
        });
    });
});
