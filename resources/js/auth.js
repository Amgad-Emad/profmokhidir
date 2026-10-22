/*
 | The sign-in screens (App\Constants\AuthScreens). Four small Alpine components:
 |
 |   authForm       one submit at a time; the primary pill shows its busy label
 |   authPassword   show/hide toggle, Caps Lock warning, strength by length
 |   authCode       six digit boxes with auto-advance, backspace and paste
 |   authChallenge  switches the two-factor form to a recovery code
 |
 | Nothing here animates: the stylesheet carries the timing.
 */

export function authForm() {
    return {
        submitting: false,

        init() {
            // A page restored from the back-forward cache keeps its DOM, so the button is reset.
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) this.reset();
            });
        },

        submit(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }

            this.submitting = true;

            const button = event.target.querySelector('[data-busy-label]');
            if (!button) return;

            button.dataset.idleLabel = button.textContent;
            button.textContent = button.dataset.busyLabel;
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('aria-disabled', 'true');
        },

        reset() {
            this.submitting = false;

            this.$root.querySelectorAll('[data-busy-label][aria-busy="true"]').forEach((button) => {
                button.textContent = button.dataset.idleLabel ?? button.textContent;
                button.removeAttribute('aria-busy');
                button.removeAttribute('aria-disabled');
            });
        },
    };
}

export function authPassword({ levels = [], warning = '' } = {}) {
    return {
        visible: false,
        caps: false,
        length: 0,

        get type() {
            return this.visible ? 'text' : 'password';
        },

        // The highest level whose minimum length the password has reached (by index: Alpine
        // proxies the objects it hands back, so identity lookups in `levels` would miss).
        get levelIndex() {
            return levels.reduce((current, level, index) => (this.length >= level.from ? index : current), 0);
        },

        get level() {
            return levels[this.levelIndex] ?? null;
        },

        get strength() {
            return levels.length < 2 ? 0 : this.levelIndex / (levels.length - 1);
        },

        get strengthLabel() {
            return this.length > 0 && this.level ? this.level.label : '';
        },

        get capsWarning() {
            return this.caps ? warning : '';
        },

        toggle() {
            this.visible = !this.visible;
        },

        key(event) {
            this.caps = typeof event.getModifierState === 'function' && event.getModifierState('CapsLock');
        },

        input(event) {
            this.length = event.target.value.length;
        },

        blur() {
            this.caps = false;
        },
    };
}

export function authCode({ length = 6 } = {}) {
    return {
        length,
        digits: Array.from({ length }, () => ''),

        init() {
            this.sync();
        },

        box(index) {
            return this.$refs[`box${index}`];
        },

        // A box may receive several characters at once (a one-time-code autofill, or a paste
        // the browser handles itself), so anything longer than one digit is spread along the row.
        input(index, event) {
            const value = event.target.value.replace(/\D/g, '');

            if (value.length > 1) {
                this.fill(index, value);
                return;
            }

            this.digits[index] = value;
            event.target.value = value;
            this.sync();

            if (value !== '' && index < this.length - 1) this.box(index + 1).focus();
        },

        keydown(index, event) {
            if (event.key === 'Backspace' && event.target.value === '' && index > 0) {
                event.preventDefault();
                this.digits[index - 1] = '';
                this.box(index - 1).value = '';
                this.box(index - 1).focus();
                this.sync();
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                this.box(index - 1).focus();
            } else if (event.key === 'ArrowRight' && index < this.length - 1) {
                event.preventDefault();
                this.box(index + 1).focus();
            }
        },

        paste(event) {
            const text = (event.clipboardData ?? window.clipboardData)?.getData('text') ?? '';
            event.preventDefault();
            this.fill(0, text);
        },

        fill(start, text) {
            const characters = text.replace(/\D/g, '').slice(0, this.length - start).split('');

            characters.forEach((character, offset) => {
                this.digits[start + offset] = character;
                this.box(start + offset).value = character;
            });
            this.sync();

            if (characters.length > 0) this.box(Math.min(start + characters.length, this.length - 1)).focus();
        },

        select(event) {
            event.target.select();
        },

        sync() {
            if (this.$refs.value) this.$refs.value.value = this.digits.join('');
        },
    };
}

export function authChallenge({ recovery = false } = {}) {
    return {
        recovery,

        toggle() {
            this.recovery = !this.recovery;

            this.$nextTick(() => {
                const target = this.recovery ? this.$refs.recovery : this.$root.querySelector('.code-field__box');
                target?.focus();
            });
        },
    };
}
