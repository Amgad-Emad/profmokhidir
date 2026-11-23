/*
 | The live preview beside a page editor (Admin › Pages): the real public page,
 | with the editor's draft, in a frame drawn at a real screen size, desktop
 | (1280 × 800) or phone (390 × 844), and scaled as a whole to fit the pane, so
 | full-height sections (the home hero) look as they do in a browser. It reloads after every save and autosave,
 | so it always shows what is stored. Open/closed and the width are remembered
 | per viewer. The frame is inert (a picture of the page): keyboard and screen
 | reader users open the same page from Preview in the save bar. The wheel over
 | the pane scrolls the page in the frame (the frame itself takes no input); at
 | either end of the page the wheel scrolls the editor as usual. `data-preview-urls` maps a view (the page, or each error code) to its address.
 */
import { eventDetail, readSetting, writeSetting } from './storage';

const SCREENS = { desktop: { width: 1280, height: 800 }, phone: { width: 390, height: 844 } };
// Room the pane leaves for the admin bar, the preview bar and the note (matches the old calc(100vh - 14rem)).
const CHROME_HEIGHT = 224;
const OPEN_KEY = 'admin.pagePreview.open';
const DEVICE_KEY = 'admin.pagePreview.device';

export function pagePreview() {
    let observer = null;
    let onSaved = null;
    let onWheel = null;

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

            // Watch the pane, not the viewport: the viewport's own size follows the scale.
            observer = new ResizeObserver(() => this.fit());
            if (this.$refs.viewport?.parentElement) observer.observe(this.$refs.viewport.parentElement);
            this.onResize = () => this.fit();
            window.addEventListener('resize', this.onResize);

            onWheel = (event) => this.scrollFrame(event);
            this.$refs.viewport?.addEventListener('wheel', onWheel, { passive: false });
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
            this.$refs.viewport?.removeEventListener('wheel', onWheel);
            window.removeEventListener('resize', this.onResize);

        },

        get screen() {
            return SCREENS[this.device] ?? SCREENS.desktop;
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

        // The inert frame ignores the pointer, so the wheel lands on the pane: scroll the framed page by the
        // same distance on screen (divided by the scale), and keep the editor still only while the page moved.
        scrollFrame(event) {
            let win;
            try {
                win = this.$refs.frame?.contentWindow;
                if (!win || !win.document) return;
            } catch (error) {
                return;
            }
            const unit = event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? win.innerHeight : 1;
            const before = win.scrollY;
            win.scrollBy({ top: (event.deltaY * unit) / this.scale, behavior: 'instant' });
            if (win.scrollY !== before) event.preventDefault();
        },

        // The largest scale at which the whole screen fits the pane's width and the window's height.
        fit() {
            const pane = this.$refs.viewport?.parentElement;
            if (!pane || !pane.clientWidth) return;
            const room = Math.max(320, window.innerHeight - CHROME_HEIGHT);
            this.scale = Math.min(1, (pane.clientWidth - 2) / this.screen.width, room / this.screen.height);
        },

        get viewportStyle() {
            const { width, height } = this.screen;
            return `width: ${Math.floor(width * this.scale) + 2}px; height: ${Math.floor(height * this.scale) + 2}px;`;
        },

        get frameStyle() {
            const { width, height } = this.screen;
            return `width: ${width}px; height: ${height}px; transform: scale(${this.scale});`;
        },
    };
}
