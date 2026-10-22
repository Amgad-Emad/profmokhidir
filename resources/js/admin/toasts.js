import { eventDetail } from './storage';

/*
 | The toast stack. Confirmations sit in a polite live region, errors in an
 | assertive one. A toast waits while it is hovered or focused, so the Undo
 | button cannot vanish under the pointer; at most three are shown.
 */
let counter = 0;

export function adminToasts({ initial = null } = {}) {
    return {
        toasts: [],

        init() {
            if (initial) this.push({ detail: initial });
        },

        get polite() {
            return this.toasts.filter((toast) => toast.tone !== 'error');
        },

        get urgent() {
            return this.toasts.filter((toast) => toast.tone === 'error');
        },

        push(event) {
            const data = event && event.detail !== undefined ? eventDetail(event) : event;
            if (!data || !data.message) return;

            const toast = {
                id: ++counter,
                message: data.message,
                tone: data.tone || 'success',
                undo: data.undo || null,
                remaining: (data.seconds || 6) * 1000,
                started: 0,
                timer: null,
            };

            this.toasts = this.toasts.filter((existing) => existing.message !== toast.message).slice(-2);
            this.toasts.push(toast);
            this.start(toast);
        },

        start(toast) {
            toast.started = Date.now();
            clearTimeout(toast.timer);
            toast.timer = setTimeout(() => this.dismiss(toast), toast.remaining);
        },

        hold(toast) {
            clearTimeout(toast.timer);
            toast.remaining = Math.max(1500, toast.remaining - (Date.now() - toast.started));
        },

        release(toast) {
            this.start(toast);
        },

        dismiss(toast) {
            clearTimeout(toast.timer);
            this.toasts = this.toasts.filter((existing) => existing.id !== toast.id);
        },

        undo(toast) {
            const component = window.Livewire?.find(toast.undo?.component);
            if (component) component.call(toast.undo.method, ...(toast.undo.args ?? []));
            this.dismiss(toast);
        },
    };
}
