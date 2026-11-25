/*
 | The live preview beside a page editor (Admin › Pages): the real public page,
 | with the editor's draft, in a frame drawn at a real screen width, desktop
 | (1280, as tall as the pane allows) or phone (390 × 844), and scaled as a whole to fill the pane, so
 | full-height sections (the home hero) look as they do in a browser. It reloads after every save and autosave,
 | so it always shows what is stored. Open/closed and the width are remembered
 | per viewer. The frame is inert (a picture of the page): keyboard and screen
 | reader users open the same page from Preview in the save bar. The wheel over
 | the pane scrolls the page in the frame (the frame itself takes no input); at
 | either end of the page the wheel scrolls the editor as usual. `data-preview-urls` maps a view (the page, or each error code) to its address.
 |
 | The preview also shows where each part of the page is edited. Regions of the previewed page carry
 | `data-edit-section` (the @editSection Blade directive) naming the editor section that changes them.
 | Opening a section, or pointing at one, outlines its region in the preview with the section's name
 | (opening also scrolls the preview to it); pointing at the preview names the section under the
 | pointer, and a click there opens that section in the editor.
 */
import { eventDetail, readSetting, writeSetting } from './storage';

const SCREENS = { desktop: { width: 1280, height: 800 }, phone: { width: 390, height: 844 } };
// The pane is sticky this far from the top of the window (5.5rem), and keeps this much room below.
const STICKY_TOP = 88;
const BOTTOM_ROOM = 24;
// A desktop page is shown as tall as the pane allows, within these heights (in page pixels).
const DESKTOP_MIN_HEIGHT = 640;
const DESKTOP_MAX_HEIGHT = 1200;
const OPEN_KEY = 'admin.pagePreview.open';
const DEVICE_KEY = 'admin.pagePreview.device';
const SECTION_ATTRIBUTE = 'data-edit-section';
const MARK_ID = 'admin-edit-section-mark';
// Matches collapsible.js: a folding part tells the others it opened.
const OPENED = 'admin-collapsible-opened';

export function pagePreview() {
    let observer = null;
    let onSaved = null;
    let onWheel = null;
    let onOpened = null;
    let onFrameLoad = null;
    let onEditorPointer = null;

    // Read once from the pane (wire:ignore): x-data stays static, so Livewire's morph never restarts the component.
    let urls = {};
    let copy = { editedIn: ':section' };

    return {
        open: readSetting(OPEN_KEY, true),
        device: readSetting(DEVICE_KEY, 'desktop'),
        view: null,
        scale: 1,
        // The framed page's own height: the phone's screen, or as much of a desktop page as fits.
        frameHeight: SCREENS.desktop.height,
        loading: true,
        // The editor section whose region is outlined (the one last opened), and the one pointed at.
        current: null,
        pointed: null,

        init() {
            try {
                urls = JSON.parse(this.$el.querySelector('[data-preview-urls]')?.dataset.previewUrls || '{}');
            } catch (error) {
                urls = {};
            }
            this.view = Object.keys(urls)[0] ?? null;
            try {
                copy = { ...copy, ...JSON.parse(this.$el.querySelector('[data-preview-copy]')?.dataset.previewCopy || '{}') };
            } catch (error) {
                // Keep the plain label.
            }

            onOpened = (event) => {
                const id = eventDetail(event).id ?? '';
                if (id.startsWith('section-')) {
                    this.current = id.slice('section-'.length);
                    this.mark(this.current, true);
                }
            };
            window.addEventListener(OPENED, onOpened);

            onFrameLoad = () => this.mark(this.pointed ?? this.current, false);
            this.$refs.frame?.addEventListener('load', onFrameLoad);

            // Pointing at a section of the form outlines its region; leaving it goes back to the open one.
            // Delegated, so sections Livewire re-renders keep working.
            onEditorPointer = (event) => {
                if (event.target.closest?.('[data-page-preview]')) return;
                const id = event.target.closest?.('[data-form-section]')?.dataset.formSection ?? null;
                if (id === this.pointed) return;
                this.pointed = id;
                this.mark(id ?? this.current, false);
            };
            this.$el.addEventListener('mouseover', onEditorPointer);

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
            window.removeEventListener(OPENED, onOpened);
            this.$refs.frame?.removeEventListener('load', onFrameLoad);
            this.$el.removeEventListener('mouseover', onEditorPointer);
        },

        // The previewed page's document, when it can be reached (same site, loaded).
        frameDocument() {
            try {
                return this.$refs.frame?.contentDocument ?? null;
            } catch (error) {
                return null;
            }
        },

        // The editor section's name, as its heading shows it.
        sectionTitle(id) {
            return document.querySelector(`#section-${CSS.escape(id)} .form-section__title`)?.textContent.trim() ?? '';
        },

        // Outline the region(s) the section changes, named after it; with `scroll`, bring them into view.
        mark(id, scroll) {
            const doc = this.frameDocument();
            if (!doc?.body) return;
            doc.getElementById(MARK_ID)?.remove();

            const regions = id ? [...doc.querySelectorAll(`[${SECTION_ATTRIBUTE}="${CSS.escape(id)}"]`)] : [];
            const rects = regions.map((region) => region.getBoundingClientRect()).filter((rect) => rect.width > 0 && rect.height > 0);
            if (rects.length === 0) return;

            const win = doc.defaultView;
            const layer = doc.createElement('div');
            layer.id = MARK_ID;
            layer.setAttribute('aria-hidden', 'true');
            layer.style.cssText = 'position:absolute;inset:0 auto auto 0;width:0;height:0;pointer-events:none;z-index:2147483647;';

            rects.forEach((rect, index) => {
                const box = doc.createElement('div');
                box.style.cssText = [
                    'position:absolute',
                    `left:${rect.left + win.scrollX - 8}px`,
                    `top:${rect.top + win.scrollY - 8}px`,
                    `width:${rect.width + 16}px`,
                    `height:${rect.height + 16}px`,
                    'border:2px solid var(--focus)',
                    'border-radius:4px',
                    'background:color-mix(in srgb, var(--focus) 8%, transparent)',
                ].join(';');

                if (index === 0) {
                    const label = doc.createElement('span');
                    label.textContent = copy.editedIn.replace(':section', this.sectionTitle(id) || id);
                    label.style.cssText = 'position:absolute;inset-block-end:100%;inset-inline-start:-2px;margin-block-end:2px;padding:2px 10px;border-radius:4px 4px 0 0;background:var(--focus);color:var(--surface);font:500 15px/1.6 var(--font-sans, sans-serif);white-space:nowrap;';
                    box.append(label);
                }

                layer.append(box);
            });

            doc.body.append(layer);

            if (scroll) {
                const top = Math.min(...rects.map((rect) => rect.top)) + win.scrollY;
                win.scrollTo({ top: Math.max(0, top - 80), behavior: 'smooth' });
            }
        },

        // The region of the previewed page under the pointer, from a pointer event on the pane.
        sectionAt(event) {
            const frame = this.$refs.frame;
            const doc = this.frameDocument();
            if (!frame || !doc) return null;
            const box = frame.getBoundingClientRect();
            const x = (event.clientX - box.left) / this.scale;
            const y = (event.clientY - box.top) / this.scale;

            return doc.elementFromPoint(x, y)?.closest(`[${SECTION_ATTRIBUTE}]`)?.getAttribute(SECTION_ATTRIBUTE) ?? null;
        },

        // Pointing at the preview names the editor section of the region under the pointer.
        hover(event) {
            const id = this.sectionAt(event);
            this.$refs.viewport.style.cursor = id ? 'pointer' : '';
            if (id === this.pointed) return;
            this.pointed = id;
            this.mark(id ?? this.current, false);
        },

        leave() {
            this.pointed = null;
            this.$refs.viewport.style.cursor = '';
            this.mark(this.current, false);
        },

        // A click on a region opens its section in the editor and brings it into view.
        pick(event) {
            const id = this.sectionAt(event);
            const section = id ? document.getElementById(`section-${id}`) : null;
            if (!section) return;

            const data = window.Alpine?.$data(section);
            if (data && 'open' in data) data.open = true;
            this.current = id;
            this.mark(id, false);
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

        // Fill the pane: a desktop page at the pane's width and as tall as the window allows; a phone
        // screen whole, as large as fits.
        fit() {
            const stage = this.$refs.viewport?.parentElement;
            const pane = stage?.closest('[data-page-preview]');
            if (!stage || !pane || !stage.clientWidth) return;

            const style = getComputedStyle(stage);
            const width = stage.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight) - 2;
            const above = stage.getBoundingClientRect().top - pane.getBoundingClientRect().top + parseFloat(style.paddingTop);
            const below = parseFloat(style.paddingBottom) + (pane.querySelector('.page-preview__help')?.offsetHeight ?? 0) + 12;
            const room = Math.max(320, window.innerHeight - STICKY_TOP - BOTTOM_ROOM - above - below);

            if (this.device === 'phone') {
                this.scale = Math.min(1, width / SCREENS.phone.width, room / SCREENS.phone.height);
                this.frameHeight = SCREENS.phone.height;

                return;
            }

            this.scale = Math.min(1, width / SCREENS.desktop.width);
            this.frameHeight = Math.round(Math.min(DESKTOP_MAX_HEIGHT, Math.max(DESKTOP_MIN_HEIGHT, room / this.scale)));
        },

        get viewportStyle() {
            return `width: ${Math.floor(this.screen.width * this.scale) + 2}px; height: ${Math.floor(this.frameHeight * this.scale) + 2}px;`;
        },

        get frameStyle() {
            return `width: ${this.screen.width}px; height: ${this.frameHeight}px; transform: scale(${this.scale});`;
        },
    };
}
