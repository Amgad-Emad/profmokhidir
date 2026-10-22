// Per-viewer conveniences (sidebar state). Storage can be missing or blocked
// (private windows, previews), so every read and write is guarded.
export function readSetting(key, fallback) {
    try {
        const value = window.localStorage.getItem(key);
        return value === null ? fallback : JSON.parse(value);
    } catch (error) {
        return fallback;
    }
}

export function writeSetting(key, value) {
    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        /* storage unavailable: the choice lasts for this page only */
    }
}

// Livewire dispatches its events with the parameters as `detail`; older
// builds wrap them in an array. Accept both.
export function eventDetail(event) {
    const detail = event?.detail;
    return Array.isArray(detail) ? (detail[0] ?? {}) : (detail ?? {});
}
