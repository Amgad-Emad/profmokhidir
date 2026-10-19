// Admin and community-chat layouts. Livewire ships its own Alpine build via
// @livewireScripts; the components below register on alpine:init.
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';

document.addEventListener('alpine:init', () => {
    /*
     | Rich text (Tiptap) bound to a Livewire property. The toolbar is limited to
     | headings, bold, italic, links, lists and quotes; the server sanitises the
     | HTML again on save (App\Support\RichText).
     */
    Alpine.data('richText', (field) => ({
        editor: null,
        ready: false,
        linking: false,
        linkUrl: '',
        marks: {},

        init() {
            this.editor = new Editor({
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
                        protocols: ['http', 'https', 'mailto'],
                        HTMLAttributes: { rel: 'noopener' },
                    }),
                ],
                content: this.$wire.get(field) || '',
                editorProps: {
                    attributes: {
                        role: 'textbox',
                        'aria-multiline': 'true',
                        'aria-labelledby': this.$refs.editor.dataset.labelId,
                        class: 'rich-text__editable',
                    },
                },
                onUpdate: ({ editor }) => {
                    const html = editor.isEmpty ? '' : editor.getHTML();
                    this.$wire.set(field, html, false);
                },
                onTransaction: () => this.refresh(),
            });

            this.ready = true;
            this.refresh();
        },

        destroy() {
            this.editor?.destroy();
        },

        refresh() {
            if (!this.editor) return;
            this.marks = {
                heading2: this.editor.isActive('heading', { level: 2 }),
                heading3: this.editor.isActive('heading', { level: 3 }),
                bold: this.editor.isActive('bold'),
                italic: this.editor.isActive('italic'),
                bulletList: this.editor.isActive('bulletList'),
                orderedList: this.editor.isActive('orderedList'),
                blockquote: this.editor.isActive('blockquote'),
                link: this.editor.isActive('link'),
            };
        },

        is(name) {
            return this.marks[name] ? 'true' : 'false';
        },

        run(command) {
            const chain = this.editor.chain().focus();
            ({
                heading2: () => chain.toggleHeading({ level: 2 }),
                heading3: () => chain.toggleHeading({ level: 3 }),
                bold: () => chain.toggleBold(),
                italic: () => chain.toggleItalic(),
                bulletList: () => chain.toggleBulletList(),
                orderedList: () => chain.toggleOrderedList(),
                blockquote: () => chain.toggleBlockquote(),
            })[command]().run();
        },

        toggleLinkForm() {
            this.linking = !this.linking;
            if (this.linking) {
                this.linkUrl = this.editor.getAttributes('link').href || '';
                this.$nextTick(() => this.$refs.linkInput?.focus());
            }
        },

        setLink() {
            const url = this.linkUrl.trim();
            if (url === '') {
                this.unsetLink();
                return;
            }
            if (!/^(https?:\/\/|mailto:)/i.test(url)) {
                this.linkUrl = 'https://' + url;
                return this.setLink();
            }
            this.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
            this.linking = false;
        },

        unsetLink() {
            this.editor.chain().focus().extendMarkRange('link').unsetLink().run();
            this.linking = false;
            this.linkUrl = '';
        },
    }));

    /*
     | Drag-and-drop reordering of table rows (native HTML5 drag events). Rows are
     | moved in the DOM while dragging; on drop the new id order is sent to the
     | component's reorder() method. Move up/down buttons remain the keyboard path.
     */
    Alpine.data('sortableRows', () => ({
        draggingId: null,
        dragged: null,
        moved: false,

        start(event, id) {
            this.draggingId = id;
            this.dragged = event.currentTarget;
            this.moved = false;
            event.dataTransfer.effectAllowed = 'move';
            try { event.dataTransfer.setData('text/plain', String(id)); } catch (e) { /* older browsers */ }
            this.dragged.classList.add('is-dragging');
        },

        over(event) {
            const target = event.currentTarget;
            if (!this.dragged || target === this.dragged || target.parentNode !== this.dragged.parentNode) return;
            const rect = target.getBoundingClientRect();
            const after = event.clientY > rect.top + rect.height / 2;
            target.parentNode.insertBefore(this.dragged, after ? target.nextSibling : target);
            this.moved = true;
        },

        ids() {
            return Array.from(this.$el.querySelectorAll('tr[data-id]')).map((row) => Number(row.dataset.id));
        },

        finish(event) {
            // Drop handler on the body; order is sent from dragend so a cancelled drag sends nothing.
        },

        end(event) {
            event.currentTarget.classList.remove('is-dragging');
            if (this.moved && this.draggingId !== null) {
                this.$wire.reorder(this.ids());
            }
            this.draggingId = null;
            this.dragged = null;
            this.moved = false;
        },
    }));
});
