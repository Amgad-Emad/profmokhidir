/*
 | "Replace before launch": any field whose value still holds the seeders'
 | placeholder marker gets a flag beside its label (and a note for screen
 | readers through aria-describedby). Re-checked as editors type and after
 | every Livewire update, so the flag goes as soon as the text is replaced.
 */
export function watchPlaceholders({ placeholderMarker: marker, placeholderFlag: flag, placeholderHint: hint }) {
    if (!marker) return;

    const valueOf = (field) => {
        const editor = field.querySelector('.ProseMirror');
        if (editor) return editor.textContent;
        return Array.from(field.querySelectorAll('input:not([type=file]):not([type=checkbox]):not([type=radio]), textarea'))
            .map((input) => input.value)
            .join(' ');
    };

    const scan = () => {
        document.querySelectorAll('[data-edit-screen] [data-form-field]').forEach((field) => {
            const label = field.querySelector(':scope > .form-label');
            if (!label) return;
            const has = valueOf(field).includes(marker);
            let badge = label.querySelector('[data-placeholder-flag]');

            field.toggleAttribute('data-placeholder', has);
            if (has && !badge) {
                badge = document.createElement('span');
                badge.className = 'placeholder-flag';
                badge.dataset.placeholderFlag = '';
                badge.title = hint;
                badge.textContent = flag;
                label.append(' ', badge);
            } else if (!has && badge) {
                badge.remove();
            }
        });
    };

    let timer = null;
    const queue = () => {
        clearTimeout(timer);
        timer = setTimeout(scan, 150);
    };

    document.addEventListener('input', queue);
    document.addEventListener('form-editor-change', queue);
    document.addEventListener('DOMContentLoaded', queue);
    // Livewire fills bound inputs after it starts, so check again once it has.
    document.addEventListener('livewire:initialized', queue);
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ succeed }) => succeed(() => queueMicrotask(queue)));
    });
    window.addEventListener('load', queue);
}
