/*
 | Smoke test: every public page at 360px, 768px and 1440px through Playwright.
 | Each page is screenshotted (full height) into storage/qa/ and fails on:
 |   - console errors or uncaught page errors,
 |   - any same-origin request that fails or answers 4xx/5xx while loading,
 |   - a broken link (every same-origin href on the page is requested once),
 |   - horizontal scroll (the document is wider than the viewport).
 |
 |   npm run qa:smoke
 |
 | Environment: SMOKE_BASE_URL (default https://profmokhidir.test), SMOKE_OUT
 | (default storage/qa), SMOKE_PAGES=/,/about to limit the pages, SMOKE_WIDTHS=360,1440,
 | SMOKE_EXTERNAL=1 to also fail on broken external links (placeholder seed
 | content links to example.org, so they are warnings by default), SMOKE_CHANNEL=chromium
 | to use Playwright's bundled Chromium instead of Google Chrome (the default, as in tests/a11y). Exit code 1 when anything fails.
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = (process.env.SMOKE_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const OUT = process.env.SMOKE_OUT ?? 'storage/qa';
const WIDTHS = (process.env.SMOKE_WIDTHS ?? '360,768,1440').split(',').map(Number);
const HEIGHTS = { 360: 740, 768: 1024, 1440: 900 };
const EXTERNAL_FATAL = process.env.SMOKE_EXTERNAL === '1';
const ORIGIN = new URL(BASE).origin;

const PUBLIC_PAGES = [
    '/', '/about', '/cv', '/publications', '/awards', '/videos', '/pictures', '/teaching',
    '/commentary', '/community', '/other', '/tributes', '/tributes/new', '/tributes/thank-you',
    '/login', '/forgot-password',
];

const results = [];
const linkCache = new Map();
let failures = 0;

function report(page, width, check, ok, details = []) {
    results.push({ page, width, check, ok, details });
    if (!ok) failures += 1;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${String(width).padStart(4)} ${page.padEnd(44)} ${check}${details.length ? ' (' + details.length + ')' : ''}`);
    for (const line of details.slice(0, 12)) console.log('       - ' + line);
}

function warn(page, width, check, details) {
    results.push({ page, width, check, ok: true, warning: true, details });
    console.log(`warn ${String(width).padStart(4)} ${page.padEnd(44)} ${check} (${details.length})`);
    for (const line of details.slice(0, 12)) console.log('       - ' + line);
}

async function sitemapUrls() {
    try {
        const response = await fetch(`${BASE}/sitemap.xml`);
        if (!response.ok) return [];
        const xml = await response.text();
        return [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)]
            .map((m) => new URL(m[1]).pathname)
            .filter((path) => !PUBLIC_PAGES.includes(path));
    } catch {
        return [];
    }
}

/* Scroll through the page so every scroll-linked reveal has run, then wait for the transitions to end. */
async function settle(page) {
    await page.evaluate(async () => {
        const step = Math.max(200, window.innerHeight * 0.8);
        for (let y = 0; y <= document.documentElement.scrollHeight; y += step) {
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 40));
        }
        window.scrollTo(0, 0);
    });
    await page.waitForFunction(() => document.getAnimations().every((a) => a.playState === 'finished' || a.playState === 'idle'), null, { timeout: 8000 }).catch(() => {});
    await page.waitForTimeout(250);
}

async function probe(url) {
    if (linkCache.has(url)) return linkCache.get(url);
    const attempt = async (method) => {
        try {
            const response = await fetch(url, { method, redirect: 'follow', signal: AbortSignal.timeout(15000), headers: { 'user-agent': 'profmokhidir-smoke' } });
            return response.status;
        } catch (error) {
            return `error: ${error.cause?.code ?? error.name}`;
        }
    };
    let status = await attempt('HEAD');
    if (status === 405 || status === 403 || typeof status === 'string') status = await attempt('GET');
    linkCache.set(url, status);
    return status;
}

async function checkLinks(page, path, width) {
    const hrefs = await page.$$eval('a[href]', (els) => els.map((el) => el.getAttribute('href')));
    const internal = new Set();
    const external = new Set();
    for (const href of hrefs) {
        if (!href || href.startsWith('#') || /^(mailto|tel|javascript):/i.test(href)) continue;
        let url;
        try { url = new URL(href, BASE); } catch { internal.add(`invalid href ${href}`); continue; }
        url.hash = '';
        (url.origin === ORIGIN ? internal : external).add(url.toString());
    }
    const broken = [];
    for (const url of internal) {
        if (url.startsWith('invalid')) { broken.push(url); continue; }
        const status = await probe(url);
        if (typeof status === 'string' || status >= 400) broken.push(`${status} ${url}`);
    }
    report(path, width, `links (${internal.size} internal)`, broken.length === 0, broken);
    const externalBroken = [];
    for (const url of external) {
        const status = await probe(url);
        if (typeof status === 'string' || status >= 400) externalBroken.push(`${status} ${url}`);
    }
    if (externalBroken.length) {
        if (EXTERNAL_FATAL) report(path, width, `external links (${external.size})`, false, externalBroken);
        else warn(path, width, `external links (${external.size}, not fatal: placeholder content)`, externalBroken);
    }
}

function screenshotName(path, width) {
    const slug = path === '/' ? 'home' : path.replace(/^\//, '').replace(/[^a-z0-9]+/gi, '-').replace(/-+$/, '');
    return `${slug}-${width}.png`;
}

async function smoke(browser, path, width) {
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height: HEIGHTS[width] ?? 900 }, deviceScaleFactor: 1 });
    const page = await context.newPage();
    const consoleErrors = [];
    const requestProblems = [];
    page.on('console', (message) => { if (message.type() === 'error') consoleErrors.push(message.text().slice(0, 200)); });
    page.on('pageerror', (error) => consoleErrors.push(`uncaught: ${error.message.slice(0, 200)}`));
    page.on('requestfailed', (request) => {
        const text = request.failure()?.errorText ?? 'failed';
        if (text.includes('ERR_ABORTED')) return;
        if (new URL(request.url()).origin === ORIGIN) requestProblems.push(`${text} ${request.url()}`);
    });
    page.on('response', (response) => {
        if (response.status() >= 400 && new URL(response.url()).origin === ORIGIN) requestProblems.push(`${response.status()} ${response.url()}`);
    });

    let status = 0;
    try {
        const response = await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle', timeout: 45000 });
        status = response?.status() ?? 0;
    } catch (error) {
        report(path, width, 'load', false, [error.message.slice(0, 200)]);
        await context.close();
        return;
    }
    report(path, width, `status ${status}`, status === 200);
    if (status !== 200) { await context.close(); return; }

    await settle(page);

    const overflow = await page.evaluate(() => {
        const width = window.innerWidth;
        const wide = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth);
        const offenders = [];
        if (wide > width + 1) {
            for (const el of document.querySelectorAll('body *')) {
                const rect = el.getBoundingClientRect();
                if (rect.right > width + 1 && rect.width > 0 && getComputedStyle(el).position !== 'fixed') {
                    offenders.push(`${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}.${[...el.classList].slice(0, 3).join('.')} right=${Math.round(rect.right)}`);
                    if (offenders.length >= 6) break;
                }
            }
        }
        return { wide, width, offenders };
    });
    report(path, width, 'no horizontal scroll', overflow.wide <= overflow.width + 1, overflow.wide > overflow.width + 1 ? [`document ${overflow.wide}px wide in a ${overflow.width}px viewport`, ...overflow.offenders] : []);

    // The responses collected during load; the page may lazy-load more while scrolling, so re-check after settling.
    report(path, width, 'console clean', consoleErrors.length === 0, consoleErrors);
    report(path, width, 'requests ok', requestProblems.length === 0, requestProblems);

    await checkLinks(page, path, width);

    const file = join(OUT, screenshotName(path, width));
    await page.screenshot({ path: file, fullPage: true });
    results.push({ page: path, width, check: 'screenshot', ok: true, details: [file] });

    await context.close();
}

mkdirSync(OUT, { recursive: true });
const browser = await chromium.launch({ channel: process.env.SMOKE_CHANNEL === 'chromium' ? undefined : (process.env.SMOKE_CHANNEL ?? 'chrome'), headless: true });

try {
    const pages = process.env.SMOKE_PAGES ? process.env.SMOKE_PAGES.split(',') : [...PUBLIC_PAGES, ...(await sitemapUrls())];
    console.log(`${pages.length} pages × ${WIDTHS.length} widths against ${BASE}`);
    for (const path of pages) {
        for (const width of WIDTHS) await smoke(browser, path, width);
    }
} finally {
    await browser.close();
}

const reportFile = join(OUT, 'smoke-report.json');
writeFileSync(reportFile, JSON.stringify({ base: BASE, generated: new Date().toISOString(), failures, results }, null, 2));
console.log(`\n${results.length} checks, ${failures} failing. Screenshots and report in ${OUT}/`);
process.exit(failures === 0 ? 0 : 1);
