/*
 | Admin › Pages walkthrough through Playwright (Google Chrome): sign in as an
 | editor, open the Pages list, open the Home editor with the side-by-side
 | preview, change the hero tagline, wait for the autosave and check the
 | preview frame shows the draft while the public home page does not, then
 | discard the draft so the dev database is left as it was. Screenshots of the
 | list and the editor (1440px, both themes; 1024 and 390 light) go to
 | storage/qa/pages-cms/ with a report.json. Console errors, failed requests
 | and horizontal scroll fail it.
 |
 |   PAGES_QA_EMAIL=... PAGES_QA_PASSWORD=... npm run qa:pages
 |
 | PAGES_QA_BASE_URL defaults to https://profmokhidir.test; run `npm run build` first.
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = (process.env.PAGES_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const OUT = process.env.PAGES_QA_OUT ?? 'storage/qa/pages-cms';
const EMAIL = process.env.PAGES_QA_EMAIL;
const PASSWORD = process.env.PAGES_QA_PASSWORD;
const DRAFT = `A draft tagline, seen only in the preview (${Date.now().toString(36)}).`;

if (!EMAIL || !PASSWORD) {
    console.error('Set PAGES_QA_EMAIL and PAGES_QA_PASSWORD (an editor account).');
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

function watch(page, label) {
    page.on('console', (message) => {
        if (message.type() === 'error') step(`${label}: no console errors`, false, message.text().slice(0, 200));
    });
    page.on('pageerror', (error) => {
        if (error.message.includes('Transition was aborted')) return;
        step(`${label}: no page errors`, false, error.message.slice(0, 200));
    });
    page.on('response', (response) => {
        const url = response.url();
        if (url.startsWith(BASE) && response.status() >= 400 && !url.includes('favicon')) {
            step(`${label}: no failed requests`, false, `${response.status()} ${url}`);
        }
    });
}

async function noHorizontalScroll(page, label) {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    step(`${label}: no horizontal scroll`, overflow <= 1, overflow > 1 ? `${overflow}px wider than the viewport` : '');
}

const browser = await chromium.launch({ channel: process.env.PAGES_QA_CHANNEL ?? 'chrome', headless: true });

try {
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 }, colorScheme: 'light', reducedMotion: 'reduce' });
    const page = await context.newPage();
    watch(page, 'editor');

    await page.goto(`${BASE}/login`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([page.waitForURL((url) => !url.pathname.startsWith('/login')), page.click('form:has(input[name="password"]) button[type="submit"]')]);
    step('sign in', new URL(page.url()).pathname.startsWith('/admin'), page.url());

    await page.goto(`${BASE}/admin/content/pages`);
    const rows = await page.locator('[data-page-row]').count();
    step('the Pages list shows every page', rows === 15, `${rows} rows`);
    await page.screenshot({ path: join(OUT, 'pages-list-1440-light.png'), fullPage: true });

    await page.goto(`${BASE}/admin/content/pages/home/edit`);
    await page.waitForLoadState('networkidle');
    const preview = page.locator('[data-page-preview]');
    step('the preview pane is open beside the form', await preview.isVisible());
    const frame = page.frameLocator('[data-page-preview] iframe');
    await frame.locator('#hero-title').waitFor({ timeout: 15000 });
    step('the preview shows the real home page', (await frame.locator('#hero-title').innerText()).includes('Mohamed Osman Khidir'));
    await page.screenshot({ path: join(OUT, 'home-editor-1440-light.png') });

    const tagline = page.getByLabel('Hero tagline', { exact: true });
    await tagline.fill(DRAFT);
    await tagline.blur();
    await page.locator('[data-save-note]', { hasText: 'Saved' }).waitFor({ timeout: 15000 });
    await frame.locator('.hero', { hasText: DRAFT }).waitFor({ timeout: 15000 }).catch(() => {});
    step('after the autosave the preview shows the draft', (await frame.locator('.hero').innerText()).includes(DRAFT));
    await page.locator('[data-page-preview] iframe').evaluate((node) => node.contentWindow.scrollTo(0, 0));
    await page.screenshot({ path: join(OUT, 'home-editor-preview-draft-1440-light.png') });

    const visitor = await browser.newPage({ ignoreHTTPSErrors: true });
    await visitor.goto(`${BASE}/`);
    step('visitors still see the published tagline', !(await visitor.content()).includes(DRAFT));
    await visitor.close();

    // Both themes and the narrower widths, with the draft still in place.
    const state = await context.storageState();
    for (const [width, height, theme] of [[1440, 900, 'dark'], [1024, 1366, 'light'], [390, 844, 'light']]) {
        const shots = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height }, colorScheme: theme, storageState: state, reducedMotion: 'reduce' });
        const shot = await shots.newPage();
        watch(shot, `${width}/${theme}`);
        await shot.goto(`${BASE}/admin/content/pages`);
        await noHorizontalScroll(shot, `pages list ${width}/${theme}`);
        await shot.screenshot({ path: join(OUT, `pages-list-${width}-${theme}.png`), fullPage: width !== 1440 });
        await shot.goto(`${BASE}/admin/content/pages/home/edit`);
        await shot.waitForLoadState('networkidle');
        await noHorizontalScroll(shot, `home editor ${width}/${theme}`);
        await shot.screenshot({ path: join(OUT, `home-editor-${width}-${theme}.png`) });
        await shots.close();
    }

    await page.getByRole('button', { name: 'Discard the draft' }).click();
    await page.locator('[data-toast]', { hasText: 'discarded' }).waitFor({ timeout: 10000 }).catch(() => {});
    step('the draft is discarded again', (await tagline.inputValue()) !== DRAFT);
} catch (error) {
    step('walkthrough completed', false, error.message.split('\n')[0]);
} finally {
    await browser.close();
}

writeFileSync(join(OUT, 'report.json'), JSON.stringify({ base: BASE, failures, steps }, null, 2));
console.log(`\n${failures === 0 ? 'All checks passed' : `${failures} check(s) failed`}. Screenshots in ${OUT}/.`);
process.exit(failures === 0 ? 0 : 1);
