/*
 | Admin list toolbar checks, through Playwright and Google Chrome against
 | https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:admin-filters
 |   FILTERS_LABEL=before npm run qa:admin-filters   (screenshots only, for a comparison)
 |
 | tests/qa/admin-filters-setup.php prepares an English and an Arabic administrator and removes
 | them afterwards. Screenshots go to storage/qa/admin-filters/<label>-<screen>-<locale>-<theme>.png
 | (the publications and tributes lists at 1440px, as the first screen shows them).
 |
 | With the default label "after", on the publications, tributes and users lists, in English and
 | Arabic, light and dark, at 390, 768 and 1440px:
 |
 |   axe        no WCAG 2.x A/AA violation on the screen, with a filter popover or sheet open too.
 |   layout     no horizontal scroll; at 1440 the band (views, toolbar, chips) is at most 140px tall
 |              and every toolbar control is 40px; at 768 the filter buttons fold into "Filters";
 |              at 390 the views scroll sideways and the search field is full width.
 |   direction  in Arabic the search field sits at the right edge and the New button at the left.
 |   keyboard   "/" focuses the search field, Escape clears it; a filter popover opens from its
 |              button, arrow keys move through the options, Space ticks one, Escape closes it and
 |              focus returns to the button.
 |   url        a ticked filter lands in the address; reloading keeps it; leaving the list and
 |              coming back with the Back button keeps it too.
 |
 | report.json goes to the same folder. Exit code 1 on a failure.
 */
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.FILTERS_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/admin-filters';
const LABEL = process.env.FILTERS_LABEL ?? 'after';
const CHECKING = LABEL === 'after';
const THEMES = ['light', 'dark'];
const LOCALES = ['en', 'ar'];
const WIDTHS = CHECKING ? [390, 768, 1440] : [1440];
const HEIGHTS = { 390: 844, 768: 1024, 1440: 900 };
const SCREENS = {
    publications: '/admin/content/publications',
    tributes: '/admin/tributes',
    users: '/admin/users',
};
const SHOTS = ['publications', 'tributes'];
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${ok || !detail ? '' : ' — ' + detail}`);
}

function tinker(env = {}) {
    const output = execFileSync('php', ['artisan', 'tinker', `--execute=require 'tests/qa/admin-filters-setup.php';`], { env: { ...process.env, ...env }, encoding: 'utf8' });
    return JSON.parse(output.trim().split('\n').pop());
}

async function signIn(browser, email, password) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await ctx.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await Promise.all([page.waitForNavigation(), page.click('form:has(input[name="password"]) button[type="submit"]')]);
    const ok = !page.url().includes('/login');
    const state = await ctx.storageState();
    await ctx.close();
    return ok ? state : null;
}

async function context(browser, theme, width, storageState) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height: HEIGHTS[width] }, deviceScaleFactor: 2, colorScheme: theme, reducedMotion: 'reduce', storageState });
    await ctx.addCookies([{ name: 'theme', value: theme, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
}

async function open(page, path) {
    await page.goto(BASE + path);
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(250);
}

/* Livewire round trips: wait for the network to go quiet after an action. */
async function idle(page) {
    await page.waitForTimeout(150);
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(150);
}

async function axe(page, name) {
    const scan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
    check(`${name}: axe`, scan.violations.length === 0, scan.violations.map((v) => `${v.id} (${v.nodes.length}): ${v.nodes[0]?.target.join(' ')}`).join('; '));
}

async function noScroll(page) {
    return page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);
}

/* The toolbar's own checks at one width; `name` prefixes every result. */
async function layout(page, name, width, locale) {
    const box = await page.evaluate(() => {
        const band = document.querySelector('[data-list-toolbar]');
        if (!band) return null;
        const rect = (el) => (el && el.offsetParent !== null ? el.getBoundingClientRect().toJSON() : null);
        const visible = (selector) => [...band.querySelectorAll(selector)].filter((el) => el.offsetParent !== null);
        const tabs = band.querySelector('[data-list-views]');
        return {
            band: rect(band),
            search: rect(band.querySelector('[data-list-search]')),
            create: rect(band.querySelector('[data-create]')),
            controls: visible('[data-toolbar-control]').map((el) => ({ name: el.dataset.toolbarControl, height: el.getBoundingClientRect().height })),
            filterButtons: visible('[data-filter-button]').length,
            sheetButton: visible('[data-filters-sheet-button]').length,
            tabsScroll: tabs ? { overflow: getComputedStyle(tabs).overflowX, scrollWidth: tabs.scrollWidth, clientWidth: tabs.clientWidth } : null,
            main: rect(document.querySelector('main')),
        };
    });
    check(`${name}: toolbar present`, box !== null);
    if (!box) return;
    check(`${name}: no horizontal scroll`, await noScroll(page));

    if (width === 1440) {
        check(`${name}: band at most 140px`, box.band.height <= 140, `${Math.round(box.band.height)}px`);
        const off = box.controls.filter((c) => Math.abs(c.height - 40) > 0.5);
        check(`${name}: toolbar controls are 40px`, off.length === 0, off.map((c) => `${c.name} ${c.height}`).join(', '));
        check(`${name}: filter buttons shown`, box.filterButtons > 0 && box.sheetButton === 0);
        if (box.search && box.create) {
            const searchFirst = locale === 'ar' ? box.search.right > box.create.right : box.search.left < box.create.left;
            check(`${name}: search at the start edge, New at the end`, searchFirst, JSON.stringify({ search: box.search.left, create: box.create.left }));
        }
    }
    if (width === 768) {
        check(`${name}: filters fold into one Filters button`, box.filterButtons === 0 && box.sheetButton === 1);
    }
    if (width === 390) {
        check(`${name}: views scroll sideways`, box.tabsScroll && ['auto', 'scroll'].includes(box.tabsScroll.overflow), JSON.stringify(box.tabsScroll));
        check(`${name}: search is full width`, box.search && box.band && box.search.width >= box.band.width - 1, `${box.search?.width} of ${box.band?.width}`);
    }
}

async function keyboard(page, name) {
    await page.evaluate(() => document.activeElement?.blur());
    await page.keyboard.press('/');
    const focused = await page.evaluate(() => document.activeElement?.matches('[data-shortcut="search"]') ?? false);
    check(`${name}: "/" focuses the search field`, focused);
    await page.keyboard.type('zz');
    await idle(page);
    await page.keyboard.press('Escape');
    await idle(page);
    const cleared = await page.evaluate(() => document.querySelector('[data-shortcut="search"]')?.value === '');
    check(`${name}: Escape clears the search`, cleared);

    const button = page.locator('[data-filter-button]').first();
    if ((await button.count()) === 0 || !(await button.isVisible())) return;
    await button.focus();
    await page.keyboard.press('Enter');
    await page.waitForTimeout(150);
    const panel = await page.evaluate(() => {
        const el = document.activeElement;
        return { inPanel: Boolean(el?.closest('[data-filter-panel]')), expanded: document.querySelector('[data-filter-button][aria-expanded="true"]') !== null };
    });
    check(`${name}: popover opens and takes focus`, panel.inPanel && panel.expanded, JSON.stringify(panel));
    const first = await page.evaluate(() => document.activeElement?.id);
    await page.keyboard.press('ArrowDown');
    const second = await page.evaluate(() => document.activeElement?.id);
    check(`${name}: arrow keys move through the options`, first !== second, `${first} → ${second}`);
    const before = page.url();
    await page.keyboard.press('Space');
    await idle(page);
    const url = page.url();
    check(`${name}: Space applies an option and the address follows`, url !== before && url.includes('filters'), url);
    await page.keyboard.press('Escape');
    await page.waitForTimeout(150);
    const back = await page.evaluate(() => document.activeElement?.matches('[data-filter-button]') && document.querySelector('[data-filter-button][aria-expanded="true"]') === null);
    check(`${name}: Escape closes the popover and focus returns to its button`, back);

    await axe(page, `${name} (filtered)`);
    await page.reload();
    await idle(page);
    check(`${name}: reload keeps the filter`, page.url().includes('filters') && (await page.locator('[data-filter-chip]').count()) > 0);
    const filtered = page.url();
    await page.goto(`${BASE}/admin`);
    await page.goBack();
    await idle(page);
    check(`${name}: Back to the list keeps the filter`, page.url() === filtered && (await page.locator('[data-filter-chip]').count()) > 0, page.url());
}

async function sheet(page, name) {
    const button = page.locator('[data-filters-sheet-button]').first();
    if ((await button.count()) === 0 || !(await button.isVisible())) return;
    await button.click();
    await page.waitForTimeout(250);
    const open = await page.locator('[data-filters-sheet]').isVisible();
    check(`${name}: Filters opens the sheet`, open);
    if (!open) return;
    await axe(page, `${name} (sheet)`);
    await page.locator('[data-filters-sheet] [data-sheet-option]:not([value=""])').first().check();
    await page.locator('[data-filters-sheet] [data-sheet-apply]').click();
    await idle(page);
    check(`${name}: Apply in the sheet filters the list`, page.url().includes('filters') && (await page.locator('[data-filter-chip]').count()) > 0, page.url());
}

const browser = await chromium.launch({ channel: 'chrome' });
const accounts = tinker();
const states = {};
for (const locale of LOCALES) {
    states[locale] = await signIn(browser, accounts[locale], accounts.password);
    check(`${locale} admin signs in`, states[locale] !== null);
}

try {
    for (const locale of LOCALES) {
        if (!states[locale]) continue;
        for (const theme of THEMES) {
            for (const width of WIDTHS) {
                const ctx = await context(browser, theme, width, states[locale]);
                const page = await ctx.newPage();
                for (const [screen, path] of Object.entries(SCREENS)) {
                    if (!CHECKING && !SHOTS.includes(screen)) continue;
                    const name = `${screen} ${locale} ${theme} ${width}`;
                    // A query string, so the screen opens on its default view rather than the last one used.
                    await open(page, `${path}?q=`);
                    if (width === 1440 && SHOTS.includes(screen)) {
                        await page.screenshot({ path: `${OUT}/${LABEL}-${screen}-${locale}-${theme}.png` });
                    }
                    if (!CHECKING) continue;
                    await axe(page, name);
                    await layout(page, name, width, locale);
                    if (width === 390 || width === 768) await sheet(page, name);
                    else await keyboard(page, name);
                }
                await ctx.close();
            }
        }
    }
} finally {
    await browser.close();
    tinker({ QA_TEARDOWN: '1' });
}

writeFileSync(`${OUT}/report-${LABEL}.json`, JSON.stringify({ label: LABEL, failures, results }, null, 2));
console.log(`\n${results.length - failures} passed, ${failures} failed`);
process.exit(CHECKING && failures ? 1 : 0);
