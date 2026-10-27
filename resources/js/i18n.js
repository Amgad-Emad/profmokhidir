// Interface strings in the page's language. The layout prints the translations the scripts need
// as window.i18n (App\Constants\ScriptStrings); every string a script shows goes through t(),
// with its English source as the key and :placeholders replaced. English pages print nothing,
// so the source is shown as it is.
export function t(source, replace = {}) {
    const strings = window.i18n || {};
    let text = Object.prototype.hasOwnProperty.call(strings, source) ? strings[source] : source;

    // Longest names first, so :count is not replaced inside :counted.
    for (const key of Object.keys(replace).sort((a, b) => b.length - a.length)) {
        text = text.split(`:${key}`).join(String(replace[key]));
    }

    return text;
}

// The page's language and direction, from <html lang dir>.
export const locale = () => document.documentElement.lang || 'en';
export const isRtl = () => document.documentElement.dir === 'rtl';

// The language switcher (<x-locale.switcher>) links to /language/{locale}?to=<same page>. The
// browser keeps a fragment across the redirect, so the current #anchor is added on the way out.
document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[data-locale-switch]') : null;
    if (!link || !window.location.hash) return;
    const url = new URL(link.href, window.location.href);
    url.hash = window.location.hash;
    link.href = url.toString();
});

// The quiet "read the site in Arabic" suggestion: dismissing it is remembered for a year.
document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-locale-suggestion-dismiss]') : null;
    if (!button) return;
    const banner = button.closest('[data-locale-suggestion]');
    document.cookie = `${button.dataset.localeSuggestionDismiss}=1; max-age=31536000; path=/; samesite=lax`;
    banner?.remove();
});
