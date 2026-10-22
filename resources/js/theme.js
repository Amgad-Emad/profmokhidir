/*
 | The colour theme on the client: applies a choice (System, Light or Dark) to
 | <html> with a soft 300ms cross-fade (instant under reduced motion), keeps the
 | theme-color metas in step, mirrors the choice to localStorage and the cookie
 | and saves it on the account through the theme route. Drives every
 | [data-theme-switch] control (a menu of three radio items) and follows changes
 | made in another tab. The inline script in <x-theme.head> handles the first
 | paint; this module only deals with changes.
 */

const STORAGE_KEY = 'theme';
const COOKIE = 'theme';
const COOKIE_MAX_AGE = 525600 * 60;
const ATTRIBUTE = 'data-theme';
const PREFERENCE = 'data-theme-preference';
const TRANSITION = 'data-theme-transition';
const TRANSITION_MS = 300;
const SYSTEM = 'system';
const VALID = ['system', 'light', 'dark'];
const ENDPOINT = '/theme';

const html = document.documentElement;
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let transitionTimer = null;

export function preference() {
    const value = html.getAttribute(PREFERENCE);

    return VALID.includes(value) ? value : SYSTEM;
}

export function resolved(value = preference()) {
    return value === SYSTEM ? (darkQuery.matches ? 'dark' : 'light') : value;
}

function updateMetas(value) {
    for (const meta of document.querySelectorAll('meta[name="theme-color"]')) {
        const scheme = value === SYSTEM ? (meta.media.includes('dark') ? 'dark' : 'light') : value;
        const colour = meta.dataset[scheme];
        if (colour) meta.setAttribute('content', colour);
    }
}

function crossFade() {
    if (reducedMotion.matches) return;
    html.setAttribute(TRANSITION, '');
    window.clearTimeout(transitionTimer);
    transitionTimer = window.setTimeout(() => html.removeAttribute(TRANSITION), TRANSITION_MS + 50);
}

function remember(value, endpoint) {
    try { localStorage.setItem(STORAGE_KEY, value); } catch { /* private mode */ }
    document.cookie = `${COOKIE}=${value};path=/;max-age=${COOKIE_MAX_AGE};samesite=lax`;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) return;

    fetch(endpoint ?? ENDPOINT, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ theme: value }),
        keepalive: true,
    }).catch(() => { /* the cookie and localStorage already carry the choice */ });
}

/* Apply a preference to the page. `persist` false only mirrors a change made elsewhere. */
export function applyTheme(value, { persist = true, endpoint = null } = {}) {
    if (!VALID.includes(value)) return;

    if (value !== preference() || (value === SYSTEM) !== !html.hasAttribute(ATTRIBUTE)) {
        crossFade();
    }

    html.setAttribute(PREFERENCE, value);
    if (value === SYSTEM) html.removeAttribute(ATTRIBUTE);
    else html.setAttribute(ATTRIBUTE, value);
    updateMetas(value);

    for (const control of document.querySelectorAll('[data-theme-switch]')) syncControl(control, value);
    for (const input of document.querySelectorAll('input[name="theme"][type="radio"]')) input.checked = input.value === value;

    if (persist) remember(value, endpoint);
    document.dispatchEvent(new CustomEvent('theme:change', { detail: { preference: value, resolved: resolved(value) } }));
}

function syncControl(control, value) {
    for (const option of control.querySelectorAll('[data-theme-option]')) {
        option.setAttribute('aria-checked', option.dataset.themeOption === value ? 'true' : 'false');
    }
}

/* The menu: opens on click, arrow keys travel, Escape and focus leaving close it. */
function mountControl(control) {
    const button = control.querySelector('[data-theme-switch-button]');
    const menu = control.querySelector('[data-theme-switch-menu]');
    if (!button || !menu) return;
    const options = [...menu.querySelectorAll('[data-theme-option]')];
    const endpoint = control.dataset.themeEndpoint || null;

    const isOpen = () => button.getAttribute('aria-expanded') === 'true';

    const open = (focusIndex = null) => {
        menu.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        control.classList.add('is-open');
        const index = focusIndex ?? Math.max(0, options.findIndex((o) => o.getAttribute('aria-checked') === 'true'));
        options[index]?.focus();
    };

    const close = ({ refocus = true } = {}) => {
        if (!isOpen()) return;
        menu.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        control.classList.remove('is-open');
        if (refocus) button.focus();
    };

    button.addEventListener('click', () => (isOpen() ? close() : open()));
    button.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            open(event.key === 'ArrowUp' ? options.length - 1 : 0);
        }
    });

    options.forEach((option, index) => {
        option.addEventListener('click', () => {
            applyTheme(option.dataset.themeOption, { endpoint });
            close();
        });
        option.addEventListener('keydown', (event) => {
            const keys = { ArrowDown: index + 1, ArrowUp: index - 1, Home: 0, End: options.length - 1 };
            if (event.key in keys) {
                event.preventDefault();
                options[(keys[event.key] + options.length) % options.length].focus();
            } else if (event.key === 'Escape' || event.key === 'Tab') {
                if (event.key === 'Escape') event.preventDefault();
                close({ refocus: event.key === 'Escape' });
            }
        });
    });

    document.addEventListener('pointerdown', (event) => {
        if (isOpen() && !control.contains(event.target)) close({ refocus: false });
    });
    control.addEventListener('focusout', (event) => {
        if (isOpen() && event.relatedTarget && !control.contains(event.relatedTarget)) close({ refocus: false });
    });
}

function mountForms() {
    for (const input of document.querySelectorAll('input[name="theme"][type="radio"][data-theme-radio]')) {
        input.addEventListener('change', () => {
            if (input.checked) applyTheme(input.value, { endpoint: input.form?.getAttribute('action') || null });
        });
    }
}

function mount() {
    document.querySelectorAll('[data-theme-switch]').forEach(mountControl);
    mountForms();
    updateMetas(preference());

    darkQuery.addEventListener('change', () => {
        if (preference() === SYSTEM) {
            document.dispatchEvent(new CustomEvent('theme:change', { detail: { preference: SYSTEM, resolved: resolved() } }));
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY && VALID.includes(event.newValue) && event.newValue !== preference()) {
            applyTheme(event.newValue, { persist: false });
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
else mount();
