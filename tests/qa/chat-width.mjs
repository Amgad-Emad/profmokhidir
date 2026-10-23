/*
 | Browser checks for the chat width, optional image description and thumbnail fallback,
 | through Playwright and Google Chrome against https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:chat-width
 |
 | Uses the QA accounts from tests/qa/chat-fixes-setup.php. With NO queue worker running it
 | sends a pasted image without a description (the original shows, scaled down, and the alt
 | names the member), then runs the worker once and waits for the poll to swap in the
 | thumbnail. It also sends three described images and five documents. Then, in both themes
 | at 390, 768, 1440 and 1920px with the side panel open and closed, it measures that the
 | message list, the messages, the attachment grids and the composer fill the centre column
 | (24px side padding below 1024px, 40px from 1024px), that text keeps a 100ch measure, that
 | no image cell is empty, and that nothing scrolls sideways. Every form on a few pages must
 | have novalidate and an `invalid` event must be cancelled (no native bubble).
 |
 | Screenshots and report.json go to storage/qa/chat-width/. Exit code 1 when a check fails.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = (process.env.CHAT_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const ROOM = `${BASE}/community/chat`;
const OUT = 'storage/qa/chat-width';
const PASSWORD = 'qa-chat-2026';
const WIDTHS = [[390, 844], [768, 1024], [1440, 900], [1920, 1080]];
const THEMES = ['light', 'dark'];
const STAMP = Date.now().toString(36);
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

const setupOut = execFileSync('php', ['artisan', 'tinker', "--execute=require 'tests/qa/chat-fixes-setup.php';"], { encoding: 'utf8' });
const { samples } = JSON.parse(setupOut.trim().split('\n').filter((l) => l.startsWith('{')).pop());
const errors = [];
const browser = await chromium.launch({ channel: 'chrome' });

async function signIn(email) {
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name=email]', email);
    await page.fill('input[name=password]', PASSWORD);
    await Promise.all([page.waitForNavigation(), page.click('form:has(input[name=password]) button[type=submit]')]);
    const state = await context.storageState();
    await context.close();
    return state;
}

async function open(state, { width = 1440, height = 900, theme = 'light' } = {}) {
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height }, colorScheme: theme, storageState: state });
    await context.addCookies([{ name: 'theme', value: theme, url: BASE }]);
    const page = await context.newPage();
    page.on('pageerror', (e) => errors.push(`${width} ${theme}: ${e}`));
    page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource.*(403|404|422)/.test(m.text())) errors.push(`${width} ${theme}: ${m.text()}`); });
    await page.goto(ROOM);
    await page.waitForSelector('[data-chat-room]');
    await page.waitForFunction(() => document.fonts.status === 'loaded').catch(() => {});
    await page.waitForTimeout(600);
    return { context, page };
}

const waitNotSending = (page) => page.waitForFunction(() => !document.querySelector('.chat-message.is-sending'), null, { timeout: 20000 }).catch(() => {});
const room = (page, fn, arg) => page.evaluate(([source, a]) => (0, eval)(source)(Alpine.$data(document.querySelector('[data-chat-room]')), a), [fn.toString(), arg]);

async function waitTrayReady(page) {
    await page.waitForFunction(() => {
        const items = document.querySelectorAll('[data-tray-item]');
        return items.length > 0 && Array.from(items).every((i) => i.dataset.state === 'ready');
    }, null, { timeout: 30000 });
}

/*
 | Paste drawn images (bands, a disc and a label) into the composer. The shared samples are a
 | flat stone colour, the same as an empty cell, so a screenshot of them proves nothing.
 */
async function pasteDrawn(page, labels) {
    await page.evaluate(async (names) => {
        const files = [];
        for (const [i, name] of names.entries()) {
            const canvas = Object.assign(document.createElement('canvas'), { width: 1200, height: 900 });
            const g = canvas.getContext('2d');
            ['#13221C', '#2F4A3E', '#A8864F', '#DCCDA8', '#F3F1EA'].forEach((c, b) => { g.fillStyle = c; g.fillRect(0, b * 180, 1200, 180); });
            g.fillStyle = ['#6F5A34', '#13221C', '#A8864F'][i % 3];
            g.beginPath(); g.arc(300 + i * 250, 450, 220, 0, Math.PI * 2); g.fill();
            g.fillStyle = '#F3F1EA'; g.font = '300 110px serif'; g.fillText(name, 80, 140);
            const blob = await new Promise((r) => canvas.toBlob(r, 'image/png'));
            files.push(new File([blob], 'image.png', { type: 'image/png' }));
        }
        const dt = new DataTransfer();
        files.forEach((f) => dt.items.add(f));
        document.querySelector('#chat-draft').dispatchEvent(new ClipboardEvent('paste', { bubbles: true, cancelable: true, clipboardData: dt }));
    }, labels);
}

async function sendDraft(page, text) {
    await page.locator('#chat-draft').click();
    await page.keyboard.type(text);
    await page.keyboard.press('Enter');
    await waitNotSending(page);
}

/* ------------------------------------------------------------------ content, without a queue worker */
const amina = await signIn('qa.amina@example.com');
const yusuf = await signIn('qa.yusuf@example.com');
{
    const { context, page } = await open(amina);

    // A pasted image with no description.
    await pasteDrawn(page, ['Pasted']);
    await waitTrayReady(page);
    const alt = await page.$eval('[data-alt]', (e) => ({ required: e.required, placeholder: e.placeholder, form: e.form?.noValidate }));
    check('the description is optional (no required, "(optional)" placeholder, form novalidate)', !alt.required && alt.placeholder === 'Add a description (optional)' && alt.form === true, JSON.stringify(alt));
    await sendDraft(page, `Pasted without a description ${STAMP}`);
    check('sending an undescribed image is not stopped', !(await page.locator('[data-send-error]').isVisible()));
    const last = () => page.evaluate(() => {
        const cells = Array.from(document.querySelectorAll('[data-attachment-image]'));
        const cell = cells[cells.length - 1];
        const img = cell?.querySelector('img');
        return { ready: cell?.dataset.thumbReady, loaded: cell?.classList.contains('is-loaded'), src: img?.currentSrc ?? '', natural: img?.naturalWidth ?? 0, alt: img?.alt };
    });
    await page.waitForFunction(() => /\/(original|thumb)\?/.test(Array.from(document.querySelectorAll('[data-attachment-image] img')).pop()?.currentSrc ?? ''), null, { timeout: 15000 }).catch(() => {});
    await page.waitForTimeout(1500);
    const before = await last();
    check('without a worker the original shows (thumbnail not ready)', before.ready === 'false' && /\/original\?/.test(before.src) && before.natural > 0 && before.loaded, JSON.stringify(before));
    check('an undescribed image is "Image shared by Amina Hassan"', before.alt === 'Image shared by Amina Hassan', before.alt);
    await page.locator('[data-attachment-image]').last().screenshot({ path: join(OUT, 'image-before-thumbnail.png') });

    // A worker makes the thumbnail; the next poll swaps it in.
    execFileSync('php', ['artisan', 'queue:work', '--stop-when-empty', '--tries=1'], { encoding: 'utf8' });
    await page.waitForFunction(() => Array.from(document.querySelectorAll('[data-attachment-image]')).pop()?.dataset.thumbReady === 'true', null, { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(1000);
    const after = await last();
    check('after the worker runs, the poll swaps in the WebP thumbnail', after.ready === 'true' && /\/thumb\?/.test(after.src) && after.natural > 0 && after.loaded, JSON.stringify(after));
    await page.locator('[data-attachment-image]').last().screenshot({ path: join(OUT, 'image-after-thumbnail.png') });

    // Three described images, then five documents, then a long paragraph.
    await pasteDrawn(page, ['Plot', 'Profile', 'Sheet']);
    await waitTrayReady(page);
    const alts = ['Students measuring a plot', 'A soil profile drawing', 'A herbarium sheet'];
    for (let i = 0; i < alts.length; i++) await page.locator('[data-alt]').nth(i).fill(alts[i]);
    await sendDraft(page, `Three photographs ${STAMP}`);
    await page.setInputFiles('[data-file-input]', [samples.pdf, samples.docx, samples.xlsx, samples.pptx, samples.csv]);
    await waitTrayReady(page);
    await sendDraft(page, `Five documents ${STAMP}`);
    await sendDraft(page, `A long paragraph ${STAMP}: ${'Soil water moves through the profile by capillary rise and drainage, and every field trial we ran in Gezira showed how much the clay mattered. '.repeat(4)}`);
    execFileSync('php', ['artisan', 'queue:work', '--stop-when-empty', '--tries=1'], { encoding: 'utf8' });
    await context.close();
}

/* ------------------------------------------------------------------ widths, themes, panel */
for (const theme of THEMES) {
    for (const [width, height] of WIDTHS) {
        for (const panel of ['open', 'closed']) {
            const { context, page } = await open(yusuf, { width, height, theme });
            const wide = width >= 1024;
            await room(page, (r, p) => { r.panelOpen = p === 'open'; }, panel);
            await page.waitForTimeout(500);
            await page.evaluate(() => document.querySelector('[data-scroller]').scrollTo({ top: 1e7, behavior: 'instant' }));
            // Show the files: every lazy image near the bottom loads.
            await page.waitForTimeout(1200);
            const label = `${width} ${theme} panel ${panel}`;

            const m = await page.evaluate(() => {
                const box = (el) => el?.getBoundingClientRect();
                const room = box(document.querySelector('.chat-room'));
                const scroller = document.querySelector('[data-scroller]');
                const pad = parseFloat(getComputedStyle(scroller).paddingLeft);
                const log = box(document.querySelector('.chat-log'));
                const messages = Array.from(document.querySelectorAll('.chat-message')).slice(-6).map((el) => box(el).width);
                // Messages sit beside the author's 2rem initials, so their column is the log less that gutter.
                const column = box(Array.from(document.querySelectorAll('.chat-run__messages')).pop()).width;
                const avatar = box(document.querySelector('.chat-run__avatar')).width + parseFloat(getComputedStyle(document.querySelector('.chat-run')).columnGap);
                const inner = box(document.querySelector('.chat-composer__inner'));
                const composerPad = parseFloat(getComputedStyle(document.querySelector('.chat-composer')).paddingLeft);
                const galleries = Array.from(document.querySelectorAll('.chat-attachments')).slice(-3).map((el) => box(el).width);
                const files = Array.from(document.querySelectorAll('.chat-files')).filter((el) => el.offsetParent).pop();
                const fileColumns = files ? getComputedStyle(files).gridTemplateColumns.split(' ').length : 0;
                const fileWidth = files ? box(files).width : 0;
                const gallery = Array.from(document.querySelectorAll('.chat-gallery')).filter((el) => el.offsetParent).pop();
                const galleryWidth = gallery ? box(gallery).width : 0;
                const body = Array.from(document.querySelectorAll('[data-message-body]')).filter((el) => el.textContent.length > 300).pop();
                const fontPx = body ? parseFloat(getComputedStyle(body).fontSize) : 0;
                const bodyWidth = body ? box(body).width : 0;
                const bodyMax = body ? getComputedStyle(body).maxWidth : '';
                return {
                    roomWidth: room.width, pad, logWidth: log.width, expected: room.width - 2 * pad, messages, inner: inner.width,
                    composerExpected: room.width - 2 * composerPad, composerPad, galleries, fileColumns, fileWidth, galleryWidth, bodyWidth, bodyMax, fontPx,
                    column, avatar,
                    overflowX: document.documentElement.scrollWidth - window.innerWidth,
                    panelShown: (() => { const p = document.querySelector('#chat-panel'); return p ? p.getClientRects().length > 0 && getComputedStyle(p).visibility !== 'hidden' : false; })(),
                };
            });

            const gutter = wide ? 40 : 24;
            const near = (a, b) => Math.abs(a - b) <= 1.5;
            check(`${label}: side padding ${gutter}px`, near(m.pad, gutter) && near(m.composerPad, gutter), `${m.pad}/${m.composerPad}`);
            check(`${label}: the message list fills the column`, near(m.logWidth, m.expected), `${m.logWidth} of ${m.expected}`);
            check(`${label}: the message column is the list less the initials`, near(m.column, m.logWidth - m.avatar), `${m.column} of ${m.logWidth} - ${m.avatar}`);
            check(`${label}: every message fills its column`, m.messages.length > 0 && m.messages.every((w) => near(w, m.column)), m.messages.join(','));
            check(`${label}: the composer fills the column`, near(m.inner, m.composerExpected), `${m.inner} of ${m.composerExpected}`);
            check(`${label}: attachments span the column`, m.galleries.length > 0 && m.galleries.every((w) => near(w, m.column)), m.galleries.join(','));
            check(`${label}: the image grid spans the column`, near(m.galleryWidth, m.column), String(m.galleryWidth));
            const expectedColumns = m.fileWidth >= 1024 ? 3 : m.fileWidth >= 640 ? 2 : 1;
            check(`${label}: five documents in ${expectedColumns} column(s)`, m.fileColumns === expectedColumns, `${m.fileColumns} at ${m.fileWidth}px`);
            const measure = parseFloat(m.bodyMax);
            check(`${label}: text keeps a 100ch measure`, measure > 0 && m.bodyWidth <= measure + 1 && near(m.bodyWidth, Math.min(measure, m.column)), `${m.bodyWidth}px, max ${m.bodyMax}`);
            check(`${label}: no horizontal scroll`, m.overflowX <= 0, String(m.overflowX));
            if (wide) check(`${label}: the panel is ${panel}`, m.panelShown === (panel === 'open'), String(m.panelShown));

            // Bring the last photographs into view (with the documents under them) and check no cell is empty.
            await page.evaluate(() => {
                const gallery = Array.from(document.querySelectorAll('.chat-gallery')).filter((el) => el.offsetParent && el.children.length > 1).pop();
                const scroller = document.querySelector('[data-scroller]');
                scroller.scrollTo({ top: scroller.scrollTop + gallery.getBoundingClientRect().top - scroller.getBoundingClientRect().top - 80, behavior: 'instant' });
            });
            await page.waitForTimeout(1500);
            const images = await page.evaluate(() => {
                const s = document.querySelector('[data-scroller]').getBoundingClientRect();
                const visible = Array.from(document.querySelectorAll('[data-attachment-image]')).filter((c) => {
                    const r = c.getBoundingClientRect();
                    return r.bottom > s.top && r.top < s.bottom && r.width > 0;
                });
                return { visible: visible.length, empty: visible.filter((c) => !c.querySelector('img')?.naturalWidth).length, breathing: visible.filter((c) => !c.classList.contains('is-loaded')).length };
            });
            check(`${label}: no empty or still-loading image cell`, images.visible > 0 && images.empty === 0 && images.breathing === 0, JSON.stringify(images));
            await page.screenshot({ path: join(OUT, `room-${width}-${theme}-panel-${panel}.png`) });
            await context.close();
        }
    }
}

/* ------------------------------------------------------------------ no native bubbles */
{
    for (const [path, state] of [['/login', undefined], ['/tributes/new', undefined], ['/search', undefined], ['/community/chat', yusuf], ['/account', yusuf]]) {
        const context = await browser.newContext({ ignoreHTTPSErrors: true, storageState: state });
        const page = await context.newPage();
        await page.goto(`${BASE}${path}`);
        await page.waitForTimeout(500);
        const r = await page.evaluate(() => {
            const forms = Array.from(document.querySelectorAll('form'));
            const probe = document.createElement('input');
            probe.required = true;
            (forms[0] ?? document.body).appendChild(probe);
            const event = new Event('invalid', { cancelable: true });
            probe.dispatchEvent(event);
            probe.remove();
            return { forms: forms.length, quiet: forms.every((f) => f.noValidate), cancelled: event.defaultPrevented };
        });
        check(`${path}: every form is novalidate and invalid is cancelled`, r.forms > 0 && r.quiet && r.cancelled, JSON.stringify(r));
        await context.close();
    }
}

check('no console or page errors', errors.length === 0, errors.slice(0, 5).join(' | '));
await browser.close();
writeFileSync(join(OUT, 'report.json'), JSON.stringify({ results, errors }, null, 2));
console.log(`\n${results.length - failures}/${results.length} checks passed`);
process.exit(failures ? 1 : 0);
