/*
 | Community chat (members only). Livewire carries requests and ships Alpine;
 | this script renders the conversation and handles the client half of the
 | reliability rules: optimistic sends with a client uuid, a keyset cursor
 | (last seen id) plus a "changed since" cursor, scroll preservation, unread
 | state, tab visibility, and reconnecting with back-off after 419/401/network
 | failures. The server (ChatService) is the source of truth for ordering.
 */

const STATE_SENDING = 'sending';
const STATE_FAILED = 'failed';
const POLL_EXPRESSION = '$js.tick';
const BOTTOM_THRESHOLD = 48;
const TOP_THRESHOLD = 80;

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

const timeFormat = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' });
const dayFormat = new Intl.DateTimeFormat(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

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
    return dayFormat.format(new Date(iso));
}

document.addEventListener('alpine:init', () => {
    Alpine.data('chatRoom', (options) => ({
        channelId: options.channelId,
        channelSlug: options.channelSlug,
        config: options.config,
        viewer: options.viewer,
        notices: options.notices,

        messages: [],
        channels: [],
        cursor: 0,
        since: null,
        hasOlder: false,
        unreadFrom: null,
        lastReadSent: 0,

        draft: '',
        sending: false,
        sendError: '',
        notice: '',
        localSeq: 0,

        editing: null,
        deleting: null,
        reporting: false,
        reportTarget: null,
        reportReason: '',
        reportError: '',
        reportSending: false,

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

        init() {
            if (this.accessLost) return;

            const initial = options.initial || {};
            this.messages = (initial.messages || []).map((m) => this.normalise(m));
            this.channels = initial.channels || [];
            this.cursor = initial.cursor || 0;
            this.since = initial.since || new Date().toISOString();
            this.hasOlder = Boolean(initial.hasOlder);
            this.lastReadSent = initial.lastRead || 0;

            const firstUnread = this.messages.find((m) => m.id > (initial.lastRead || 0) && !m.mine);
            this.unreadFrom = firstUnread ? firstUnread.id : null;

            // wire:poll runs this Livewire JS action (a plain Alpine method would be sent
            // to the server as a component method instead).
            this.$wire.$js('tick', () => this.tick());
            this.pollAttribute = Array.from(this.$el.attributes).map((a) => a.name).find((name) => name.startsWith('wire:poll')) || null;

            this.$nextTick(() => {
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
        },

        /* ---------------------------------------------------------- rendering */

        get groups() {
            const sorted = [...this.messages].sort((a, b) => {
                if (a.id && b.id) return a.id - b.id;
                if (a.id && !b.id) return -1;
                if (!a.id && b.id) return 1;
                return a.local - b.local;
            });

            const groups = [];
            let current = null;

            for (const m of sorted) {
                const day = localDayKey(m.created_at);
                if (!current || current.day !== day) {
                    current = { day, label: dayLabel(m.created_at), messages: [] };
                    groups.push(current);
                }
                current.messages.push(m);
            }

            return groups;
        },

        formatTime(iso) {
            return timeFormat.format(new Date(iso));
        },

        channelUrl(channel) {
            return window.location.pathname.replace(/\/[^/]*$/, '/' + encodeURIComponent(channel.slug));
        },

        normalise(message) {
            return { ...message, key: message.client_uuid || String(message.id), state: null, error: null, local: ++this.localSeq };
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

        /* ---------------------------------------------------------- sending */

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

        async send() {
            const body = this.draft.trim();
            if (body === '' || this.sending || this.expired || this.accessLost) return;
            if (body.length > this.config.bodyMaxLength) {
                this.sendError = `Messages can be at most ${this.config.bodyMaxLength} characters.`;
                return;
            }

            this.sendError = '';
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
            });
            message.state = STATE_SENDING;
            this.messages.push(message);
            this.draft = '';
            this.$nextTick(() => {
                this.scrollToBottom(false);
                this.$refs.draft?.focus();
            });

            await this.deliver(message);
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
                const result = await this.$wire.send(message.body, message.client_uuid, this.cursor, this.since);

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
            this.editing = { key: message.key, id: message.id, body: message.body, version: message.version, error: '', stale: false, saving: false };
        },

        cancelEdit() {
            this.editing = null;
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
                    if (target) Object.assign(target, { deleted: true, body: '', html: '', can_edit: false, can_delete: false, can_report: false });
                    this.editing = null;
                    this.notice = result.message;
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
            try {
                const result = await this.$wire.remove(message.id);
                if (result && result.ok) {
                    this.merge(result.message);
                } else {
                    this.handleResult(result, () => {});
                }
            } catch (error) {
                this.handleFailure(error);
            }
        },

        /* ---------------------------------------------------------- reports */

        startReport(message) {
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
                    this.notice = result.notice || 'Thank you. An administrator will look at this message.';
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

            if (el.scrollTop < TOP_THRESHOLD && this.hasOlder && !this.loadingOlder) {
                this.loadOlder();
            }
        },

        scrollToBottom(smooth) {
            const el = this.$refs.scroller;
            if (!el) return;
            el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
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
                .listen('.message.deleted', () => this.sync());
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

        onFailure(({ preventDefault }) => {
            if (!document.querySelector('[data-chat-room]')) return;
            preventDefault();
            window.dispatchEvent(new CustomEvent('chat-request-error', { detail: { status: 503 } }));
        });
    });
});
