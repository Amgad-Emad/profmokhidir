/*
 | The sign-in screens (App\Constants\AuthScreens). Two small Alpine components (the
 | password and code fields are the shared formPassword and formOtp in forms.js):
 |
 |   authForm       one submit at a time; the primary pill shows its busy label
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

export function authChallenge({ recovery = false } = {}) {
    return {
        recovery,

        toggle() {
            this.recovery = !this.recovery;

            this.$nextTick(() => {
                const target = this.recovery ? this.$refs.recovery : this.$root.querySelector('.form-otp input');
                target?.focus();
            });
        },
    };
}
