/*
 | Readability and section card checks, through Playwright and Google Chrome against
 | https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:readability
 |   READABILITY_LABEL=before npm run qa:readability   (screenshots only, for a comparison)
 |
 | tests/qa/readability-setup.php prepares an admin and an approved member and removes them
 | afterwards. Screenshots go to storage/qa/readability/<label>-<subject>-<width>-<theme>.png:
 | the section cards (home and About), an About paragraph, the chat and an admin form, at
 | 390 and 1440px in the light and the dark theme.
 |
 | With the default label "after", in both themes and at 390, 768 and 1440px:
 |
 |   weights    body text is Hanken Grotesk 400 at 17px (phones) or 18px with 1.6 leading; no
 |              element with Hanken text is set at weight 300; labels are 500 at 0.24em; no
 |              visible text in Hanken is under 15px except the 12px uppercase labels.
 |   cards      the grid is as wide as the brass rule above it, every card in a row has the
 |              same height, four or three cards a row at 1440, two at 768, one at 390; no
 |              label, heading or description overflows its card; each card is one link.
 |   overflow   no horizontal scroll on any page visited.
 |
 | report.json goes to the same folder. Exit code 1 on a failure.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.READABILITY_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/readability';
const LABEL = process.env.READABILITY_LABEL ?? 'after';
const CHECKING = LABEL === 'after';
const THEMES = ['light', 'dark'];
const HEIGHTS = { 390: 844, 768: 1024, 1440: 900 };
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${ok || !detail ? '' : ' — ' + detail}`);
}

function tinker(env = {}) {
    const output = execFileSync('php', ['artisan', 'tinker', `--execute=require 'tests/qa/readability-setup.php';`], { env: { ...process.env, ...env }, encoding: 'utf8' });
    return JSON.parse(output.trim().split('\n').pop());
}

async function settle(page) {
    await page.evaluate(async () => {
        const step = Math.max(200, window.innerHeight * 0.8);
        for (let y = 0; y <= document.documentElement.scrollHeight; y += step) {
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 30));
        }
        window.scrollTo(0, 0);
    });
    await page.waitForFunction(() => document.getAnimations().every((a) => a.playState === 'finished' || a.playState === 'idle'), null, { timeout: 8000 }).catch(() => {});
    await page.waitForTimeout(200);
}

async function context(browser, theme, width, storageState) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height: HEIGHTS[width] }, deviceScaleFactor: 2, colorScheme: theme, reducedMotion: 'reduce', ...(storageState ? { storageState } : {}) });
    await ctx.addCookies([{ name: 'theme', value: theme, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
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

async function shoot(page, selector, name) {
    const target = page.locator(selector).first();
    if ((await target.count()) === 0) {
        check(`${name}: ${selector} present`, false);
        return;
    }
    await target.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
    await target.screenshot({ path: `${OUT}/${LABEL}-${name}.png` });
}

/* Typography over every visible text element: weights, sizes, label tracking. */
async function typography(page, where) {
    const found = await page.evaluate(() => {
        const problems = { light: [], small: [], label: [] };
        const body = getComputedStyle(document.body);
        for (const el of document.querySelectorAll('body *')) {
            if (!el.offsetParent && getComputedStyle(el).position !== 'fixed') continue;
            const own = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim() !== '');
            if (!own || el.closest('[aria-hidden="true"], .sr-only, svg, script, style')) continue;
            const s = getComputedStyle(el);
            if (!s.fontFamily.includes('Hanken')) continue;
            const id = `${el.tagName.toLowerCase()}.${[...el.classList].slice(0, 3).join('.')} "${el.textContent.trim().slice(0, 30)}"`;
            if (Number(s.fontWeight) < 400) problems.light.push(`${id} ${s.fontWeight}`);
            const size = parseFloat(s.fontSize);
            const isLabel = s.textTransform === 'uppercase';
            if (isLabel && el.classList.contains('t-label') && (Number(s.fontWeight) !== 500 || Math.abs(parseFloat(s.letterSpacing) / size - 0.24) > 0.01)) problems.label.push(`${id} ${s.fontWeight} ${s.letterSpacing}`);
            if (size < 14.9 && !isLabel && !el.closest('.admin-count, .admin-kbd, .catalogue__badge, .chat-filemark, .site-search-toggle__key, kbd')) problems.small.push(`${id} ${s.fontSize}`);
        }
        return { problems, body: { weight: body.fontWeight, size: body.fontSize, lineHeight: parseFloat(body.lineHeight) / parseFloat(body.fontSize) } };
    });
    return found;
}

async function cards(page, width) {
    return page.evaluate((w) => {
        const out = [];
        for (const grid of document.querySelectorAll('.section-cards')) {
            const rule = grid.closest('nav, section')?.querySelector('.rule-brass');
            const g = grid.getBoundingClientRect();
            const items = [...grid.querySelectorAll('.section-card')];
            const rects = items.map((c) => c.getBoundingClientRect());
            const rows = new Map();
            rects.forEach((r) => rows.set(Math.round(r.top), [...(rows.get(Math.round(r.top)) ?? []), r.height]));
            const perRow = Math.max(...[...rows.values()].map((r) => r.length));
            const overflow = [];
            for (const card of items) {
                for (const el of card.querySelectorAll('span')) {
                    if (el.scrollWidth > el.clientWidth + 1 && getComputedStyle(el).overflow !== 'visible') overflow.push(el.className);
                    const r = el.getBoundingClientRect();
                    const c = card.getBoundingClientRect();
                    if (r.width && (r.right > c.right + 0.5 || r.left < c.left - 0.5)) overflow.push(`${el.className} outside the card`);
                }
                if (card.querySelectorAll('a').length) overflow.push('nested link');
            }
            out.push({
                ruleWidth: rule ? rule.getBoundingClientRect().width : null,
                ruleLeft: rule ? rule.getBoundingClientRect().left : null,
                gridWidth: g.width,
                gridLeft: g.left,
                perRow,
                count: items.length,
                equalHeights: [...rows.values()].every((hs) => Math.max(...hs) - Math.min(...hs) < 1),
                overflow,
            });
        }
        return out;
    }, width);
}

async function noScroll(page) {
    return page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);
}

const browser = await chromium.launch({ channel: 'chrome' });
const accounts = tinker();
const member = await signIn(browser, accounts.member, accounts.password);
const admin = await signIn(browser, accounts.admin, accounts.password);
check('member signs in', member !== null);
check('admin signs in', admin !== null);

try {
    for (const theme of THEMES) {
        for (const width of [390, 768, 1440]) {
            const shots = width !== 768;
            const ctx = await context(browser, theme, width);
            const page = await ctx.newPage();

            for (const path of ['/', '/about', '/cv', '/publications', '/awards', '/tributes', '/community']) {
                await page.goto(BASE + path);
                await settle(page);
                if (shots && path === '/') await shoot(page, 'section[aria-labelledby="continue-the-story"]', `home-cards-${width}-${theme}`);
                if (shots && path === '/about') {
                    await shoot(page, 'nav[aria-label="Continue reading"]', `about-cards-${width}-${theme}`);
                    await shoot(page, '.prose-bio > p:nth-of-type(2), .prose-bio > p', `about-paragraph-${width}-${theme}`);
                }
                if (!CHECKING) continue;

                check(`${path} ${width} ${theme}: no horizontal scroll`, await noScroll(page));
                const type = await typography(page, path);
                check(`${path} ${width} ${theme}: body is Hanken 400 at ${width < 640 ? 17 : 18}px, leading 1.6`, type.body.weight === '400' && type.body.size === `${width < 640 ? 17 : 18}px` && Math.abs(type.body.lineHeight - 1.6) < 0.01, JSON.stringify(type.body));
                check(`${path} ${width} ${theme}: no Hanken text at weight 300`, type.problems.light.length === 0, type.problems.light.slice(0, 5).join('; '));
                check(`${path} ${width} ${theme}: no Hanken text under 15px but labels`, type.problems.small.length === 0, type.problems.small.slice(0, 5).join('; '));
                check(`${path} ${width} ${theme}: labels 500 at 0.24em`, type.problems.label.length === 0, type.problems.label.slice(0, 5).join('; '));

                for (const grid of await cards(page, width)) {
                    const expected = width >= 1280 ? Math.min(grid.count, 4) : width >= 700 ? Math.min(grid.count, 2) : 1;
                    check(`${path} ${width} ${theme}: cards fill the rule's width`, grid.ruleWidth === null || (Math.abs(grid.ruleWidth - grid.gridWidth) < 1 && Math.abs(grid.ruleLeft - grid.gridLeft) < 1), `rule ${grid.ruleWidth} grid ${grid.gridWidth}`);
                    check(`${path} ${width} ${theme}: ${expected} card(s) a row`, grid.perRow === expected, `${grid.perRow} a row`);
                    check(`${path} ${width} ${theme}: cards in a row are the same height`, grid.equalHeights);
                    check(`${path} ${width} ${theme}: nothing overflows a card`, grid.overflow.length === 0, grid.overflow.join('; '));
                }
            }
            await ctx.close();

            for (const [state, path, selector, name] of [
                [member, '/community/chat', '.chat-page, main', 'chat'],
                [admin, '/admin/content/publications/create', 'main', 'admin-form'],
            ]) {
                if (!state) continue;
                const signed = await context(browser, theme, width, state);
                const p = await signed.newPage();
                await p.goto(BASE + path);
                await p.waitForLoadState('networkidle').catch(() => {});
                await p.waitForTimeout(300);
                if (shots) await p.screenshot({ path: `${OUT}/${LABEL}-${name}-${width}-${theme}.png` });
                if (CHECKING) {
                    check(`${path} ${width} ${theme}: no horizontal scroll`, await noScroll(p));
                    const type = await typography(p, path);
                    check(`${path} ${width} ${theme}: no Hanken text at weight 300`, type.problems.light.length === 0, type.problems.light.slice(0, 5).join('; '));
                    check(`${path} ${width} ${theme}: no Hanken text under 15px but labels`, type.problems.small.length === 0, type.problems.small.slice(0, 5).join('; '));
                }
                await signed.close();
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
