/*
 | Admin › Appearance. `appearanceEditor` sends a colour to the server (setColour) from the picker, the
 | hex field or the eyedropper, and announces it at once so the preview can show it before the server
 | answers. `appearancePreview` draws a real page in a frame at desktop width, scaled to the pane, and
 | writes the theme being edited into it: the full set of tokens from the server (data-css on the state
 | element, which Livewire updates after every change) and, until that arrives, the one colour just
 | picked. The frame is same-origin, so its document is written directly; nothing is saved.
 */
const WIDTH = 1280;
const HEIGHT = 1700;
const HEX = /^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i;
const EVENT = 'appearance-colour';

export function appearanceEditor() {
    return {
        eyedropper: typeof window !== 'undefined' && 'EyeDropper' in window,

        preview(token, value, alpha = '') {
            const colour = `${(value || '').trim()}${alpha && /^#[0-9a-f]{6}$/i.test((value || '').trim()) ? alpha : ''}`;
            if (HEX.test(colour)) window.dispatchEvent(new CustomEvent(EVENT, { detail: { token, value: colour } }));
        },

        choose(token, value, alpha = '') {
            let colour = (value || '').trim();
            if (alpha && /^#[0-9a-f]{6}$/i.test(colour)) colour += alpha;
            this.preview(token, colour);
            this.$wire.setColour(token, colour);
        },

        async eyedrop(token, alpha = '') {
            try {
                const result = await new window.EyeDropper().open();
                this.choose(token, result.sRGBHex, alpha);
            } catch (error) {
                // Cancelled with Escape: nothing to do.
            }
        },
    };
}

export function appearancePreview() {
    let mutations = null;
    let resize = null;
    let onColour = null;
    let instant = {};

    return {
        init() {
            const frame = this.$refs.frame;
            frame.style.width = `${WIDTH}px`;
            frame.style.height = `${HEIGHT}px`;
            frame.addEventListener('load', () => this.apply());

            mutations = new MutationObserver(() => this.sync());
            mutations.observe(this.$refs.state, { attributes: true });
            resize = new ResizeObserver(() => this.fit());
            resize.observe(this.$refs.viewport);

            onColour = (event) => {
                instant[event.detail.token] = event.detail.value;
                this.writeInstant();
            };
            window.addEventListener(EVENT, onColour);

            this.sync(true);
            this.fit();
        },

        destroy() {
            mutations?.disconnect();
            resize?.disconnect();
            window.removeEventListener(EVENT, onColour);
        },

        state() {
            return this.$refs.state.dataset;
        },

        document() {
            try {
                return this.$refs.frame.contentDocument;
            } catch (error) {
                return null;
            }
        },

        sync(first = false) {
            const frame = this.$refs.frame;
            const url = this.state().url;

            if (first || frame.dataset.src !== url) {
                frame.dataset.src = url;
                frame.src = url;

                return;
            }

            this.apply();
        },

        style(doc, id) {
            let element = doc.getElementById(id);
            if (!element) {
                element = doc.createElement('style');
                element.id = id;
                doc.head.appendChild(element);
            }

            return element;
        },

        apply() {
            const doc = this.document();
            if (!doc?.head) return;

            doc.documentElement.setAttribute('data-theme', this.state().mode);
            this.style(doc, 'appearance-live').textContent = this.state().css;
            instant = {};
            this.writeInstant();
        },

        writeInstant() {
            const doc = this.document();
            if (!doc?.head) return;

            const declarations = Object.entries(instant)
                .filter(([token, value]) => /^[a-z-]+$/.test(token) && HEX.test(value))
                .map(([token, value]) => `--${token}:${value}`);

            this.style(doc, 'appearance-instant').textContent = declarations.length ? `:root:root:root:root{${declarations.join(';')}}` : '';
        },

        fit() {
            const viewport = this.$refs.viewport;
            const scale = viewport.clientWidth / WIDTH;
            this.$refs.frame.style.transform = `scale(${scale})`;
            viewport.style.height = `${Math.round(HEIGHT * scale)}px`;
        },
    };
}
