/*
 | Keyboard shortcuts of the dashboard (listed on the "?" sheet):
 | Ctrl/Cmd+K palette, Ctrl/Cmd+S save, Ctrl/Cmd+Enter publish, "/" search,
 | "?" the sheet. Save and publish press the screen's own buttons
 | ([data-shortcut]), so they do exactly what a click does.
 */
const isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);

// Typing into a field keeps "/" and "?" as characters; a ticked checkbox or a button does not.
const TEXT_INPUTS = 'input:not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]):not([type=file]), textarea, select, [contenteditable="true"], .ProseMirror';
const typing = (target) => target instanceof Element && target.closest(TEXT_INPUTS) !== null;

const visible = (selector) => Array.from(document.querySelectorAll(selector))
    .find((element) => !element.disabled && element.offsetParent !== null) ?? null;

const press = (event, selector) => {
    const button = visible(selector);
    if (!button) return;
    event.preventDefault();
    button.click();
};

export function initShortcuts() {
    if (isMac) {
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-shortcut-modifier]').forEach((key) => { key.textContent = '⌘'; });
        });
    }

    document.addEventListener('keydown', (event) => {
        const modifier = event.metaKey || event.ctrlKey;
        const key = event.key.toLowerCase();

        if (modifier && key === 'k') {
            event.preventDefault();
            window.dispatchEvent(new CustomEvent('admin-palette-open'));
            return;
        }

        if (modifier && key === 's') {
            press(event, '[data-shortcut~="save"]');
            return;
        }

        if (modifier && key === 'enter') {
            press(event, '[data-shortcut~="publish"]');
            return;
        }

        if (modifier || event.altKey || typing(event.target) || document.querySelector('[aria-modal="true"]:not([hidden])')?.offsetParent) return;

        if (event.key === '/') {
            event.preventDefault();
            const search = visible('[data-shortcut="search"]');
            if (search) search.focus();
            else window.dispatchEvent(new CustomEvent('admin-palette-open'));
        } else if (event.key === '?') {
            event.preventDefault();
            window.dispatchEvent(new CustomEvent('admin-shortcuts-open'));
        }
    });
}
