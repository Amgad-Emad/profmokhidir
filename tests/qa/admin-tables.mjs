/*
 | Admin data table checks, through Playwright and Google Chrome against
 | https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:admin-tables
 |   TABLES_LABEL=before npm run qa:admin-tables   (screenshots only, for a comparison)
 |
 | tests/qa/admin-tables-setup.php prepares an English and an Arabic administrator and removes
 | them afterwards. Screenshots go to storage/qa/admin-tables/<label>-<screen>-<locale>-<theme>.png
 | (the teaching materials and publications tables at 1440px, as the first screen shows them).
 |
 | With the default label "after", on the teaching, publications, tributes and users tables, in
 | English and Arabic, light and dark, at 390, 1024, 1280 and 1440px:
 |
 |   axe        no WCAG 2.x A/AA violation, also with the Columns menu open.
 |   layout     no horizontal page scroll; from 1280 the table does not scroll sideways at
 |              comfortable density (at 1024 it scrolls inside its frame); rows are 56px (44px compact) and text cells align to the start
 |              edge, dates and status to the end; at 390 every row is a card with its menu shown.
 |   direction  in Arabic the primary column is the rightmost and the row actions sit at the left.
 |   keyboard   the title link opens the edit screen; Tab reaches the row actions, which show on
 |              focus; a sort header toggles aria-sort and lands in the address.
 |
 | report-<label>.json goes to the same folder. Exit code 1 on a failure.
 */
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.TABLES_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/admin-tables';
const LABEL = process.env.TABLES_LABEL ?? 'after';
const CHECKING = LABEL === 'after';
const THEMES = ['light', 'dark'];
const LOCALES = ['en', 'ar'];
const WIDTHS = CHECKING ? [390, 1024, 1280, 1440] : [1440];
const HEIGHTS = { 390: 844, 1024: 768, 1280: 800, 1440: 900 };
const SCREENS = {
    teaching: '/admin/content/teaching',
    publications: '/admin/content/publications',
    tributes: '/admin/tributes',
    users: '/admin/users',
};
const SHOTS = ['teaching', 'publications'];
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${ok || !detail ? '' : ' — ' + detail}`);
}

function tinker(env = {}) {
    const output = execFileSync('php', ['artisan', 'tinker', `--execute=require 'tests/qa/admin-tables-setup.php';`], { env: { ...process.env, ...env }, encoding: 'utf8' });
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
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height: HEIGHTS[width] }, deviceScaleFactor: 2, colorScheme: theme, reducedMotion: 'reduce', storageState, hasTouch: width === 390, isMobile: width === 390 });
    await ctx.addCookies([{ name: 'theme', value: theme, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
}

async function open(page, path) {
    await page.goto(BASE + path);
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(250);
}

async function idle(page) {
    await page.waitForTimeout(150);
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(200);
}

async function axe(page, name) {
    const scan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
    check(`${name}: axe`, scan.violations.length === 0, scan.violations.map((v) => `${v.id} (${v.nodes.length}): ${v.nodes[0]?.target.join(' ')}`).join('; '));
}

/* The table's own layout checks at one width. */
async function layout(page, name, width, locale) {
    const box = await page.evaluate(() => {
        const table = document.querySelector('[data-data-table]');
        if (!table) return null;
        const scroll = table.querySelector('[data-table-scroll]');
        const row = table.querySelector('[data-row]');
        const cell = (selector) => row?.querySelector(selector);
        const rect = (el) => (el ? el.getBoundingClientRect().toJSON() : null);
        const align = (el) => (el ? getComputedStyle(el).textAlign : null);
        return {
            pageFits: document.documentElement.scrollWidth <= window.innerWidth,
            tableFits: scroll ? scroll.scrollWidth <= scroll.clientWidth + 1 : false,
            layout: table.dataset.layout ?? '',
            rowHeight: row ? row.getBoundingClientRect().height : 0,
            density: table.closest('[data-density]')?.dataset.density,
            primary: rect(cell('[data-cell-type="primary"]')),
            actions: rect(cell('[data-cell="actions"]')),
            textAlign: align(cell('[data-cell-type="text"]')),
            endAlign: align(cell('[data-cell-type="relative"], [data-cell-type="date"], [data-cell-type="number"]')),
            statusAlign: align(cell('[data-cell-type="status"]')),
            menuVisible: (() => {
                const button = row?.querySelector('[data-row-menu] button');
                if (!button) return null;
                const style = getComputedStyle(button.closest('[data-row-menu]'));
                return style.visibility !== 'hidden' && Number(style.opacity) > 0.5 && button.getBoundingClientRect().width > 0;
            })(),
            rows: table.querySelectorAll('[data-row]').length,
            skeletonShown: [...table.querySelectorAll('.data-table__skeleton')].some((el) => el.offsetParent !== null),
        };
    });
    check(`${name}: data table present`, box !== null && box.rows > 0, JSON.stringify(box));
    if (!box || box.rows === 0) return;
    check(`${name}: no horizontal page scroll`, box.pageFits);
    check(`${name}: skeleton rows hidden once loaded`, !box.skeletonShown);

    if (width >= 1024) {
        // The brief: every table fits a 1280px screen at comfortable density; narrower, it scrolls inside its frame.
        if (width >= 1280) check(`${name}: table fits without sideways scroll`, box.tableFits);
        const expected = box.density === 'compact' ? 44 : 56;
        check(`${name}: rows are ${expected}px`, Math.abs(box.rowHeight - expected) <= 2, `${box.rowHeight}px`);
        const start = locale === 'ar' ? 'right' : 'left';
        const end = locale === 'ar' ? 'left' : 'right';
        if (box.textAlign) check(`${name}: text cells align to the start edge`, ['start', start].includes(box.textAlign), box.textAlign);
        if (box.endAlign) check(`${name}: dates and numbers align to the end edge`, ['end', end].includes(box.endAlign), box.endAlign);
        if (box.statusAlign) check(`${name}: status aligns to the end edge`, ['end', end].includes(box.statusAlign), box.statusAlign);
        if (box.primary && box.actions) {
            const ordered = locale === 'ar' ? box.primary.left > box.actions.left : box.primary.left < box.actions.left;
            check(`${name}: primary column at the start, actions at the end`, ordered);
        }
    }
    if (width === 390) {
        check(`${name}: rows become cards`, box.layout === 'cards' || box.rowHeight > 60, box.layout);
        if (box.menuVisible !== null) check(`${name}: the … menu is visible on touch`, box.menuVisible);
    }
}

async function keyboard(page, name) {
    const link = page.locator('[data-row] [data-row-link]').first();
    if ((await link.count()) > 0) {
        const href = await link.getAttribute('href');
        check(`${name}: title is a real link`, Boolean(href) && href.includes('/admin/'), href);
        await link.focus();
        await page.keyboard.press('Tab');
        await page.waitForTimeout(250);
        const shown = await page.evaluate(() => {
            const el = document.activeElement;
            const actions = el?.closest('[data-row-actions], [data-row-menu], [data-cell]');
            if (!actions) return { ok: false, tag: el?.tagName };
            return { ok: Number(getComputedStyle(el.closest('[data-row-actions]') ?? el).opacity) > 0.5, tag: el.tagName };
        });
        check(`${name}: focus reaches a visible row control`, shown.ok, JSON.stringify(shown));
    }

    const sort = page.locator('th [data-sort]').first();
    if ((await sort.count()) > 0 && (await sort.isVisible())) {
        await sort.click();
        await idle(page);
        const state = await page.evaluate(() => document.querySelector('th[aria-sort]')?.getAttribute('aria-sort'));
        check(`${name}: header sort sets aria-sort and the address`, Boolean(state) && page.url().includes('sort='), `${state} ${page.url()}`);
    }

    const columns = page.locator('[data-columns-button]').first();
    if ((await columns.count()) > 0 && (await columns.isVisible())) {
        await columns.click();
        await page.waitForTimeout(150);
        check(`${name}: Columns menu opens`, await page.locator('[data-columns-menu]').isVisible());
        await axe(page, `${name} (columns menu)`);
        await page.keyboard.press('Escape');
    }
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
                    await open(page, `${path}?q=`);
                    if (width === 1440 && SHOTS.includes(screen)) {
                        await page.screenshot({ path: `${OUT}/${LABEL}-${screen}-${locale}-${theme}.png` });
                    }
                    if (!CHECKING) continue;
                    if (width === 390 && SHOTS.includes(screen)) {
                        await page.screenshot({ path: `${OUT}/${LABEL}-${screen}-${locale}-${theme}-390.png`, fullPage: true });
                    }
                    await axe(page, name);
                    await layout(page, name, width, locale);
                    if (width !== 390) await keyboard(page, name);
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
