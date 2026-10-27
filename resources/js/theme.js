/*
 | The colour theme on the client: Light or Dark. Until the visitor chooses, <html>
 | carries no data-theme and prefers-color-scheme decides; one click (or Enter or
 | Space) on any [data-theme-toggle] switches to the other theme at once with a soft
 | 300ms cross-fade (instant under reduced motion), keeps the theme-color metas in
 | step, mirrors the choice to localStorage and the cookie and saves it on the
 | account through the theme route. Follows changes made in another tab. The inline
 | script in <x-theme.head> handles the first paint, including a device that still
 | remembers the retired "system" value (data-theme-resolved, saved here).
 */

const STORAGE_KEY = 'theme';
const COOKIE = 'theme';
const COOKIE_MAX_AGE = 525600 * 60;
const ATTRIBUTE = 'data-theme';
const RESOLVED = 'data-theme-resolved';
const TRANSITION = 'data-theme-transition';
const TRANSITION_MS = 300;
const VALID = ['light', 'dark'];
const ENDPOINT = '/theme';
const LABEL = 'Switch to %s theme';

const html = document.documentElement;
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let transitionTimer = null;

/* True once the visitor (or their account) has chosen a theme. */
export function chosen() {
    return VALID.includes(html.getAttribute(ATTRIBUTE));
}

/* The theme showing now: the choice, else what the device prefers. */
export function current() {
    return chosen() ? html.getAttribute(ATTRIBUTE) : darkQuery.matches ? 'dark' : 'light';
}

const opposite = (value) => (value === 'dark' ? 'light' : 'dark');

function updateMetas() {
    for (const meta of document.querySelectorAll('meta[name="theme-color"]')) {
        const scheme = chosen() ? current() : meta.media.includes('dark') ? 'dark' : 'light';
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

/* The toggle names the theme it switches to and is pressed while the dark theme shows. */
function syncToggle(toggle) {
    const button = toggle.querySelector('[data-theme-toggle-button]');
    if (!button) return;
    const showing = current();
    const next = opposite(showing);
    // The server prints each label in the page's language (data-theme-label-light / -dark).
    const label = next === 'dark' ? toggle.dataset.themeLabelDark : toggle.dataset.themeLabelLight;
    button.setAttribute('aria-label', label || (toggle.dataset.themeLabel || LABEL).replace('%s', next));
    button.setAttribute('aria-pressed', showing === 'dark' ? 'true' : 'false');
    button.value = next;
    toggle.dataset.themeShowing = showing;
}

function syncAll() {
    updateMetas();
    document.querySelectorAll('[data-theme-toggle]').forEach(syncToggle);
}

/* Apply a theme to the page. `persist` false only mirrors a change made elsewhere. */
export function applyTheme(value, { persist = true, endpoint = null } = {}) {
    if (!VALID.includes(value)) return;

    if (value !== current()) crossFade();
    html.setAttribute(ATTRIBUTE, value);
    syncAll();

    if (persist) remember(value, endpoint);
    document.dispatchEvent(new CustomEvent('theme:change', { detail: { theme: value } }));
}

/* The toggle is a one-button form (it works without script): the script switches in place instead. */
function mountToggle(toggle) {
    syncToggle(toggle);
    toggle.addEventListener('submit', (event) => {
        event.preventDefault();
        applyTheme(opposite(current()), { endpoint: toggle.getAttribute('action') || null });
    });
}

function mount() {
    document.querySelectorAll('[data-theme-toggle]').forEach(mountToggle);
    updateMetas();

    // A device that still remembered "system": the head script pinned the theme it resolves to; keep it on the account too.
    if (html.hasAttribute(RESOLVED)) {
        html.removeAttribute(RESOLVED);
        remember(current(), document.querySelector('[data-theme-toggle]')?.getAttribute('action') || null);
    }

    darkQuery.addEventListener('change', () => {
        if (chosen()) return;
        syncAll();
        document.dispatchEvent(new CustomEvent('theme:change', { detail: { theme: current() } }));
    });

    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY && VALID.includes(event.newValue) && event.newValue !== html.getAttribute(ATTRIBUTE)) {
            applyTheme(event.newValue, { persist: false });
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
else mount();
