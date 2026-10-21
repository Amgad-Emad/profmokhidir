/*
 | Shared keyboard behaviour for the full-screen panels (site menu, search):
 | the focusable elements inside a root, and a Tab / Shift+Tab wrap between
 | the first and the last of them.
 */

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function focusablesIn(root) {
    if (!root) return [];

    return Array.from(root.querySelectorAll(FOCUSABLE)).filter(
        (el) => el.offsetParent !== null || el === document.activeElement,
    );
}

export function trapTab(event, root) {
    if (event.key !== 'Tab') return;

    const items = focusablesIn(root);
    if (items.length === 0) {
        event.preventDefault();
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || active === root)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
    }
}
