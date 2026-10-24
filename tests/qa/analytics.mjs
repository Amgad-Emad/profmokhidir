/*
 | Browser checks for the first-party analytics, through Playwright and Google Chrome
 | against https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:analytics
 |
 | tests/qa/analytics-setup.php prepares an editor, the demo figures and switches analytics on;
 | afterwards it removes the editor and the live check's beacons and switches analytics off.
 |
 |   live       as a signed-out visitor with an ordinary browser, open /about, scroll to the
 |              foot, wait, then leave: one page view is stored with engaged seconds and a
 |              scroll mark, and no cookie was set by the beacon. With Do Not Track, nothing.
 |   absent     the analytics meta tag is not on /login, /admin, /admin/analytics or
 |              /community/chat (redirect), and not on public pages for signed-in staff.
 |   report     /admin/analytics and the dashboard's Audience section in both themes at 1440
 |              and 390px: the chart canvas is drawn, every figure uses tabular numerals, no
 |              horizontal scroll, no console errors. Screenshots in storage/qa/analytics/.
 |
 | Exit code 1 on a failure.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.ANALYTICS_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/analytics';
const THEMES = ['light', 'dark'];
const DESKTOP_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

function setup(env = {}) {
    const out = execFileSync('php', ['artisan', 'tinker', "--execute=require 'tests/qa/analytics-setup.php';"], { encoding: 'utf8', env: { ...process.env, ...env } });
    return JSON.parse(out.trim().split('\n').filter((l) => l.startsWith('{')).pop());
}

const qa = setup();
const browser = await chromium.launch({ channel: 'chrome' });
const errors = [];

async function context(theme, { width = 1440, height = 900, state, userAgent, headers } = {}) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height }, colorScheme: theme, storageState: state, userAgent, extraHTTPHeaders: headers });
    await ctx.addCookies([{ name: 'theme', value: theme, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
}

async function open(ctx, path) {
    const p = await ctx.newPage();
    p.on('pageerror', (e) => errors.push(`${path}: ${e}`));
    p.on('console', (m) => m.type() === 'error' && errors.push(`${path}: ${m.text()}`));
    await p.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    return p;
}

try {
    // ---- live: a real-looking visitor is counted with engaged time and scroll depth.
    {
        const ctx = await context('light', { userAgent: DESKTOP_UA });
        // Playwright marks the browser as automated; an ordinary visitor's browser is not.
        await ctx.addInitScript(() => Object.defineProperty(Navigator.prototype, 'webdriver', { get: () => false }));
        const beacons = [];
        ctx.on('request', (r) => r.url().endsWith('/a/collect') && beacons.push({ type: r.headers()['content-type'] }));
        const responses = [];
        ctx.on('response', (r) => r.url().endsWith('/a/collect') && responses.push(r));
        const p = await open(ctx, '/about');
        check('live: meta tag on /about', (await p.locator('meta[name="analytics"]').count()) === 1);
        for (let i = 0; i < 6; i++) {
            await p.mouse.wheel(0, 1600);
            await p.waitForTimeout(900);
        }
        await p.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
        await p.waitForTimeout(1500);
        await p.goto(`${BASE}/publications`, { waitUntil: 'networkidle' });
        await p.waitForTimeout(800);
        check('live: page view and engagement beacons sent', beacons.length >= 3, `${beacons.length} beacons`);
        check('live: beacons are text/plain', beacons.every((b) => String(b.type).startsWith('text/plain')), beacons.map((b) => b.type).join(','));
        check('live: endpoint answers 204', responses.length > 0 && responses.every((r) => r.status() === 204), responses.map((r) => r.status()).join(','));
        const cookieNames = (await ctx.cookies()).map((c) => c.name);
        check('live: no analytics cookie', !cookieNames.some((n) => /analytic|visitor|_ga|track/i.test(n)), cookieNames.join(','));
        const after = setup({ QA_COUNT: '1', QA_SINCE: qa.now });
        const about = after.rows.find((row) => row.path === '/about');
        check('live: page views stored', after.rows.length >= 2 && after.events >= qa.events + 2, after.rows.map((row) => row.path).join(','));
        check('live: engaged seconds and scroll mark stored', about && about.engaged_seconds >= 3 && about.scroll_depth === 100, JSON.stringify(about));
        check('live: no IP or user agent stored', !JSON.stringify(after.rows).includes('Macintosh') && !/\d+\.\d+\.\d+\.\d+/.test(JSON.stringify(after.rows)));
        await ctx.close();
    }

    // ---- Do Not Track: nothing is sent, the meta tag is not printed.
    {
        const ctx = await context('light', { userAgent: DESKTOP_UA, headers: { DNT: '1' } });
        const sent = [];
        ctx.on('request', (r) => r.url().endsWith('/a/collect') && sent.push(r.url()));
        const p = await open(ctx, '/about');
        await p.waitForTimeout(500);
        check('dnt: no meta tag and no beacon', (await p.locator('meta[name="analytics"]').count()) === 0 && sent.length === 0);
        await ctx.close();
    }

    // ---- Sign in as the editor once.
    const signInCtx = await context('light');
    const login = await open(signInCtx, '/login');
    check('absent: not on /login', (await login.locator('meta[name="analytics"]').count()) === 0);
    await login.fill('input[name=email]', qa.email);
    await login.fill('input[name=password]', qa.password);
    await Promise.all([login.waitForNavigation(), login.click('form:has(input[name=password]) button[type=submit]')]);
    const state = await signInCtx.storageState();
    await signInCtx.close();

    {
        const ctx = await context('light', { state });
        for (const path of ['/admin', '/admin/analytics', '/about', '/community/chat']) {
            const p = await open(ctx, path);
            check(`absent: not on ${path} for staff`, (await p.locator('meta[name="analytics"]').count()) === 0, p.url());
            await p.close();
        }
        await ctx.close();
    }

    // ---- report: screenshots and checks in both themes.
    for (const theme of THEMES) {
        for (const width of [1440, 390]) {
            const ctx = await context(theme, { width, height: 900, state });
            const report = await open(ctx, '/admin/analytics');
            await report.waitForTimeout(1500);
            const drawn = await report.locator('[data-visitors-chart] canvas').evaluate((c) => {
                const data = c.getContext('2d').getImageData(0, 0, c.width, c.height).data;
                let painted = 0;
                for (let i = 3; i < data.length; i += 4) if (data[i] > 0) painted++;
                return painted;
            });
            check(`report ${theme} ${width}: chart drawn`, drawn > 500, `${drawn} px`);
            const overflow = await report.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
            check(`report ${theme} ${width}: no horizontal scroll`, overflow <= 0, `${overflow}px`);
            const tabular = await report.locator('.analytics-figure__value, .ranked-list__figure, .analytics-table td').evaluateAll((els) => els.filter((el) => !getComputedStyle(el).fontVariantNumeric.includes('tabular-nums')).length);
            check(`report ${theme} ${width}: tabular figures`, tabular === 0, `${tabular} without`);
            const shaded = await report.locator('[data-world-map] path[data-tier]').count();
            check(`report ${theme} ${width}: map shaded`, shaded > 5, `${shaded} countries`);
            await report.screenshot({ path: `${OUT}/analytics-${theme}-${width}.png`, fullPage: true });

            const dashboard = await open(ctx, '/admin');
            await dashboard.waitForTimeout(400);
            const audience = dashboard.locator('[data-section="audience"]');
            await audience.scrollIntoViewIfNeeded();
            await dashboard.waitForTimeout(1800);
            check(`dashboard ${theme} ${width}: audience figures`, (await audience.locator('[data-figure]').count()) === 4);
            await audience.screenshot({ path: `${OUT}/audience-${theme}-${width}.png` });
            await ctx.close();
        }
    }
} finally {
    await browser.close();
    const down = setup({ QA_TEARDOWN: '1', QA_SINCE: qa.now });
    console.log(`teardown: removed ${down.removed} live-check page views, analytics switched off`);
}

check('no console or page errors', errors.length === 0, errors.slice(0, 5).join(' | '));
writeFileSync(`${OUT}/report.json`, JSON.stringify({ results, errors }, null, 2));
console.log(`\n${results.length - failures}/${results.length} checks passed`);
process.exit(failures > 0 ? 1 : 0);
