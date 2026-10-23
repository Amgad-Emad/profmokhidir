/*
 | Admin walkthrough as a first-time editor, through Playwright (Google Chrome):
 | sign in, open the command palette, add a publication with a PDF, publish it,
 | find it on the public site, then approve a tribute. Every step is checked
 | and timed; console errors, failed requests and horizontal scroll fail it.
 | Then the dashboard, a list, the profile edit screen (with its save bar) and
 | the command palette are screenshotted at 390, 1024 and 1440px in both themes
 | into storage/qa/admin/ (name-width-theme.png), with a report.json.
 |
 |   ADMIN_QA_EMAIL=... ADMIN_QA_PASSWORD=... npm run qa:admin
 |
 | Needs an editor account and at least one pending tribute (the seeders make
 | three). ADMIN_QA_BASE_URL defaults to https://profmokhidir.test; run
 | `npm run build` first. Exit code 1 when a step fails.
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = (process.env.ADMIN_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const OUT = process.env.ADMIN_QA_OUT ?? 'storage/qa/admin';
const EMAIL = process.env.ADMIN_QA_EMAIL;
const PASSWORD = process.env.ADMIN_QA_PASSWORD;
const WIDTHS = [390, 1024, 1440];
const HEIGHTS = { 390: 844, 1024: 1366, 1440: 900 };
const THEMES = ['light', 'dark'];
const STAMP = Date.now().toString(36);
const TITLE = `Soil moisture and sorghum yields in the Gezira (${STAMP})`;

if (!EMAIL || !PASSWORD) {
    console.error('Set ADMIN_QA_EMAIL and ADMIN_QA_PASSWORD (an editor account).');
    process.exit(2);
}

mkdirSync(OUT, { recursive: true });
const steps = [];
let failures = 0;

function step(name, ok = true, detail = '') {
    steps.push({ name, ok, detail });
    if (!ok) failures += 1;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

/* A one-page PDF, built by hand so the walkthrough needs no fixtures. */
function pdf() {
    const objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        null,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];
    const text = 'BT /F1 18 Tf 72 760 Td (Soil moisture and sorghum yields) Tj ET';
    objects[3] = `<< /Length ${text.length} >>\nstream\n${text}\nendstream`;
    let body = '%PDF-1.4\n';
    const offsets = [];
    objects.forEach((object, index) => {
        offsets.push(body.length);
        body += `${index + 1} 0 obj\n${object}\nendobj\n`;
    });
    const xref = body.length;
    body += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
    body += offsets.map((offset) => `${String(offset).padStart(10, '0')} 00000 n \n`).join('');
    body += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF\n`;
    return Buffer.from(body, 'latin1');
}

function watch(page, label) {
    page.on('console', (message) => {
        if (message.type() === 'error') step(`${label}: no console errors`, false, message.text().slice(0, 200));
    });
    page.on('pageerror', (error) => {
        // Chrome aborts a cross-document view transition when automation navigates again at once; not a page fault.
        if (error.message.includes('Transition was aborted')) return;
        step(`${label}: no page errors`, false, error.message.slice(0, 200));
    });
    page.on('response', (response) => {
        const url = response.url();
        if (url.startsWith(BASE) && response.status() >= 400 && !url.includes('favicon')) {
            step(`${label}: request ok`, false, `${response.status()} ${url}`);
        }
    });
}

async function noHorizontalScroll(page, label) {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    step(`${label}: no horizontal scroll`, overflow <= 1, overflow > 1 ? `${overflow}px wider than the viewport` : '');
}

async function signIn(context) {
    const page = await context.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([page.waitForURL((url) => !url.pathname.startsWith('/login')), page.click('form:has(input[name="password"]) button[type="submit"]')]);
    step('sign in lands on the dashboard', new URL(page.url()).pathname === '/admin', page.url());
    return page;
}

async function openPalette(page, text) {
    await page.waitForLoadState('networkidle');
    await page.keyboard.press('Control+k');
    await page.locator('.palette__input').waitFor();
    await page.waitForFunction(() => document.activeElement?.classList.contains('palette__input'));
    await page.keyboard.type(text);
    await page.waitForTimeout(700);
}

async function toastText(page, containing) {
    const toast = page.locator('[data-toast] .admin-toast__message', { hasText: containing }).last();
    await toast.waitFor({ timeout: 10000 }).catch(() => {});
    return (await toast.count()) ? (await toast.innerText()).trim() : `no toast with "${containing}"`;
}

const browser = await chromium.launch({ channel: process.env.ADMIN_QA_CHANNEL ?? 'chrome', headless: true });
const started = Date.now();

try {
    // ---------------------------------------------------------------- the task, at 1440px, light
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 }, colorScheme: 'light' });
    const page = await signIn(context);
    watch(page, 'walkthrough');

    // The dashboard says what needs doing.
    step('dashboard shows "Needs your attention"', await page.locator('#attention-heading').isVisible());
    step('dashboard shows the launch checklist', await page.locator('[data-checklist]').isVisible());
    step('pending tributes show as a card', await page.locator('[data-queue="tributes"]').isVisible());

    // The command palette finds "New publication".
    await openPalette(page, 'new publ');
    const firstOption = page.locator('.palette__option').first();
    step('palette offers New publication first', (await firstOption.innerText()).includes('New publication'));
    await Promise.all([page.waitForURL('**/content/publications/create'), page.keyboard.press('Enter')]);
    step('Enter opens the new publication screen', page.url().endsWith('/admin/content/publications/create'));

    // Fill in the publication as an editor would.
    await page.fill('#form-title', TITLE);
    await page.selectOption('#form-type', 'journal_article').catch(() => {});
    await page.fill('#form-year', '1978');
    await page.fill('#form-authors', 'Khidir, M. O., Hassan, A.');
    await page.fill('#form-venue-publisher', 'Sudan Journal of Agricultural Research');
    await page.locator('#form-venue-publisher').blur();
    await page.waitForFunction(() => document.querySelector('[data-save-note]')?.textContent?.startsWith('Saved'), null, { timeout: 15000 })
        .then(() => step('the draft autosaves when a field is left'))
        .catch(async () => step('the draft autosaves when a field is left', false, `save bar says "${await page.locator('[data-save-note]').innerText()}"`));
    step('the address moved to the edit screen', /\/content\/publications\/\d+\/edit$/.test(page.url()), page.url());

    await page.locator('input[type=file]').first().setInputFiles({ name: 'sorghum-yields.pdf', mimeType: 'application/pdf', buffer: pdf() });
    await page.locator('[data-toast]', { hasText: 'attached' }).waitFor({ timeout: 20000 })
        .then(async () => step('the PDF uploads and shows its name', (await page.locator('[data-current-file]').first().innerText()).includes('sorghum-yields.pdf')))
        .catch(async () => step('the PDF uploads and shows its name', false, (await page.locator('[data-upload-field]').first().innerText()).replace(/\s+/g, ' ').slice(0, 200)));

    // The save bar and the section nav are where the editor needs them.
    step('save bar is visible without scrolling', await page.locator('[data-save-bar]').isVisible());
    step('section nav lists the parts of the form', (await page.locator('.edit-layout__link').count()) >= 4);

    // Preview opens the real public page, signed.
    const [preview] = await Promise.all([context.waitForEvent('page'), page.click('[data-preview]')]);
    await preview.waitForURL((url) => url.href !== 'about:blank', { timeout: 15000 }).catch(() => {});
    await preview.waitForLoadState('domcontentloaded');
    step('preview shows the draft on the public page', (await preview.content()).includes(TITLE.split(' (')[0]) && (await preview.locator('[data-preview-banner]').isVisible()), preview.url().split('?')[0]);
    await preview.close();

    // Publish with the keyboard shortcut.
    await page.keyboard.press('Control+Enter');
    const published = await toastText(page, 'published');
    step('Ctrl+Enter publishes with a toast that says so', published.includes('published'), published);
    step('the save bar now offers Update and Unpublish', await page.locator('[data-unpublish]').isVisible());
    await page.screenshot({ path: join(OUT, 'walkthrough-published-1440-light.png') });

    // View it on the public site.
    const [site] = await Promise.all([context.waitForEvent('page'), page.click('[data-save-bar] [data-view-on-site]')]);
    await site.waitForLoadState('load');
    step('the publication is on the public page', (await site.content()).includes(TITLE.split(' (')[0]), site.url());
    await site.close();

    // Approve a tribute.
    await page.click('[data-nav-item="tributes"]');
    await page.waitForURL('**/admin/tributes');
    const firstTribute = page.locator('[data-tribute]').first();
    const who = (await firstTribute.locator('p.font-medium').first().innerText()).trim();
    await firstTribute.locator('[data-approve]').click();
    const approved = await toastText(page, 'approved');
    step('approving a tribute confirms it by name', approved.includes(who) && approved.includes('approved'), approved);

    // Keyboard sheet.
    await page.keyboard.press('?');
    step('"?" opens the shortcuts sheet', await page.locator('#shortcuts-title').waitFor({ timeout: 5000 }).then(() => true).catch(() => false));
    await page.keyboard.press('Escape');
    step('Escape closes the shortcuts sheet', await page.locator('#shortcuts-title').waitFor({ state: 'hidden', timeout: 5000 }).then(() => true).catch(() => false));

    // Undo after a move to the trash.
    await page.goto(`${BASE}/admin/content/publications`);
    await page.locator('[data-filter="status"]').waitFor();
    await page.keyboard.press('/');
    step('"/" jumps to the list search', await page.evaluate(() => document.activeElement?.id === 'search'));
    await page.keyboard.type(STAMP);
    await page.locator('tr', { hasText: TITLE }).waitFor({ timeout: 8000 });
    const row = page.locator('tr', { hasText: TITLE });
    await row.locator('.row-menu__button').click();
    await row.locator('[data-delete]').click();
    const trashed = await toastText(page, 'trash');
    step('move to trash needs no dialog and offers Undo', trashed.includes('trash') && (await page.locator('[data-toast-undo]').last().isVisible()), trashed);
    await page.locator('[data-toast-undo]').last().click();
    const restored = await toastText(page, 'restored');
    step('Undo restores it from the trash', restored.includes('restored'), restored);

    step('the whole task took', true, `${Math.round((Date.now() - started) / 1000)}s`);
    const state = await context.storageState();
    await context.close();

    // ---------------------------------------------------------------- screenshots
    for (const theme of THEMES) {
        for (const width of WIDTHS) {
            const shots = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height: HEIGHTS[width] }, colorScheme: theme, storageState: state, reducedMotion: 'reduce' });
            const shot = await shots.newPage();
            watch(shot, `${width}/${theme}`);
            const label = `${width}-${theme}`;

            await shot.goto(`${BASE}/admin`);
            await noHorizontalScroll(shot, `dashboard ${label}`);
            await shot.screenshot({ path: join(OUT, `dashboard-${label}.png`), fullPage: true });

            await shot.goto(`${BASE}/admin/content/publications`);
            await noHorizontalScroll(shot, `list ${label}`);
            await shot.screenshot({ path: join(OUT, `list-${label}.png`), fullPage: true });

            await shot.goto(`${BASE}/admin/content/profile`);
            await noHorizontalScroll(shot, `profile ${label}`);
            await shot.screenshot({ path: join(OUT, `profile-${label}.png`) });

            await shot.goto(`${BASE}/admin`);
            await openPalette(shot, 'soil');
            await shot.screenshot({ path: join(OUT, `palette-${label}.png`) });

            await shots.close();
        }
    }
} catch (error) {
    step('walkthrough finished', false, error.message.split('\n')[0]);
} finally {
    await browser.close();
}

writeFileSync(join(OUT, 'report.json'), JSON.stringify({ base: BASE, generated: new Date().toISOString(), failures, steps }, null, 2));
console.log(`\n${steps.length} checks, ${failures} failing. Screenshots and report in ${OUT}`);
process.exit(failures === 0 ? 0 : 1);
