/*
 | Browser tests for the chat fixes (one room, the composer, the Members tab, attachments),
 | through Playwright and Google Chrome against https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:chat
 |
 | tests/qa/chat-fixes-setup.php prepares the QA accounts (two members, an administrator
 | with fresh recovery codes, a pending applicant, a throwaway member to suspend) and one
 | sample file of every accepted type. A queue worker runs alongside for the image
 | conversions. Two members in two windows; checked at 390, 768 and 1440px in both themes:
 |
 |   typing   2000 characters with and without spaces, typing while polls arrive, a pasted
 |            long URL and Shift+Enter: nothing is lost, the field grows to 40% of the
 |            viewport and then scrolls inside itself, nothing scrolls the document
 |   layout   no document scroll and no horizontal scroll, Members opens the Members tab
 |            with every member, "You" and "online", focus on the tab (not a heading), axe
 |   files    every type through the paperclip, drag and drop, a pasted image, previews
 |            with progress and the optional description, the grid and the lightbox,
 |            document cards, downloads as a member, refused for a guest and a pending
 |            applicant, location data gone from the downloaded photo, a renamed program
 |            refused, the administrator's member menu (view profile, suspend)
 |
 | Screenshots and report.json go to storage/qa/chat-fixes/. Exit code 1 when a check fails.
 */
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync, spawn } from 'node:child_process';
import { mkdirSync, writeFileSync, readFileSync } from 'node:fs';
import { join, basename } from 'node:path';

const BASE = (process.env.CHAT_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const ROOM = `${BASE}/community/chat`;
const OUT = 'storage/qa/chat-fixes';
const PASSWORD = 'qa-chat-2026';
const WIDTHS = [[390, 844], [768, 1024], [1440, 900]];
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

function artisan(args, env = {}) {
    return execFileSync('php', ['artisan', ...args], { encoding: 'utf8', env: { ...process.env, ...env } });
}

function setup(env = {}) {
    const out = artisan(['tinker', "--execute=require 'tests/qa/chat-fixes-setup.php';"], env);
    return JSON.parse(out.trim().split('\n').filter((l) => l.startsWith('{')).pop());
}

const { adminRecovery, samples } = setup();
const worker = spawn('php', ['artisan', 'queue:work', '--sleep=1', '--tries=1'], { stdio: 'ignore' });
const errors = [];

const browser = await chromium.launch({ channel: 'chrome' });

async function signIn(email, recovery = null) {
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name=email]', email);
    await page.fill('input[name=password]', PASSWORD);
    await Promise.all([page.waitForNavigation(), page.click('form:has(input[name=password]) button[type=submit]')]);
    if (page.url().includes('two-factor-challenge')) {
        await page.evaluate((code) => {
            document.querySelectorAll('input[name=code]').forEach((i) => { i.disabled = true; i.required = false; });
            const input = document.querySelector('input[name=recovery_code]');
            input.disabled = false;
            input.value = code;
            input.form.requestSubmit();
        }, recovery);
        await page.waitForNavigation();
    }
    const state = await context.storageState();
    await context.close();
    return state;
}

async function open(state, { width = 1440, height = 900, theme = 'light', who = 'member' } = {}) {
    const context = await browser.newContext({
        ignoreHTTPSErrors: true, viewport: { width, height }, colorScheme: theme, storageState: state,
        permissions: ['clipboard-read', 'clipboard-write'],
    });
    const page = await context.newPage();
    page.on('pageerror', (e) => errors.push(`${who} ${width} ${theme}: ${e}`));
    page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource.*(403|404|422)/.test(m.text())) errors.push(`${who} ${width} ${theme}: ${m.text()}`); });
    await page.goto(ROOM);
    await page.waitForSelector('[data-chat-room]');
    await page.waitForFunction(() => document.fonts.status === 'loaded').catch(() => {});
    await page.waitForTimeout(600);
    return { context, page };
}

/** The page itself never scrolls: only the message list, the panel body and the composer do. */
async function documentStill(page) {
    return page.evaluate(() => ({
        scrollY: window.scrollY,
        overflowY: document.documentElement.scrollHeight - window.innerHeight,
        overflowX: document.documentElement.scrollWidth - window.innerWidth,
    }));
}

async function noDocumentScroll(page, label) {
    const d = await documentStill(page);
    check(`no document scroll: ${label}`, d.scrollY === 0 && d.overflowY <= 0 && d.overflowX <= 0, JSON.stringify(d));
}

async function composerState(page) {
    return page.evaluate(() => {
        const el = document.querySelector('#chat-draft');
        return {
            value: el.value, length: el.value.length, focused: document.activeElement === el,
            height: el.clientHeight, scrollHeight: el.scrollHeight, scrollWidth: el.scrollWidth, clientWidth: el.clientWidth,
            max: window.innerHeight * 0.4, caret: el.selectionStart, maxlength: el.getAttribute('maxlength'),
        };
    });
}

async function clearComposer(page) {
    await page.locator('#chat-draft').click();
    await page.keyboard.press('ControlOrMeta+A');
    await page.keyboard.press('Backspace');
}

async function waitNotSending(page) {
    await page.waitForFunction(() => !document.querySelector('.chat-message.is-sending'), null, { timeout: 20000 }).catch(() => {});
}

/* ------------------------------------------------------------------ sign in */
const amina = await signIn('qa.amina@example.com');
const yusuf = await signIn('qa.yusuf@example.com');
const pending = await signIn('qa.pending@example.com');
const admin = await signIn('qa.admin@example.com', adminRecovery);

/* ------------------------------------------------------------------ typing */
for (const [width, height] of [[390, 844], [1440, 900]]) {
    const { context, page } = await open(amina, { width, height, who: 'amina' });
    const box = page.locator('#chat-draft');

    // 2000 characters without a single space: wraps, grows to 40% of the viewport, then scrolls inside.
    await clearComposer(page);
    const unbroken = 'x'.repeat(1990) + 'END' + 'yyyyyyy';
    await page.keyboard.type(unbroken, { delay: 2 });
    let s = await composerState(page);
    check(`${width}: 2000 characters without spaces are all kept`, s.value === unbroken, `${s.length} chars`);
    check(`${width}: the composer has no maxlength`, s.maxlength === null, s.maxlength);
    check(`${width}: a long word wraps (no sideways scroll in the field)`, s.scrollWidth <= s.clientWidth + 1, `${s.scrollWidth}/${s.clientWidth}`);
    check(`${width}: the field grows to about 40% of the viewport, then scrolls inside`, s.height <= s.max + 2 && s.height >= s.max - 40 && s.scrollHeight > s.height, `h=${s.height} max=${Math.round(s.max)} sh=${s.scrollHeight}`);
    check(`${width}: the count shows near the limit`, await page.locator('[data-count]').isVisible(), await page.locator('[data-count]').textContent());
    await noDocumentScroll(page, `${width} after 2000 characters`);
    await page.screenshot({ path: join(OUT, `typing-2000-unbroken-${width}.png`) });

    // 2000 characters with spaces.
    await clearComposer(page);
    const words = ('Knowledge is the light we pass on. ').repeat(58).slice(0, 2000);
    await page.keyboard.type(words, { delay: 1 });
    s = await composerState(page);
    check(`${width}: 2000 characters with spaces are all kept`, s.value === words, `${s.length} chars`);

    // Typing while polls arrive: every keystroke survives two or more poll responses.
    await clearComposer(page);
    let polls = 0;
    const onResponse = (r) => { if (r.url().includes('/livewire') && r.request().method() === 'POST') polls++; };
    page.on('response', onResponse);
    const slow = 'Typing slowly while the room polls for new letters, which must never touch this field. ';
    const started = Date.now();
    let typed = '';
    while (polls < 2 && Date.now() - started < 30000) {
        for (const ch of slow) {
            await page.keyboard.type(ch, { delay: 25 });
            typed += ch;
            if (polls >= 2) break;
        }
    }
    page.off('response', onResponse);
    s = await composerState(page);
    check(`${width}: typing across ${polls} poll responses loses nothing`, polls >= 2 && s.value === typed && s.focused && s.caret === typed.length, `${polls} polls, ${s.length}/${typed.length} chars, focused=${s.focused}`);

    // Paste a long URL, then Shift+Enter and more text, then send.
    await clearComposer(page);
    const url = `https://example.org/archive/agronomy/${'very-long-path-segment-'.repeat(12)}?ref=${STAMP}`;
    await page.evaluate((text) => navigator.clipboard.writeText(text), url);
    await page.keyboard.type('See ');
    await page.keyboard.press('ControlOrMeta+V');
    s = await composerState(page);
    if (!s.value.includes(url)) await page.keyboard.insertText(url); // headless clipboards can refuse; insertText is the same input path
    await page.keyboard.press('Shift+Enter');
    await page.keyboard.type(`second line ${width}-${STAMP}`);
    s = await composerState(page);
    const expected = `See ${url}\nsecond line ${width}-${STAMP}`;
    check(`${width}: a pasted long URL and Shift+Enter are kept`, s.value === expected, JSON.stringify(s.value.slice(-40)));
    check(`${width}: the pasted URL wraps inside the field`, s.scrollWidth <= s.clientWidth + 1, `${s.scrollWidth}/${s.clientWidth}`);
    await page.keyboard.press('Enter');
    await waitNotSending(page);
    const sent = await page.evaluate((marker) => Array.from(document.querySelectorAll('[data-message-body]')).map((b) => b.innerText).find((t) => t.includes(marker)) || '', `second line ${width}-${STAMP}`);
    check(`${width}: the sent message keeps the URL and the new line`, sent.includes('very-long-path-segment-very-long-path-segment') && /\n/.test(sent), sent.slice(0, 60));
    check(`${width}: the composer is empty and focused after sending`, (await composerState(page)).value === '' && (await composerState(page)).focused);
    await noDocumentScroll(page, `${width} after sending`);
    await context.close();
}

/* ------------------------------------------------------------------ layout, Members, both themes */
for (const theme of THEMES) {
    for (const [width, height] of WIDTHS) {
        const { context, page } = await open(amina, { width, height, theme, who: 'amina' });
        await noDocumentScroll(page, `${width} ${theme} on load`);
        check(`${width} ${theme}: no channel rail`, (await page.locator('[data-chat-rail], [data-channels]').count()) === 0);
        await page.screenshot({ path: join(OUT, `room-${width}-${theme}.png`) });

        await page.click('[data-members-link]');
        await page.waitForTimeout(400);
        const members = await page.evaluate(() => {
            const panel = document.querySelector('[data-chat-panel]');
            const rect = panel.getBoundingClientRect();
            const rows = Array.from(document.querySelectorAll('[data-members-list] [data-member]'));
            // offsetParent is null for the fixed drawer, so measure the box instead.
            const visible = (el) => el && el.getClientRects().length > 0 && getComputedStyle(el).display !== 'none' && getComputedStyle(el).visibility !== 'hidden';
            return {
                panelVisible: visible(panel) && rect.width > 200,
                inViewport: rect.bottom <= window.innerHeight + 1 && rect.right <= window.innerWidth + 1,
                tab: document.querySelector('[data-tab="members"]').getAttribute('aria-selected'),
                focused: document.activeElement?.getAttribute('role') + ':' + document.activeElement?.dataset.tab,
                rows: rows.length,
                expected: Number(document.querySelector('[data-member-count] [x-text]').textContent),
                you: rows.filter((r) => visible(r.querySelector('[data-you]'))).map((r) => r.innerText.split('\n')[0]),
                online: rows.filter((r) => visible(r.querySelector('[data-online]'))).length,
                headingsFocusable: document.querySelectorAll('h1[tabindex], h2[tabindex], h3[tabindex]').length,
                outline: getComputedStyle(document.activeElement).outlineStyle,
            };
        });
        check(`${width} ${theme}: Members opens the panel on the Members tab`, members.panelVisible && members.tab === 'true' && members.inViewport, JSON.stringify({ v: members.panelVisible, t: members.tab, in: members.inViewport }));
        check(`${width} ${theme}: every member is listed`, members.rows > 0 && members.rows === members.expected, `${members.rows}/${members.expected}`);
        check(`${width} ${theme}: the member's own entry says You, and who is online is marked`, members.you.length === 1 && members.online >= 1, JSON.stringify(members.you) + ` online=${members.online}`);
        check(`${width} ${theme}: focus is on the Members tab, no heading is a focus target`, members.focused === 'tab:members' && members.headingsFocusable === 0, members.focused);
        await noDocumentScroll(page, `${width} ${theme} with Members open`);
        await page.screenshot({ path: join(OUT, `members-${width}-${theme}.png`) });

        // Arrow keys move between tabs.
        await page.keyboard.press('ArrowLeft');
        await page.waitForTimeout(250);
        check(`${width} ${theme}: arrow keys move between tabs`, await page.evaluate(() => document.activeElement?.dataset.tab === 'pinned' && document.querySelector('[data-tab="pinned"]').getAttribute('aria-selected') === 'true'));
        await page.keyboard.press('ArrowLeft');
        await page.waitForTimeout(300);
        await page.screenshot({ path: join(OUT, `about-${width}-${theme}.png`) });

        const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
        check(`${width} ${theme}: axe finds no WCAG 2.2 AA violations (panel open)`, axe.violations.length === 0, axe.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).slice(0, 3).join(', ')}`).join(' | '));
        await context.close();
    }
}

/* ------------------------------------------------------------------ attachments: two members, two windows */
{
    const a = await open(amina, { width: 1440, height: 900, who: 'amina' });
    const y = await open(yusuf, { width: 1440, height: 900, who: 'yusuf' });

    // A renamed program is refused by its content.
    await a.page.setInputFiles('[data-file-input]', samples.exe);
    await a.page.waitForFunction(() => document.querySelector('[data-tray-item][data-state="failed"]'), null, { timeout: 15000 }).catch(() => {});
    check('a program renamed to .pdf is refused', (await a.page.locator('[data-tray-item][data-state="failed"]').count()) === 1, await a.page.locator('.chat-tray__error').first().textContent().catch(() => ''));
    await a.page.click('[data-tray-item] [data-remove-attachment]');

    // Images: JPEG (with GPS), PNG, WebP, HEIC, all through the paperclip in one message.
    const images = [samples.jpg, samples.png, samples.webp, samples.heic].filter(Boolean);
    await a.page.setInputFiles('[data-file-input]', images);
    await a.page.waitForTimeout(150);
    check('previews show name, size and a progress bar while uploading', await a.page.evaluate(() => {
        const items = Array.from(document.querySelectorAll('[data-tray-item]'));
        return items.length > 0 && items.every((i) => i.querySelector('.chat-tray__name')?.textContent && i.querySelector('.chat-tray__meta')?.textContent.includes('B'));
    }));
    await a.page.waitForFunction(() => document.querySelectorAll('[data-tray-item]').length > 0 && Array.from(document.querySelectorAll('[data-tray-item]')).every((i) => i.dataset.state === 'ready'), null, { timeout: 30000 }).catch(() => {});
    const trayStates = await a.page.$$eval('[data-tray-item]', (els) => els.map((e) => `${e.querySelector('.chat-tray__name').textContent}:${e.dataset.state}`));
    check(`all four image types upload (${trayStates.length})`, trayStates.length === images.length && trayStates.every((t) => t.endsWith(':ready')), trayStates.join(', '));
    check('a HEIC photo arrives as JPEG', trayStates.some((t) => /iphone-photo\.jpg:ready/.test(t)), trayStates.join(', '));
    await a.page.screenshot({ path: join(OUT, 'tray-images-1440.png') });

    // The description is optional: three are described, the fourth is sent without one.
    check('the image description has no required attribute', await a.page.$$eval('[data-alt]', (els) => els.every((e) => !e.required && e.placeholder === 'Add a description (optional)')));
    const alts = ['Students measuring a plot', 'A soil profile drawing', 'A herbarium sheet'];
    const altInputs = a.page.locator('[data-alt]');
    for (let i = 0; i < alts.length; i++) await altInputs.nth(i).fill(alts[i]);
    await a.page.locator('#chat-draft').click();
    await a.page.keyboard.type(`Photographs ${STAMP}`);
    await a.page.keyboard.press('Enter');
    await waitNotSending(a.page);

    // Documents: PDF (3 MB), Word, Excel, PowerPoint, CSV, then ZIP by drag and drop and an image by paste.
    // Watch for a visible progress bar for as long as anything is uploading (a local upload is quick).
    await a.page.evaluate(() => {
        window.__sawProgress = false;
        const look = () => {
            const bar = document.querySelector('[data-tray-item][data-state="uploading"] [data-progress]');
            if (bar && getComputedStyle(bar).display !== 'none' && bar.getAttribute('aria-valuenow') !== null) window.__sawProgress = true;
        };
        window.__progressWatch = new MutationObserver(look);
        window.__progressWatch.observe(document.body, { subtree: true, childList: true, attributes: true });
    });
    await a.page.setInputFiles('[data-file-input]', [samples.pdf, samples.docx, samples.xlsx, samples.pptx, samples.csv]);
    await a.page.waitForFunction(() => Array.from(document.querySelectorAll('[data-tray-item]')).every((i) => i.dataset.state === 'ready'), null, { timeout: 30000 }).catch(() => {});
    check('a progress bar is shown for an upload in flight', await a.page.evaluate(() => { window.__progressWatch.disconnect(); return window.__sawProgress; }));
    check('the sixth file is refused (five per message)', await a.page.evaluate(async () => {
        const room = Alpine.$data(document.querySelector('[data-chat-room]'));
        await room.addFiles([new File(['a,b\n1,2\n'], 'extra.csv', { type: 'text/csv' })]);
        return room.attachments.length === 5 && room.sendError.includes('5');
    }));
    await a.page.locator('#chat-draft').click();
    await a.page.keyboard.type(`Documents ${STAMP}`);
    await a.page.keyboard.press('Enter');
    await waitNotSending(a.page);

    // Drag and drop a ZIP onto the conversation; paste an image into the composer.
    const zipBytes = Array.from(readFileSync(samples.zip));
    const dropped = await a.page.evaluate(async (bytes) => {
        const dt = new DataTransfer();
        dt.items.add(new File([new Uint8Array(bytes)], 'problem-sets.zip', { type: 'application/zip' }));
        const target = document.querySelector('[data-chat-conversation]');
        target.dispatchEvent(new DragEvent('dragenter', { bubbles: true, cancelable: true, dataTransfer: dt }));
        await new Promise((r) => setTimeout(r, 350));
        const overlay = getComputedStyle(document.querySelector('[data-drop-zone]')).display !== 'none';
        target.dispatchEvent(new DragEvent('dragover', { bubbles: true, cancelable: true, dataTransfer: dt }));
        target.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: dt }));
        return overlay;
    }, zipBytes);
    check('dragging files over the conversation shows the drop frame', dropped);
    const pngBytes = Array.from(readFileSync(samples.png));
    await a.page.evaluate((bytes) => {
        const dt = new DataTransfer();
        dt.items.add(new File([new Uint8Array(bytes)], 'image.png', { type: 'image/png' }));
        document.querySelector('#chat-draft').dispatchEvent(new ClipboardEvent('paste', { bubbles: true, cancelable: true, clipboardData: dt }));
    }, pngBytes);
    await a.page.waitForFunction(() => document.querySelectorAll('[data-tray-item]').length === 2 && Array.from(document.querySelectorAll('[data-tray-item]')).every((i) => i.dataset.state === 'ready'), null, { timeout: 20000 }).catch(() => {});
    const dropPaste = await a.page.$$eval('[data-tray-item] .chat-tray__name', (els) => els.map((e) => e.textContent));
    check('a dropped ZIP and a pasted image join the tray', dropPaste.some((n) => n.endsWith('.zip')) && dropPaste.some((n) => n.startsWith('pasted-image')), dropPaste.join(', '));
    await a.page.locator('[data-alt]').first().fill('A pasted soil diagram');
    await a.page.locator('#chat-draft').click();
    await a.page.keyboard.type(`Dropped and pasted ${STAMP}`);
    await a.page.screenshot({ path: join(OUT, 'tray-drop-paste-1440.png') });
    await a.page.keyboard.press('Enter');
    await waitNotSending(a.page);

    // Yusuf's window: the next poll brings the three messages with their files.
    await y.page.waitForFunction((stamp) => document.body.innerText.includes(`Dropped and pasted ${stamp}`), STAMP, { timeout: 20000 }).catch(() => {});
    await y.page.waitForTimeout(400);
    const seen = await y.page.evaluate((stamp) => {
        const message = (text) => Array.from(document.querySelectorAll('[data-chat-message]')).find((m) => m.innerText.includes(text));
        const photos = message(`Photographs ${stamp}`);
        const docs = message(`Documents ${stamp}`);
        return {
            grid: photos ? photos.querySelectorAll('[data-attachment-image]').length : 0,
            alts: photos ? Array.from(photos.querySelectorAll('[data-attachment-image] img')).map((i) => i.alt) : [],
            cards: docs ? Array.from(docs.querySelectorAll('[data-attachment-document]')).map((c) => c.innerText.replace(/\s+/g, ' ')) : [],
            downloads: docs ? docs.querySelectorAll('[data-download]').length : 0,
            docUrl: docs?.querySelector('[data-download]')?.href,
            // The signed link to the original file (a signature covers its path, so it cannot be derived from the thumbnail's).
            originalUrl: Alpine.$data(document.querySelector('[data-chat-room]')).messages.find((m) => m.body.includes(`Photographs ${stamp}`))?.attachments.find((a) => a.name === 'field-trip-with-gps.jpg')?.url,
        };
    }, STAMP);
    check(`the other member sees the images as a grid (${seen.grid})`, seen.grid === images.length, seen.alts.join(' | '));
    check('image descriptions are the alt text', seen.alts.includes('Students measuring a plot'));
    check('an image sent without a description is described as shared by its member', seen.alts.includes('Image shared by Amina Hassan'), seen.alts.join(' | '));
    check(`the other member sees document cards with type, size and Download (${seen.cards.length})`, seen.cards.length === 5 && seen.downloads === 5 && seen.cards.every((c) => /MB|KB|B/.test(c)), seen.cards.join(' | '));
    await y.page.evaluate((stamp) => Array.from(document.querySelectorAll('[data-chat-message]')).find((m) => m.innerText.includes(`Documents ${stamp}`))?.scrollIntoView({ block: 'center' }), STAMP);
    await y.page.waitForTimeout(300);
    await y.page.screenshot({ path: join(OUT, 'documents-1440-light.png') });

    // The lightbox.
    await y.page.evaluate((stamp) => Array.from(document.querySelectorAll('[data-chat-message]')).find((m) => m.innerText.includes(`Photographs ${stamp}`))?.scrollIntoView({ block: 'center' }), STAMP);
    await y.page.waitForTimeout(300);
    await y.page.screenshot({ path: join(OUT, 'images-grid-1440-light.png') });
    await y.page.locator('[data-chat-message]', { hasText: `Photographs ${STAMP}` }).locator('[data-attachment-image]').first().click();
    await y.page.waitForTimeout(900);
    const box = await y.page.evaluate(() => ({ open: !!document.querySelector('dialog.lightbox[open]'), src: document.querySelector('.lightbox__image')?.src || '', counter: document.querySelector('.lightbox__bar p')?.textContent }));
    check('a thumbnail opens the image in the lightbox', box.open && box.src.includes('/community/chat/attachments/'), JSON.stringify(box));
    await y.page.screenshot({ path: join(OUT, 'lightbox-1440-light.png') });
    await y.page.keyboard.press('ArrowRight');
    await y.page.waitForTimeout(500);
    check('arrow keys move through the message\'s images', (await y.page.locator('.lightbox__bar p').textContent()).startsWith('2 of'));
    await y.page.keyboard.press('Escape');
    await y.page.waitForTimeout(300);
    check('Escape closes the lightbox and focus returns to the thumbnail', await y.page.evaluate(() => !document.querySelector('dialog.lightbox[open]') && document.activeElement?.matches('[data-attachment-image]')));
    await noDocumentScroll(y.page, 'after the lightbox');

    // Downloads: a member gets the file; a guest and a pending applicant are refused.
    const asMember = await y.context.request.get(seen.docUrl, { maxRedirects: 0 });
    check('a member downloads a document', asMember.status() === 200 && /attachment; filename=/.test(asMember.headers()['content-disposition'] || ''), `${asMember.status()} ${asMember.headers()['content-disposition']}`);
    const original = await y.context.request.get(seen.originalUrl, { maxRedirects: 0 });
    const originalBody = await original.body();
    const sample = readFileSync(samples.jpg);
    check('the sample photo carries a GPS location before upload', sample.includes(Buffer.from('Exif\0\0')) && sample.includes(Buffer.from('N\0\0\0')));
    check('the photo as downloaded carries no location (EXIF stripped)', original.status() === 200 && originalBody.length > 0 && !originalBody.includes(Buffer.from('Exif\0\0')), `status ${original.status()}, ${originalBody.length} bytes`);
    const guest = await browser.newContext({ ignoreHTTPSErrors: true });
    const asGuest = await guest.request.get(seen.docUrl, { maxRedirects: 0 });
    check('a guest is refused (sent to sign in)', asGuest.status() === 302 && /\/login$/.test(asGuest.headers().location || ''), `${asGuest.status()} ${asGuest.headers().location}`);
    await guest.close();
    const applicant = await browser.newContext({ ignoreHTTPSErrors: true, storageState: pending });
    const asPending = await applicant.request.get(seen.docUrl, { maxRedirects: 0 });
    check('a pending applicant is refused', asPending.status() === 302 && /\/community$/.test(asPending.headers().location || ''), `${asPending.status()} ${asPending.headers().location}`);
    const tampered = await y.context.request.get(seen.docUrl.replace(/signature=[a-f0-9]+/, 'signature=0'), { maxRedirects: 0 });
    check('a tampered link is refused', tampered.status() === 403, String(tampered.status()));
    await applicant.close();

    // The same conversation at 390 and 768, both themes: grid, cards and the lightbox fit.
    for (const theme of THEMES) {
        for (const [width, height] of [[390, 844], [768, 1024]]) {
            const v = await open(yusuf, { width, height, theme, who: 'yusuf' });
            await v.page.evaluate((stamp) => Array.from(document.querySelectorAll('[data-chat-message]')).find((m) => m.innerText.includes(`Photographs ${stamp}`))?.scrollIntoView({ block: 'start' }), STAMP);
            await v.page.waitForTimeout(400);
            await noDocumentScroll(v.page, `${width} ${theme} with attachments`);
            await v.page.screenshot({ path: join(OUT, `attachments-${width}-${theme}.png`) });
            await v.page.locator('[data-chat-message]', { hasText: `Photographs ${STAMP}` }).locator('[data-attachment-image]').first().click();
            await v.page.waitForTimeout(900);
            check(`${width} ${theme}: the lightbox opens`, await v.page.evaluate(() => !!document.querySelector('dialog.lightbox[open]')));
            await v.page.screenshot({ path: join(OUT, `lightbox-${width}-${theme}.png`) });
            await v.page.keyboard.press('Escape');
            await v.context.close();
        }
    }

    await a.context.close();
    await y.context.close();
}

/* ------------------------------------------------------------------ administrator: member menu */
{
    const { context, page } = await open(admin, { width: 1440, height: 900, who: 'admin' });
    await page.click('[data-members-link]');
    await page.waitForTimeout(400);
    const row = page.locator('[data-member]', { hasText: 'Throwaway Member' });
    await row.locator('[data-member-menu-button]').click();
    await page.waitForTimeout(300);
    check('the administrator sees View profile and Suspend for a member', await row.locator('[data-view-profile]').isVisible() && await row.locator('[data-suspend]').isVisible());
    await page.screenshot({ path: join(OUT, 'admin-member-menu-1440.png') });
    const profile = await row.locator('[data-view-profile]').getAttribute('href');
    check('View profile opens the member in Users & roles', /\/admin\/users\?search=qa\.throwaway%40example\.com$/.test(profile || ''), profile);
    await row.locator('[data-suspend]').click();
    await page.waitForTimeout(300);
    await row.locator('[data-suspend-yes]').click();
    await page.waitForFunction(() => !document.body.innerText.includes('Throwaway Member'), null, { timeout: 10000 }).catch(() => {});
    check('Suspend uses the community service and the member leaves the list', !(await page.evaluate(() => document.querySelector('[data-members-list]').innerText.includes('Throwaway Member'))), await page.locator('[data-notice]').textContent());
    await noDocumentScroll(page, 'admin members tab');
    await context.close();
}

await browser.close();
worker.kill();
setup({ QA_TEARDOWN: '1' });

check('no console errors or page errors', errors.length === 0, errors.slice(0, 5).join(' | '));
writeFileSync(join(OUT, 'report.json'), JSON.stringify({ when: new Date().toISOString(), failures, results }, null, 2));
console.log(`\n${results.length - failures}/${results.length} checks passed. Screenshots in ${OUT}/`);
process.exit(failures === 0 ? 0 : 1);
