/*
 | Browser checks for Admin › Appearance, through Playwright and Google Chrome against
 | https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:appearance
 |
 | tests/qa/appearance-setup.php prepares a QA admin, a QA member and a QA theme whose draft fails
 | the contrast check, applies each template in turn and restores Evergreen & Ivory at the end.
 |
 |   screen     the Appearance screen: template cards, the Basic colours, the Advanced colours and
 |              the contrast guard with a failing pair (screenshots); a colour typed into a hex field
 |              reaches the live preview frame before anything is saved.
 |   templates  each template applied in turn, then in the light and the dark mode: axe (WCAG 2.x
 |              A/AA) with no violations on the main pages, the chat and the dashboard, the theme's
 |              <style> block present (absent for the brand), no console errors, no horizontal
 |              scroll; a screenshot of the home page; then tests/qa/smoke.mjs on the main pages.
 |
 | Screenshots and report.json go to storage/qa/appearance/. Exit code 1 on a failure.
 | APPEARANCE_QA_ONLY_SCREEN=1 checks the screen alone; APPEARANCE_QA_SMOKE=0 skips smoke.mjs.
 */
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { execFileSync, spawnSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.APPEARANCE_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/appearance';
const MODES = ['light', 'dark'];
const PAGES = ['/', '/about', '/publications', '/tributes/new', '/login'];
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];
const SMOKE = process.env.APPEARANCE_QA_SMOKE !== '0';
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

function setup(env = {}) {
    const out = execFileSync('php', ['artisan', 'tinker', "--execute=require 'tests/qa/appearance-setup.php';"], { encoding: 'utf8', env: { ...process.env, ...env } });
    return JSON.parse(out.trim().split('\n').filter((l) => l.startsWith('{')).pop());
}

const qa = setup();
const browser = await chromium.launch({ channel: 'chrome' });

async function context(mode, { width = 1440, height = 900, state } = {}) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height }, colorScheme: mode, storageState: state, reducedMotion: 'reduce' });
    await ctx.addCookies([{ name: 'theme', value: mode, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
}

async function open(ctx, path, errors) {
    const page = await ctx.newPage();
    page.on('pageerror', (e) => errors.push(`${path}: ${e}`));
    page.on('console', (m) => m.type() === 'error' && errors.push(`${path}: ${m.text()}`));
    await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    await page.addStyleTag({ content: '[data-rise], [data-rise-group] > * { opacity: 1 !important; transform: none !important; }' });
    await page.waitForTimeout(150);
    return page;
}

async function signIn(email) {
    const ctx = await context('light');
    const page = await open(ctx, '/login', []);
    await page.fill('input[name=email]', email);
    await page.fill('input[name=password]', qa.password);
    await Promise.all([page.waitForNavigation(), page.click('form:has(input[name=password]) button[type=submit]')]);
    const state = await ctx.storageState();
    await ctx.close();
    return state;
}

const adminState = await signIn(qa.admin);
const memberState = await signIn(qa.member);

/* ------------------------------------------------------------------ the screen */
{
    const errors = [];
    const ctx = await context('light', { width: 1440, height: 1000, state: adminState });
    const page = await open(ctx, '/admin/settings/appearance', errors);

    await page.locator('[data-appearance-templates]').screenshot({ path: `${OUT}/screen-templates.png` });
    check('screen: every template has a card with two miniatures', (await page.locator('[data-preset]').count()) === qa.presets.length && (await page.locator('.theme-mini').count()) === qa.presets.length * 2);

    await page.click(`[data-edit-theme="${qa.failing}"]`);
    await page.waitForSelector('[data-contrast-failures]');
    await page.waitForTimeout(600);
    await page.evaluate(() => { document.querySelector('#appearance-editor-title').scrollIntoView({ block: 'start' }); window.scrollBy(0, -120); });
    await page.waitForTimeout(200);
    await page.screenshot({ path: `${OUT}/screen-editor.png` });
    await page.locator('[data-basic-colours]').scrollIntoViewIfNeeded();
    await page.locator('[data-basic-colours]').screenshot({ path: `${OUT}/screen-basic.png` });
    await page.locator('[data-contrast-guard]').screenshot({ path: `${OUT}/screen-contrast-failing.png` });
    check('screen: the contrast guard lists the failing pairs with a Fix', (await page.locator('[data-contrast-failure]').count()) > 0 && (await page.locator('[data-fix]').count()) > 0);

    await page.click('[data-advanced] > summary');
    await page.waitForTimeout(200);
    await page.evaluate(() => { document.querySelector('[data-advanced]').scrollIntoView({ block: 'start' }); window.scrollBy(0, -90); });
    await page.waitForTimeout(200);
    await page.screenshot({ path: `${OUT}/screen-advanced.png` });
    await page.locator('[data-token-group="text"]').screenshot({ path: `${OUT}/screen-advanced-font-colours.png` });
    check('screen: Advanced colours list the font colours', (await page.locator('[data-advanced] [data-token="link-hover"]').count()) === 1 && (await page.locator('[data-advanced] [data-token="placeholder"]').count()) === 1);

    // A colour typed into a hex field reaches the preview frame before it is saved.
    const frame = page.frameLocator('.appearance-preview__frame');
    await page.waitForFunction(() => document.querySelector('.appearance-preview__frame')?.contentDocument?.getElementById('appearance-live'));
    const field = page.locator('[data-token="surface"] .token-row__hex');
    await field.scrollIntoViewIfNeeded();
    await field.fill('#fbf6ea');
    await field.press('Enter');
    await page.waitForLoadState('networkidle');
    await page.waitForFunction(() => {
        try {
            const doc = document.querySelector('.appearance-preview__frame')?.contentDocument;
            return Boolean(doc?.body) && getComputedStyle(doc.body).backgroundColor === 'rgb(251, 246, 234)' && Boolean(document.querySelector('[data-unsaved]'));
        } catch (error) {
            return false;
        }
    }, null, { timeout: 20000, polling: 200 }).then(() => check('screen: the live preview follows an unsaved colour', true), (e) => check('screen: the live preview follows an unsaved colour', false, String(e).slice(0, 300)));
    check('screen: nothing was saved yet', (await page.locator('[data-unsaved]').count()) === 1);
    await page.locator('[data-basic-colours]').scrollIntoViewIfNeeded();
    await page.screenshot({ path: `${OUT}/screen-live-preview.png` });

    // Mode switch shows the dark values.
    await page.click('[data-mode-switch] [data-mode="dark"]');
    await page.waitForTimeout(800);
    await page.locator('[data-basic-colours]').screenshot({ path: `${OUT}/screen-basic-dark.png` });

    const axe = await new AxeBuilder({ page }).withTags(TAGS).exclude('.appearance-preview__frame').analyze();
    check('screen: axe', axe.violations.length === 0, axe.violations.map((v) => `${v.id}: ${v.nodes[0]?.target}`).join('; '));
    check('screen: no console errors', errors.length === 0, errors.join(' | '));
    await ctx.close();
}

/* ------------------------------------------------------------------ each template */
for (const slug of process.env.APPEARANCE_QA_ONLY_SCREEN ? [] : qa.presets) {
    setup({ QA_APPLY: slug });

    for (const mode of MODES) {
        const errors = [];
        const audited = [];
        const visit = async (path, state) => {
            const ctx = await context(mode, { state });
            const page = await open(ctx, path, errors);
            const hasBlock = (await page.locator('style[data-colour-theme]').count()) === 1;
            if (!(slug === 'evergreen-ivory' ? !hasBlock : hasBlock)) audited.push(`${path}: theme block ${hasBlock ? 'present' : 'missing'}`);
            const scroll = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
            if (scroll) audited.push(`${path}: horizontal scroll`);
            const axe = await new AxeBuilder({ page }).withTags(TAGS).analyze();
            for (const v of axe.violations) audited.push(`${path}: ${v.id} ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(', ')}`);
            if (path === '/') await page.screenshot({ path: `${OUT}/home-${slug}-${mode}.png` });
            await ctx.close();
        };

        for (const path of PAGES) await visit(path);
        await visit('/community/chat', memberState);
        await visit('/admin', adminState);
        check(`${slug} ${mode}: axe, theme block and layout on ${PAGES.length + 2} pages`, audited.length === 0, audited.join(' | '));
        check(`${slug} ${mode}: no console errors`, errors.length === 0, errors.join(' | '));
    }

    if (SMOKE) {
        const smoke = spawnSync('node', ['tests/qa/smoke.mjs'], {
            encoding: 'utf8',
            env: { ...process.env, SMOKE_PAGES: PAGES.join(','), SMOKE_WIDTHS: '360,1440', SMOKE_OUT: `${OUT}/smoke/${slug}`, SMOKE_MEMBER_EMAIL: qa.member, SMOKE_MEMBER_PASSWORD: qa.password },
        });
        const tail = (smoke.stdout + smoke.stderr).trim().split('\n').slice(-3).join(' / ');
        check(`${slug}: qa:smoke (both modes)`, smoke.status === 0, tail);
    }
}

setup({ QA_TEARDOWN: '1' });
await browser.close();
writeFileSync(`${OUT}/report.json`, JSON.stringify({ failures, results }, null, 2));
console.log(`\n${results.length - failures}/${results.length} checks passed. Screenshots in ${OUT}/`);
process.exit(failures ? 1 : 0);
