/*
 | Browser checks for the button system, the account menu, sign out and the two-factor
 | card, through Playwright and Google Chrome against https://profmokhidir.test and the
 | dev database.
 |
 |   npm run build && npm run qa:buttons
 |
 | tests/qa/buttons-auth-setup.php prepares an approved member, an editor and an admin.
 | In the light and the dark theme:
 |
 |   hover      every variant on /styleguide#buttons, on the page, on a card and on
 |              evergreen: the pointer rests on the button, the transition settles, and
 |              the label's computed colour must reach 4.5:1 against what is behind it
 |              (the button's own background composited over the surface). The reported
 |              cases are checked where they live: "Open the chat" on the community card
 |              and the ivory pill on the home hero.
 |   menu       signed out the bar shows a quiet Sign in; signed in (member, editor,
 |              admin) the account menu opens with the right items, the theme toggle
 |              and Sign out, closes on Escape, and Sign out lands on the home page with
 |              the notice.
 |   2fa card   the admin dashboard shows the dismissible card; "Not now" hides it.
 |   widths     /, /account, the chat and /admin at 390px and 1440px for each role: no
 |              horizontal scroll and no script errors.
 |
 | Screenshots and report.json go to storage/qa/buttons-auth/. Exit code 1 on a failure.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.BUTTONS_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/buttons-auth';
const THEMES = ['light', 'dark'];
const SETTLE_MS = 700; // the 400ms colour transition, with room to spare
const MIN_LABEL = 4.5;
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

function setup(env = {}) {
    const out = execFileSync('php', ['artisan', 'tinker', "--execute=require 'tests/qa/buttons-auth-setup.php';"], { encoding: 'utf8', env: { ...process.env, ...env } });
    return JSON.parse(out.trim().split('\n').filter((l) => l.startsWith('{')).pop());
}

const accounts = setup();
const browser = await chromium.launch({ channel: 'chrome' });
const errors = [];

async function context(theme, { width = 1440, height = 900, state } = {}) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width, height }, colorScheme: theme, storageState: state, reducedMotion: 'reduce' });
    await ctx.addCookies([{ name: 'theme', value: theme, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
}

async function open(ctx, path) {
    const p = await ctx.newPage();
    p.on('pageerror', (e) => errors.push(`${path}: ${e}`));
    await p.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    await p.addStyleTag({ content: '*, html { scroll-behavior: auto !important; } [data-rise], [data-rise-group] > * { opacity: 1 !important; transform: none !important; }' });
    return p;
}

async function signIn(role, theme) {
    const ctx = await context(theme);
    const p = await open(ctx, '/login');
    await p.fill('input[name=email]', accounts[role]);
    await p.fill('input[name=password]', accounts.password);
    await Promise.all([p.waitForNavigation(), p.click('form:has(input[name=password]) button[type=submit]')]);
    const state = await ctx.storageState();
    await ctx.close();
    return state;
}

/* The label colour of a button against what is behind it, as the browser paints it now. */
function measure(el) {
    const parse = (value) => {
        let m = value.match(/^rgba?\(([^)]+)\)$/);
        if (m) {
            const [r, g, b, a = '1'] = m[1].split(/[,\s/]+/).filter(Boolean);
            return [Number(r) / 255, Number(g) / 255, Number(b) / 255, Number(a)];
        }
        m = value.match(/^color\(srgb ([\d.e-]+) ([\d.e-]+) ([\d.e-]+)(?: \/ ([\d.e-]+))?\)$/);
        if (m) return [Number(m[1]), Number(m[2]), Number(m[3]), m[4] === undefined ? 1 : Number(m[4])];
        return null;
    };
    const over = (top, bottom) => [0, 1, 2].map((i) => top[i] * top[3] + bottom[i] * (1 - top[3])).concat(1);
    const lum = (c) => c.slice(0, 3).map((v) => (v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4)).reduce((sum, v, i) => sum + v * [0.2126, 0.7152, 0.0722][i], 0);

    // Layers from the page down to the button: every background on the way is composited.
    const layers = [];
    for (let node = el; node; node = node.parentElement) {
        const bg = parse(getComputedStyle(node).backgroundColor);
        if (bg && bg[3] > 0) layers.push(bg);
    }
    let behind = [1, 1, 1, 1];
    for (const layer of layers.reverse()) behind = over(layer, behind);

    const ink = parse(getComputedStyle(el).color) ?? [0, 0, 0, 1];
    const fg = over(ink, behind);
    const [l1, l2] = [lum(fg), lum(behind)];
    return { ratio: (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05), color: getComputedStyle(el).color, background: getComputedStyle(el).backgroundColor };
}

async function hoverAndMeasure(p, locator) {
    await locator.scrollIntoViewIfNeeded();
    await locator.hover();
    await p.waitForTimeout(SETTLE_MS);
    return locator.evaluate(measure);
}

for (const theme of THEMES) {
    /* ---------------------------------------------------------------- hover, every variant */
    const ctx = await context(theme);
    const guide = await open(ctx, '/styleguide#buttons');
    for (const surface of ['page', 'raised', 'band']) {
        const panel = guide.locator(`[data-button-surface="${surface}"]`);
        const samples = panel.locator('[data-button-sample]');
        for (let i = 0; i < (await samples.count()); i++) {
            const button = samples.nth(i);
            const variant = await button.getAttribute('data-button-sample');
            const result = await hoverAndMeasure(guide, button);
            check(`${theme} ${surface} ${variant} hover label`, result.ratio >= MIN_LABEL, `${result.ratio.toFixed(2)}:1 (${result.color} on ${result.background})`);
            await panel.screenshot({ path: `${OUT}/hover-${theme}-${surface}-${variant}.png` });
        }
        await guide.mouse.move(0, 0);
    }

    /* The reported cases where they live. */
    const memberState = await signIn('member', theme);
    const memberCtx = await context(theme, { state: memberState });
    const community = await open(memberCtx, '/community');
    const openChat = community.locator('[data-standing]').getByRole('link', { name: 'Open the chat' });
    const chatHover = await hoverAndMeasure(community, openChat);
    check(`${theme} "Open the chat" hover label`, chatHover.ratio >= MIN_LABEL, `${chatHover.ratio.toFixed(2)}:1 (${chatHover.color} on ${chatHover.background})`);
    await community.locator('[data-standing]').screenshot({ path: `${OUT}/hover-${theme}-community-open-the-chat.png` });

    const home = await open(ctx, '/');
    const readStory = home.locator('.btn--primary').first();
    const heroHover = await hoverAndMeasure(home, readStory);
    check(`${theme} hero ivory pill hover label`, heroHover.ratio >= MIN_LABEL, `${heroHover.ratio.toFixed(2)}:1 (${heroHover.color} on ${heroHover.background})`);
    await readStory.locator('xpath=..').screenshot({ path: `${OUT}/hover-${theme}-hero-pills.png` });

    /* ---------------------------------------------------------------- signed out: Sign in */
    check(`${theme} visitor 1440 sees Sign in`, await home.locator('[data-sign-in-link]').isVisible());
    check(`${theme} visitor has no account menu`, (await home.locator('[data-account-menu]').count()) === 0);

    /* ---------------------------------------------------------------- account menu per role */
    const states = { member: memberState, editor: await signIn('editor', theme), admin: await signIn('admin', theme) };
    const expected = { member: ['account', 'chat'], editor: ['account', 'dashboard'], admin: ['account', 'chat', 'dashboard'] };

    for (const [role, state] of Object.entries(states)) {
        for (const width of [390, 1440]) {
            const roleCtx = await context(theme, { width, height: width === 390 ? 844 : 900, state });
            const p = await open(roleCtx, '/about');
            const button = p.locator('.site-account__button');
            await button.click();
            await p.waitForTimeout(300);
            const items = await p.locator('#site-account-menu [data-account-item]').evaluateAll((els) => els.map((e) => e.dataset.accountItem));
            check(`${theme} ${role} ${width} menu items`, JSON.stringify(items) === JSON.stringify(expected[role]), items.join(', '));
            check(`${theme} ${role} ${width} menu has theme toggle and sign out`, (await p.locator('#site-account-menu [data-theme-toggle]').isVisible()) && (await p.locator('#site-account-menu [data-sign-out] button').isVisible()));
            const box = await p.locator('#site-account-menu').boundingBox();
            check(`${theme} ${role} ${width} menu inside the viewport`, box && box.x >= 0 && box.x + box.width <= width + 0.5, box ? `${Math.round(box.x)}–${Math.round(box.x + box.width)}` : 'not shown');
            if (role === 'member') await p.screenshot({ path: `${OUT}/account-menu-${theme}-${width}.png`, clip: { x: 0, y: 0, width, height: Math.min(560, width === 390 ? 640 : 560) } });
            await p.keyboard.press('Escape');
            await p.waitForTimeout(300);
            check(`${theme} ${role} ${width} Escape closes and returns focus`, !(await p.locator('#site-account-menu').isVisible()) && (await button.evaluate((b) => b === document.activeElement)));

            // No horizontal scroll on the pages this role uses.
            const paths = ['/', '/account', ...(role !== 'editor' ? ['/community/chat'] : []), ...(role !== 'member' ? ['/admin'] : [])];
            for (const path of paths) {
                const page = await open(roleCtx, path);
                const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
                check(`${theme} ${role} ${width} ${path} no horizontal scroll`, overflow <= 0, `${overflow}px`);
                await page.screenshot({ path: `${OUT}/page-${theme}-${role}-${width}${path.replaceAll('/', '_') || '_home'}.png` });
                await page.close();
            }
            await roleCtx.close();
        }
    }

    /* ---------------------------------------------------------------- the two-factor card */
    for (const width of [390, 1440]) {
        const adminCtx = await context(theme, { width, height: width === 390 ? 844 : 900, state: states.admin });
        const dash = await open(adminCtx, '/admin');
        const card = dash.locator('[data-two-factor-card]');
        check(`${theme} ${width} admin dashboard shows the two-factor card`, await card.isVisible());
        await card.screenshot({ path: `${OUT}/two-factor-card-${theme}-${width}.png` });
        await adminCtx.close();
    }

    /* ---------------------------------------------------------------- sign out from the menu */
    const outCtx = await context(theme, { state: await signIn('member', theme) });
    const out = await open(outCtx, '/about');
    await out.locator('.site-account__button').click();
    await Promise.all([out.waitForNavigation(), out.locator('#site-account-menu [data-sign-out] button').click()]);
    check(`${theme} sign out lands on home`, new URL(out.url()).pathname === '/', out.url());
    check(`${theme} sign out shows the notice`, await out.locator('[data-signed-out-notice]').isVisible());
    await out.screenshot({ path: `${OUT}/signed-out-${theme}.png` });
    check(`${theme} signed out shows Sign in again`, await out.locator('[data-sign-in-link]').isVisible());

    await outCtx.close();
    await memberCtx.close();
    await ctx.close();
}

/* Dismissing the card is remembered (run once, light theme, after the screenshots). */
{
    const ctx = await context('light', { state: await signIn('admin', 'light') });
    const dash = await open(ctx, '/admin');
    await Promise.all([dash.waitForNavigation(), dash.locator('[data-two-factor-dismiss] button').click()]);
    check('two-factor card dismissed', (await dash.locator('[data-two-factor-card]').count()) === 0);
    const again = await open(ctx, '/admin');
    check('two-factor card stays dismissed', (await again.locator('[data-two-factor-card]').count()) === 0);
    await ctx.close();
}

check('no script errors', errors.length === 0, errors.slice(0, 5).join(' | '));
await browser.close();
setup({ QA_TEARDOWN: '1' });

writeFileSync(`${OUT}/report.json`, JSON.stringify({ failures, results }, null, 2));
console.log(`\n${results.length - failures}/${results.length} checks passed; screenshots in ${OUT}/`);
process.exit(failures ? 1 : 0);
