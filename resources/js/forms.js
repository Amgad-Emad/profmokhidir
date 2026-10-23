/*
 | The form-control system (resources/views/components/form, resources/css/forms.css).
 |
 | Page-wide behaviour, started once by initForms():
 |   validation    plain forms are checked on blur (fields the person has typed in) and on
 |                 submit, never while typing; a field already in error is re-checked as it
 |                 changes so the message can go away. The browser's own "Please fill out
 |                 this field" bubble never shows: forms carry novalidate and `invalid` is
 |                 cancelled; the server validates every form again.
 |                 Messages come from the data-msg-* attributes the server printed
 |                 (App\Services\FormFieldService::browserMessages), each naming the fix.
 |   corrected     a field that was invalid and is now valid gets data-corrected (the "valid"
 |                 state), for plain and Livewire forms alike, and keeps it across morphs
 |   counters      "n / max characters", announced only near the limit
 |   autogrow      textareas grow with their text where CSS field-sizing is missing
 |   livewire      after a form's submit action, focus moves to the first field in error and
 |                 the error summary is shown (window event form-invalid)
 |
 | Alpine components, registered by registerForms(Alpine): formPassword, formOtp,
 | formCombobox, formTags, formUpload and formEditor (forms/editor.js).
 */
import formEditor from './forms/editor';

const FIELD = '[data-form-field]';
const CONTROLS = 'input:not([type=hidden]):not([type=file]), select, textarea';
const WARNING_ICON =
    '<svg class="inline-block shrink-0 mt-1 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 4.5l8.5 15h-17z" /><path d="M12 10v4" /><path d="M12 17.25h.01" /></svg>';

/* ------------------------------------------------------------------ helpers */

const isLivewireForm = (form) => !form || form.hasAttribute('wire:submit') || form.closest('[wire\\:id]') !== null;

const fieldOf = (el) => el.closest(FIELD);

const errorOf = (field) => field?.querySelector(':scope > [data-form-error]') ?? null;

function escapeHtml(text) {
    const span = document.createElement('span');
    span.textContent = text;
    return span.innerHTML;
}

function describedBy(el, id, add) {
    const ids = new Set((el.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean));
    add ? ids.add(id) : ids.delete(id);
    ids.size ? el.setAttribute('aria-describedby', [...ids].join(' ')) : el.removeAttribute('aria-describedby');
}

/** Controls that share one message: a radio group, or a single control. */
function groupOf(el) {
    if (el.type === 'radio' && el.name && el.form) {
        return [...el.form.querySelectorAll(`input[type=radio][name="${CSS.escape(el.name)}"]`)];
    }
    return [el];
}

export function showError(el, message) {
    const field = fieldOf(el);
    const error = errorOf(field);
    const controls = groupOf(el);

    if (message) {
        controls.forEach((control) => {
            control.setAttribute('aria-invalid', 'true');
            if (error) describedBy(control, error.id, true);
        });
        if (error) {
            error.innerHTML = `${WARNING_ICON}<span>${escapeHtml(message)}</span>`;
            error.setAttribute('role', 'alert');
        }
        return;
    }

    controls.forEach((control) => {
        control.removeAttribute('aria-invalid');
        if (error) describedBy(control, error.id, false);
    });
    if (error) {
        error.innerHTML = '';
        error.removeAttribute('role');
    }
}

/** The message for a control the browser (or our own range check) finds wrong, or null. */
export function messageFor(el) {
    const data = el.dataset;
    const value = (el.value ?? '').trim();

    if (el.type === 'radio') {
        const group = groupOf(el);
        const required = group.find((radio) => radio.required);
        if (required && !group.some((radio) => radio.checked)) return required.dataset.msgMissing || required.validationMessage;
        return null;
    }

    const v = el.validity;
    if (!v) return null;
    if (v.valueMissing) return data.msgMissing || el.validationMessage;
    if (v.typeMismatch) return data.msgType || el.validationMessage;
    if (v.tooLong) return data.msgLong || el.validationMessage;
    if (v.tooShort) return data.msgShort || el.validationMessage;
    if (v.patternMismatch || v.rangeUnderflow || v.rangeOverflow || v.badInput || v.stepMismatch) {
        return data.msgRange || el.validationMessage;
    }

    if (value !== '' && (data.rangeMin || data.rangeMax)) {
        const number = Number(value);
        const outside =
            !/^\d+$/.test(value) ||
            (data.rangeMin && number < Number(data.rangeMin)) ||
            (data.rangeMax && number > Number(data.rangeMax));
        if (outside) return data.msgRange || null;
    }

    return null;
}

export function validate(el) {
    const message = messageFor(el);
    showError(el, message);
    return message === null;
}

/** Hidden values the browser does not check: a combobox choice, the one-time code. */
function validateHidden(form) {
    const failures = [];

    form.querySelectorAll('[data-combobox] input[type=hidden][data-required]').forEach((hidden) => {
        const search = hidden.closest('[data-combobox]').querySelector('[role=combobox]');
        const message = hidden.value === '' ? search.dataset.msgMissing : null;
        showError(search, message);
        if (message) failures.push(search);
    });

    form.querySelectorAll('[data-otp] input[type=hidden][data-msg-missing]:not([disabled])').forEach((hidden) => {
        const box = hidden.closest('[data-otp]').querySelector('.form-otp input');
        const message = hidden.value.length < hidden.closest('[data-otp]').querySelectorAll('.form-otp input').length ? hidden.dataset.msgMissing : null;
        showError(box, message);
        if (message) failures.push(box);
    });

    return failures;
}

function fillSummary(form, invalid) {
    const summary = form.querySelector('[data-error-summary]');
    if (!summary) return;

    const list = summary.querySelector('[data-summary-list]');
    list.innerHTML = '';
    invalid.forEach((el) => {
        const message = errorOf(fieldOf(el))?.textContent.trim();
        if (!message) return;
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = `#${el.id}`;
        link.textContent = message;
        item.append(link);
        list.append(item);
    });
    summary.hidden = false;
}

export function focusFirstError(root) {
    const control =
        root.querySelector('[aria-invalid="true"]:not([type=hidden])') ??
        root.querySelector(`${FIELD}:has(> [data-form-error]:not(:empty)) :is(${CONTROLS}, .ProseMirror)`);
    if (!control) return false;

    window.dispatchEvent(new CustomEvent('form-invalid', { detail: { root } }));
    // After the click that sent the form has finished moving focus to its button.
    requestAnimationFrame(() => control.focus({ preventScroll: false }));
    return true;
}

/* ------------------------------------------------------------------ page-wide behaviour */

function trackValidation() {
    document.addEventListener('focusout', (event) => {
        const el = event.target;
        if (!el.matches?.(CONTROLS) || !fieldOf(el) || isLivewireForm(el.form)) return;
        if (el.type === 'radio' || el.type === 'checkbox' || el.closest('[data-combobox], [data-tags], [data-otp]')) return;
        // Leaving for the form's own submit button: a message appearing now would push the
        // button down between press and release and swallow the click. The submit check
        // shows the same message a moment later.
        const next = event.relatedTarget;
        if (next?.form === el.form && next.matches?.('button:not([type=button]):not([type=reset]), input[type=submit]')) return;
        // Only a field the person has answered (or one already in error): tabbing past an
        // empty field is not a mistake yet.
        if (!el.hasAttribute('data-touched') && el.getAttribute('aria-invalid') !== 'true') return;
        validate(el);
    });

    const recheck = (event) => {
        const el = event.target;
        if (!el.matches?.(CONTROLS) || isLivewireForm(el.form)) return;
        el.setAttribute('data-touched', '');
        if (el.getAttribute('aria-invalid') === 'true' && messageFor(el) === null) showError(el, null);
    };
    document.addEventListener('input', recheck);
    document.addEventListener('change', recheck);

    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            if (isLivewireForm(form) || !form.querySelector(FIELD) || event.submitter?.hasAttribute('formnovalidate')) return;

            const invalid = [...form.querySelectorAll(CONTROLS)].filter((el) => fieldOf(el) && !el.disabled && !validate(el));
            invalid.push(...validateHidden(form));
            if (invalid.length === 0) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            fillSummary(form, invalid);
            focusFirstError(form);
        },
        true,
    );

    // The browser's own bubbles are replaced by the messages under the fields. Every form is
    // printed with novalidate; a form added later (an Alpine template, a Livewire morph) is
    // quietened here, and an `invalid` event never reaches the browser's bubble.
    const quiet = () => document.querySelectorAll('form').forEach((form) => {
        form.noValidate = true;
    });
    quiet();
    document.addEventListener('livewire:navigated', quiet);
    document.addEventListener('invalid', (event) => event.preventDefault(), true);
}

function trackCorrections() {
    const corrected = new WeakSet();

    new MutationObserver((records) => {
        records.forEach(({ target, attributeName, oldValue }) => {
            if (attributeName === 'aria-invalid') {
                if (oldValue === 'true' && target.getAttribute('aria-invalid') !== 'true') {
                    corrected.add(target);
                    target.setAttribute('data-corrected', '');
                }
            } else if (attributeName === 'data-corrected' && oldValue !== null && !target.hasAttribute('data-corrected')) {
                // A Livewire morph strips what the server did not print; keep the valid state.
                if (corrected.has(target) && target.getAttribute('aria-invalid') !== 'true') target.setAttribute('data-corrected', '');
            }
        });
    }).observe(document.documentElement, { subtree: true, attributes: true, attributeOldValue: true, attributeFilter: ['aria-invalid', 'data-corrected'] });
}

function updateCounter(el) {
    const counter = fieldOf(el)?.querySelector(':scope > [data-form-counter]');
    if (!counter) return;

    const max = Number(counter.dataset.max);
    const count = el.value.length;
    const near = count >= max * 0.8;
    counter.textContent = counter.dataset.format.replace(':count', count).replace(':max', max);
    counter.dataset.near = near ? 'true' : 'false';
    counter.dataset.over = count > max ? 'true' : 'false';
    counter.setAttribute('aria-live', near ? 'polite' : 'off');
}

function grow(el) {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight + 2}px`;
}

function trackTyping() {
    const sizing = CSS.supports?.('field-sizing', 'content');

    document.addEventListener('input', (event) => {
        const el = event.target;
        if (!el.matches?.('input, textarea')) return;
        updateCounter(el);
        if (!sizing && el.matches('textarea[data-autogrow]')) grow(el);
    });

    if (!sizing) {
        const growAll = () => document.querySelectorAll('textarea[data-autogrow]').forEach(grow);
        growAll();
        document.addEventListener('livewire:navigated', growAll);
    }

    document.querySelectorAll(`${FIELD} [data-form-counter]`).forEach((counter) => {
        const el = counter.closest(FIELD).querySelector('input, textarea');
        if (el) updateCounter(el);
    });
}

function trackLivewire() {
    document.addEventListener('livewire:init', () => {
        window.Livewire.interceptAction(({ action, onFinish }) => {
            const form = action.origin?.el;
            if (form?.tagName !== 'FORM') return;
            onFinish(() => requestAnimationFrame(() => requestAnimationFrame(() => focusFirstError(form))));
        });
    });
}

export function initForms() {
    if (window.__formsReady) return;
    window.__formsReady = true;

    trackCorrections();

    const start = () => {
        trackValidation();
        trackTyping();
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
    trackLivewire();
}

/* ------------------------------------------------------------------ Alpine components */

export function formPassword({ levels = [], warning = '' } = {}) {
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

        get strength() {
            return levels.length < 2 ? 0 : this.levelIndex / (levels.length - 1);
        },

        get strengthLabel() {
            return this.length > 0 && levels[this.levelIndex] ? levels[this.levelIndex].label : '';
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

export function formOtp({ length = 6 } = {}) {
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
            if (this.digits.join('').length === this.length) showError(this.box(0), null);
        },
    };
}

export function formCombobox({ options = [], value = null, noMatches = '', results: resultsFormat = ':count options' } = {}) {
    const labelFor = (current) => options.find((option) => option.value === String(current ?? ''))?.label ?? '';

    return {
        value: value ?? '',
        query: labelFor(value),
        open: false,
        active: -1,
        results: options,
        status: '',

        init() {
            this.$watch('value', (current) => {
                if (!this.open) this.query = labelFor(current);
            });
        },

        get activeId() {
            const list = this.$refs.search.getAttribute('aria-controls');
            return this.open && this.active >= 0 ? `${list}-${this.active}` : null;
        },

        announce() {
            this.status = this.results.length ? resultsFormat.replace(':count', this.results.length) : noMatches;
        },

        show() {
            if (this.open) return;
            this.results = options;
            this.active = Math.max(0, options.findIndex((option) => option.value === String(this.value ?? '')));
            this.open = true;
            this.announce();
        },

        filter() {
            const term = this.query.trim().toLowerCase();
            this.results = term === '' ? options : options.filter((option) => option.label.toLowerCase().includes(term));
            this.active = this.results.length ? 0 : -1;
            this.open = true;
            this.announce();
        },

        move(step) {
            if (!this.open) {
                this.show();
                return;
            }
            const count = this.results.length;
            if (count === 0) return;
            this.active = (this.active + step + count) % count;
            this.$nextTick(() => document.getElementById(this.activeId)?.scrollIntoView({ block: 'nearest' }));
        },

        enter(event) {
            if (!this.open || this.active < 0) return;
            event.preventDefault();
            this.choose(this.results[this.active]);
        },

        choose(option) {
            this.value = option.value;
            this.query = option.label;
            this.open = false;
            showError(this.$refs.search, null);
        },

        close(restore) {
            this.open = false;
            if (restore) this.query = labelFor(this.value);
        },

        // Leaving the field: an exact label is taken, an emptied field clears the choice,
        // anything else goes back to the current choice.
        commit() {
            const term = this.query.trim().toLowerCase();
            const exact = options.find((option) => option.label.toLowerCase() === term);
            if (term === '') this.value = '';
            else if (exact) this.value = exact.value;
            this.close(true);
        },
    };
}

export function formTags({ tags = [], separator = null, max = null, copy = {} } = {}) {
    const join = (list) => (separator ? list.join(`${separator} `) : [...list]);
    const parse = (current) =>
        Array.isArray(current)
            ? current.map(String).filter(Boolean)
            : String(current ?? '')
                  .split(separator ?? ',')
                  .map((tag) => tag.trim())
                  .filter(Boolean);

    return {
        tags: [...tags],
        value: join(tags),
        draft: '',
        status: '',

        init() {
            this.$watch('value', (current) => {
                const next = parse(current);
                if (next.join('\u0000') !== this.tags.join('\u0000')) this.tags = next;
            });
        },

        sync(message) {
            this.value = join(this.tags);
            this.status = message;
        },

        removeLabel(tag) {
            return (copy.remove ?? ':tag').replace(':tag', tag);
        },

        add() {
            const entries = this.draft.split(',').map((tag) => tag.trim()).filter(Boolean);
            this.draft = '';
            const added = [];
            entries.forEach((entry) => {
                if (max !== null && this.tags.length >= max) return;
                if (!this.tags.some((tag) => tag.toLowerCase() === entry.toLowerCase())) {
                    this.tags.push(entry);
                    added.push(entry);
                }
            });
            if (added.length) this.sync((copy.added ?? ':tags').replace(':tags', added.join(', ')));
        },

        key(event) {
            if (event.key === ',') {
                event.preventDefault();
                this.add();
            } else if (event.key === 'Backspace' && this.draft === '' && this.tags.length) {
                event.preventDefault();
                this.draft = this.tags.pop();
                this.sync((copy.editing ?? ':tag').replace(':tag', this.draft));
            }
        },

        paste(event) {
            const text = event.clipboardData?.getData('text') ?? '';
            if (!text.includes(',')) return;
            event.preventDefault();
            this.draft += text;
            this.add();
        },

        removeAt(index) {
            const [gone] = this.tags.splice(index, 1);
            this.sync((copy.removed ?? ':tag').replace(':tag', gone));
            this.$refs.entry.focus();
        },
    };
}

export function formUpload({ image = false, name = null, meta = null, preview = null, copy = {} } = {}) {
    let objectUrl = null;

    const size = (bytes) => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let value = bytes;
        let unit = 0;
        while (value >= 1024 && unit < units.length - 1) {
            value /= 1024;
            unit += 1;
        }
        return `${value.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
    };

    return {
        dragging: false,
        uploading: false,
        percent: 0,
        note: '',
        fileName: name,
        fileMeta: meta,
        previewUrl: preview,

        get progressLabel() {
            return (copy.progress ?? ':percent%').replace(':percent', this.percent);
        },

        chosen(event) {
            const files = [...(event.target.files ?? [])];
            if (files.length === 0) return;

            const [first] = files;
            this.fileName = files.length === 1 ? first.name : files.map((file) => file.name).join(', ');
            this.fileMeta = `${(first.name.split('.').pop() ?? '').toUpperCase()} · ${size(files.reduce((total, file) => total + file.size, 0))}`;
            this.note = '';

            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = image && first.type.startsWith('image/') ? URL.createObjectURL(first) : null;
            this.previewUrl = objectUrl ?? (image ? this.previewUrl : null);
        },

        start() {
            this.uploading = true;
            this.percent = 0;
            this.note = '';
        },

        progress(value) {
            this.percent = Math.round(value);
        },

        finish() {
            this.uploading = false;
            this.note = copy.done ?? '';
        },

        fail() {
            this.uploading = false;
            this.note = copy.failed ?? '';
        },

        clear() {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            this.fileName = null;
            this.fileMeta = null;
            this.previewUrl = null;
            this.note = '';
            if (this.$refs.input) this.$refs.input.value = '';
        },
    };
}

export function registerForms(Alpine) {
    Alpine.data('formPassword', formPassword);
    Alpine.data('formOtp', formOtp);
    Alpine.data('formCombobox', formCombobox);
    Alpine.data('formTags', formTags);
    Alpine.data('formUpload', formUpload);
    Alpine.data('formEditor', formEditor);
}
