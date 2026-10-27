/*
 | The rich text editor behind <x-form.editor> (App\Constants\FormControls::editorTools).
 | Tiptap is imported on demand, so pages without an editor never download it. The toolbar is
 | one Tab stop with arrow-key movement (ARIA toolbar pattern), shows which styles are active,
 | and its tooltips name the shortcut (⌘ on a Mac, Ctrl elsewhere). Links go through a small
 | dialog that checks the address. The value goes to the Livewire property (`model`) or to a
 | hidden input; the server sanitises the HTML again on save (App\Support\RichText).
 | The text follows what is typed (dir="auto", or the component's `dir`), and on a right-to-left
 | page the toolbar's arrow keys follow its visual order.
 */
import { isRtl, t } from '../i18n';

const MAC = /Mac|iPhone|iPad/.test(navigator.platform ?? navigator.userAgent);

export default function formEditor(config = {}) {
    // The Editor must never live on the reactive component data: Alpine would wrap it in a
    // proxy and ProseMirror then rejects its own transactions ("Applying a mismatched
    // transaction"). It stays in this closure.
    let editor = null;

    return {
        ready: false,
        marks: {},
        history: { undo: false, redo: false },
        linking: false,
        linkUrl: '',
        linkError: '',
        words: 0,

        get wordsLabel() {
            const [one, many] = config.words ?? [t(':count word'), t(':count words')];
            return (this.words === 1 ? one : many).replace(':count', this.words);
        },

        async init() {
            if (MAC) this.$root.querySelectorAll('[data-shortcut]').forEach((kbd) => (kbd.textContent = kbd.textContent.replace('Ctrl+', '⌘').replace('Alt+', '⌥').replace('Shift+', '⇧')));

            const [{ Editor }, { default: StarterKit }, { default: Link }] = await Promise.all([
                import('@tiptap/core'),
                import('@tiptap/starter-kit'),
                import('@tiptap/extension-link'),
            ]);

            const content = config.model ? (this.$wire?.get(config.model) ?? '') : (config.content ?? '');

            editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [2, 3] },
                        code: false,
                        codeBlock: false,
                        strike: false,
                        horizontalRule: false,
                        dropcursor: false,
                        gapcursor: false,
                    }),
                    Link.configure({
                        openOnClick: false,
                        autolink: true,
                        linkOnPaste: true,
                        protocols: config.protocols ?? ['https', 'http', 'mailto'],
                        HTMLAttributes: { rel: 'noopener' },
                    }),
                ],
                content: content || '',
                editorProps: {
                    attributes: {
                        role: 'textbox',
                        dir: config.dir || 'auto',
                        'aria-multiline': 'true',
                        'aria-labelledby': config.labelId,
                        ...(config.describedBy ? { 'aria-describedby': config.describedBy } : {}),
                        ...(config.invalid ? { 'aria-invalid': 'true' } : {}),
                        'data-placeholder': config.placeholder ?? '',
                    },
                    handleKeyDown: (view, event) => {
                        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                            event.preventDefault();
                            this.openLink();
                            return true;
                        }
                        return false;
                    },
                },
                onUpdate: ({ editor: instance }) => {
                    const html = instance.isEmpty ? '' : instance.getHTML();
                    if (config.model) this.$wire.set(config.model, html, false);
                    else if (this.$refs.value) this.$refs.value.value = html;
                    // Edit screens listen for this to mark the form as changed.
                    this.$root.dispatchEvent(new CustomEvent('form-editor-change', { bubbles: true }));
                },
                onTransaction: () => this.refresh(),
            });

            this.ready = true;
            this.refresh();
        },

        destroy() {
            editor?.destroy();
            editor = null;
        },

        /** The raw Tiptap instance (for tests and debugging); never store it on `this`. */
        instance() {
            return editor;
        },

        refresh() {
            if (!editor) return;
            this.marks = {
                heading2: editor.isActive('heading', { level: 2 }),
                heading3: editor.isActive('heading', { level: 3 }),
                bold: editor.isActive('bold'),
                italic: editor.isActive('italic'),
                bulletList: editor.isActive('bulletList'),
                orderedList: editor.isActive('orderedList'),
                blockquote: editor.isActive('blockquote'),
                link: editor.isActive('link'),
            };
            this.history = { undo: editor.can().undo(), redo: editor.can().redo() };
            const text = editor.state.doc.textContent.trim();
            this.words = text === '' ? 0 : text.split(/\s+/).length;
            this.$refs.editor.firstElementChild?.toggleAttribute('data-empty', editor.isEmpty);
        },

        is(name) {
            return this.marks[name] ? 'true' : 'false';
        },

        can(name) {
            return this.history[name] ?? false;
        },

        run(command) {
            if (!editor) return;
            if (command === 'link') {
                this.linking ? this.closeLink() : this.openLink();
                return;
            }

            const chain = editor.chain().focus();
            ({
                heading2: () => chain.toggleHeading({ level: 2 }),
                heading3: () => chain.toggleHeading({ level: 3 }),
                bold: () => chain.toggleBold(),
                italic: () => chain.toggleItalic(),
                bulletList: () => chain.toggleBulletList(),
                orderedList: () => chain.toggleOrderedList(),
                blockquote: () => chain.toggleBlockquote(),
                undo: () => chain.undo(),
                redo: () => chain.redo(),
            })[command]().run();
        },

        /** Arrow keys, Home and End move along the toolbar; Tab leaves it. */
        toolbarKey(event) {
            const tools = [...this.$root.querySelectorAll('.form-editor__tool:not(:disabled)')];
            const index = tools.indexOf(document.activeElement);
            if (index < 0) return;

            // The toolbar runs right to left on an Arabic page, so the arrows follow what is seen.
            const forward = isRtl() ? 'ArrowLeft' : 'ArrowRight';
            const back = isRtl() ? 'ArrowRight' : 'ArrowLeft';
            const next = { [forward]: index + 1, [back]: index - 1, Home: 0, End: tools.length - 1 }[event.key];
            if (next === undefined) return;

            event.preventDefault();
            const target = tools[(next + tools.length) % tools.length];
            tools.forEach((tool) => tool.setAttribute('tabindex', tool === target ? '0' : '-1'));
            target.focus();
        },

        openLink() {
            this.linkUrl = editor?.getAttributes('link').href ?? '';
            this.linkError = '';
            this.linking = true;
            this.$nextTick(() => this.$refs.linkInput?.focus());
        },

        closeLink() {
            this.linking = false;
            this.linkError = '';
            editor?.commands.focus();
        },

        /** A full address: https://…, http://… or mailto:…; a bare "example.org" gets https://. */
        normalise(url) {
            const value = url.trim();
            if (value === '') return '';
            const candidate = /^[a-z][a-z0-9+.-]*:/i.test(value) ? value : `https://${value}`;
            try {
                const parsed = new URL(candidate);
                const protocol = parsed.protocol.replace(':', '');
                if (!(config.protocols ?? []).includes(protocol)) return null;
                if (protocol !== 'mailto' && !parsed.hostname.includes('.')) return null;
                return candidate;
            } catch {
                return null;
            }
        },

        checkLink() {
            if (this.linkUrl.trim() === '') {
                this.linkError = '';
                return true;
            }
            this.linkError = this.normalise(this.linkUrl) === null ? config.linkInvalid : '';
            return this.linkError === '';
        },

        applyLink() {
            const url = this.normalise(this.linkUrl);
            if (url === '') {
                this.removeLink();
                return;
            }
            if (url === null) {
                this.linkError = config.linkInvalid;
                this.$refs.linkInput?.focus();
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
            this.linking = false;
            this.linkError = '';
        },

        removeLink() {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            this.linking = false;
            this.linkUrl = '';
            this.linkError = '';
        },
    };
}
