/*
 | The live preview beside a page editor (Admin › Pages): the real public page,
 | with the editor's draft, in a frame drawn at desktop (1280px) or phone (390px)
 | width and scaled to fit the pane. It reloads after every save and autosave,
 | so it always shows what is stored. Open/closed and the width are remembered
 | per viewer. The frame is inert (a picture of the page): keyboard and screen
 | reader users open the same page from Preview in the save bar. `data-preview-urls` maps a view (the page, or each error code) to its address.
 */
import { eventDetail, readSetting, writeSetting } from './storage';

const WIDTHS = { desktop: 1280, phone: 390 };
const OPEN_KEY = 'admin.pagePreview.open';
const DEVICE_KEY = 'admin.pagePreview.device';

export function pagePreview() {
    let observer = null;
    let onSaved = null;

    // Read once from the pane (wire:ignore): x-data stays static, so Livewire's morph never restarts the component.
    let urls = {};

    return {
        open: readSetting(OPEN_KEY, true),
        device: readSetting(DEVICE_KEY, 'desktop'),
        view: null,
        scale: 1,
        loading: true,

        init() {
            try {
                urls = JSON.parse(this.$el.querySelector('[data-preview-urls]')?.dataset.previewUrls || '{}');
            } catch (error) {
                urls = {};
            }
            this.view = Object.keys(urls)[0] ?? null;

            onSaved = (event) => {
                if (!eventDetail(event).blocked) this.reload();
            };
            window.addEventListener('admin-saved', onSaved);

            observer = new ResizeObserver(() => this.fit());
            if (this.$refs.viewport) observer.observe(this.$refs.viewport);
            this.$watch('open', (open) => {
                writeSetting(OPEN_KEY, open);
                if (open) this.$nextTick(() => this.fit());
            });
            this.$watch('device', (device) => {
                writeSetting(DEVICE_KEY, device);
                this.$nextTick(() => this.fit());
            });
            this.fit();
        },

        destroy() {
            observer?.disconnect();
            window.removeEventListener('admin-saved', onSaved);

        },

        get width() {
            return WIDTHS[this.device] ?? WIDTHS.desktop;
        },

        get src() {
            return urls[this.view] ?? '';
        },

        show(view) {
            this.view = view;
            this.reload();
        },

        reload() {
            const frame = this.$refs.frame;
            if (!frame || !this.src) return;
            this.loading = true;
            // The address is signed, so nothing may be added to it; previews are sent no-store, so a plain reload is fresh.
            try {
                frame.contentWindow.location.replace(this.src);
            } catch (error) {
                frame.src = this.src;
            }
        },

        fit() {
            const viewport = this.$refs.viewport;
            if (!viewport || !viewport.clientWidth) return;
            this.scale = Math.min(1, viewport.clientWidth / this.width);
        },

        get frameStyle() {
            const height = this.$refs.viewport ? this.$refs.viewport.clientHeight / this.scale : 900;
            return `width: ${this.width}px; height: ${Math.round(height)}px; transform: scale(${this.scale});`;
        },
    };
}
