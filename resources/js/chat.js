/*
 | The correspondence room (members only). Livewire carries requests and ships
 | Alpine; this script renders the conversation and handles the client half of
 | the reliability rules: optimistic sends with a client uuid, a keyset cursor
 | (last seen id) plus a "changed since" cursor, scroll preservation, unread
 | state, tab visibility, and reconnecting with back-off after 419/401/network
 | failures. The server (ChatService) is the source of truth for ordering.
 | On top of that: runs of one author within five minutes, replies, permalinks,
 | pins, search, drafts per channel, keyboard travel between messages, and
 | KaTeX typesetting fetched only when a message carries maths.
 */
import './theme';
import { initForms, registerForms } from './forms';

initForms();

const STATE_SENDING = 'sending';
const STATE_FAILED = 'failed';
const POLL_EXPRESSION = '$js.tick';
const BOTTOM_THRESHOLD = 48;
const TOP_THRESHOLD = 80;
const WIDE_QUERY = '(min-width: 1280px)';
const PANEL_STORAGE_KEY = 'chat-panel';
const COMPOSER_MAX_HEIGHT = 192;

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
    Alpine.data('chatRoom', (options) => ({
        channelId: options.channelId,
        channelSlug: options.channelSlug,
        config: options.config,
        viewer: options.viewer,
        notices: options.notices,
        canModerate: Boolean(options.canModerate),

        messages: [],
        channels: [],
        pins: [],
        members: [],
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
        reporting: false,
        reportTarget: null,
        reportReason: '',
        reportError: '',
        reportSending: false,

        mobileView: 'conversation',
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

        togglePanel() {
            this.panelOpen = !this.panelOpen;
            if (this.isWide) storage()?.setItem(PANEL_STORAGE_KEY, this.panelOpen ? 'open' : 'closed');
            if (this.panelOpen) this.$nextTick(() => this.$root.querySelector('[data-panel-close]')?.focus());
        },

        closePanel() {
            if (!this.panelOpen) return;
            this.panelOpen = false;
            if (this.isWide) storage()?.setItem(PANEL_STORAGE_KEY, 'closed');
            this.$root.querySelector('[data-panel-toggle]')?.focus();
        },

        showChannels() {
            this.mobileView = 'channels';
            try {
                window.history.pushState({ chatView: 'channels' }, '');
            } catch {
                // History is optional.
            }
            this.$nextTick(() => this.$root.querySelector('[data-channels] a[aria-current="page"]')?.focus());
        },

        onPopState() {
            this.mobileView = 'conversation';
        },

        onEscape() {
            if (this.reporting) return; // the dialog handles its own escape
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
                    run = { key: m.key, mine: m.mine, author: m.author, affiliation: m.affiliation, messages: [] };
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

        channelUrl(channel) {
            return window.location.pathname.replace(/\/[^/]*$/, '/' + encodeURIComponent(channel.slug));
        },

        permalink(id) {
            const url = new URL(window.location.href);
            url.search = '';
            url.hash = '';
            url.searchParams.set(this.config.messageParam, String(id));
            return url.toString();
        },

        normalise(message) {
            return { ...message, key: message.client_uuid || String(message.id), state: null, error: null, local: ++this.localSeq };
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
            this.$nextTick(() => {
                const toolbar = (element || this.$refs.scroller.querySelector(`#message-${message.id}`))?.querySelector('[data-toolbar] button:not([style*="display: none"])');
                toolbar?.focus();
            });
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
                if (this.merge(incoming)) added++;
            }

            for (const incoming of payload.changed || []) {
                this.merge(incoming);
            }

            if (payload.cursor) this.cursor = Math.max(this.cursor, payload.cursor);
            if (payload.since) this.since = payload.since;
            if (payload.channels) this.channels = payload.channels;
            if (payload.pins) this.applyPins(payload.pins);

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
        merge(incoming) {
            const byId = incoming.id ? this.messages.find((m) => m.id === incoming.id) : null;
            const target = byId || this.messages.find((m) => m.client_uuid && m.client_uuid === incoming.client_uuid);

            if (!target) {
                this.messages.push(this.normalise(incoming));
                return true;
            }

            // Never let an older copy overwrite a newer one; deletion always wins.
            if (target.deleted && !incoming.deleted) return false;
            if (!incoming.deleted && target.version > incoming.version) return false;

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

        grow() {
            const el = this.$refs.draft;
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = `${Math.min(el.scrollHeight, COMPOSER_MAX_HEIGHT)}px`;
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
            if (body === '' || this.sending || this.expired || this.accessLost) return;
            if (body.length > this.config.bodyMaxLength) {
                this.sendError = `Messages can be at most ${this.config.bodyMaxLength} characters.`;
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
            });
            message.state = STATE_SENDING;
            message.replyToId = reply ? reply.id : null;
            this.messages.push(message);
            // Work on the reactive copy from here on: a state change on the plain
            // object would not re-render the "sending" / "not sent" flags.
            const tracked = this.messages[this.messages.length - 1];
            this.draft = '';
            this.replyTo = null;
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
                const result = await this.$wire.send(message.body, message.client_uuid, this.cursor, this.since, message.replyToId ?? null);

                if (result && result.ok) {
                    this.applySync(result.sync);
                    this.merge(result.message); // confirms the optimistic entry by client uuid
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
