/*
 | Browser tests for focus and the theme toggle, through Playwright and Google Chrome
 | against https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:focus
 |
 | tests/qa/focus-setup.php prepares an editor and an approved member. In both themes:
 |
 |   text entry  clicks into every kind of field (text, email, password, search, url,
 |               number, textarea, select and the rich text editor) on the sign-in page,
 |               the community signup, the search dialog, the chat composer and an admin
 |               form, and asserts that neither the field nor its frame draws an outline
 |               or a box-shadow ring: the field's own line is 2px and a faint tint sits
 |               behind it instead. Tabbing into a field draws no ring either.
 |   keyboard    tabs to a button and asserts the 2px ring offset 2px is visible.
 |   theme       one button, no menu: a click, Enter and Space switch at once, the label
 |               and aria-pressed follow, the cookie and localStorage keep the choice.
 |
 | Screenshots of a focused chat composer, sign-in field and admin field in each theme
 | go to storage/qa/focus/, with report.json. Exit code 1 when a check fails.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = (process.env.FOCUS_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/focus';
const THEMES = ['light', 'dark'];
const SETTLE_MS = 400; // the 200ms line transition, with room to spare
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

function setup(env = {}) {
    const out = execFileSync('php', ['artisan', 'tinker', "--execute=require 'tests/qa/focus-setup.php';"], { encoding: 'utf8', env: { ...process.env, ...env } });
    return JSON.parse(out.trim().split('\n').filter((l) => l.startsWith('{')).pop());
}

const accounts = setup();
const browser = await chromium.launch({ channel: 'chrome' });
const errors = [];

async function context(theme, state = undefined) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 }, colorScheme: theme, storageState: state });
    await ctx.addCookies([{ name: 'theme', value: theme, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
}

async function page(ctx, path) {
    const p = await ctx.newPage();
    p.on('pageerror', (e) => errors.push(`${path}: ${e}`));
    await p.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    await p.addStyleTag({ content: '*, html { scroll-behavior: auto !important; }' });
    return p;
}

async function signIn(email) {
    const ctx = await context('light');
    const p = await page(ctx, '/login');
    await p.fill('input[name=email]', email);
    await p.fill('input[name=password]', accounts.password);
    await Promise.all([p.waitForNavigation(), p.click('form:has(input[name=password]) button[type=submit]')]);
    const state = await ctx.storageState();
    await ctx.close();
    return state;
}

/* Everything the page draws around the focused field: the field, its frame and three ancestors. */
function inspectFocus() {
    const el = document.activeElement;
    const frame = el.closest('.form-control, .palette__field');
    const parse = (shadow) => (shadow === 'none' ? [] : shadow.split(/,(?![^(]*\))/).map((part) => {
        const nums = [...part.replace(/rgba?\([^)]*\)|color\([^)]*\)|oklch\([^)]*\)/g, '').matchAll(/(-?[\d.]+)px/g)].map((m) => parseFloat(m[1]));
        const [x = 0, y = 0, blur = 0, spread = 0] = nums;
        return { inset: part.includes('inset'), x, y, blur, spread };
    }));
    const draws = (node) => {
        const s = getComputedStyle(node);
        const outline = s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) > 0;
        // A ring: any outer shadow, or an inset one on more than the foot (x, blur or spread, or a top line).
        const ring = parse(s.boxShadow).filter((sh) => !sh.inset || sh.x !== 0 || sh.blur !== 0 || sh.spread !== 0 || sh.y > 0);
        return { outline, ring: ring.length > 0, shadow: s.boxShadow };
    };
    const nodes = [el];
    if (frame && frame !== el) nodes.push(frame);
    let up = (frame ?? el).parentElement;
    for (let i = 0; i < 3 && up; i++, up = up.parentElement) nodes.push(up);
    const drawn = nodes.map((node) => ({ tag: node.tagName.toLowerCase() + (node.className && typeof node.className === 'string' ? '.' + node.className.trim().split(/\s+/)[0] : ''), ...draws(node) }));
    const lineHost = frame ?? el;
    const s = getComputedStyle(lineHost);
    const foot = parse(s.boxShadow).find((sh) => sh.inset && sh.y < 0);
    return {
        id: el.id || el.getAttribute('name') || el.className,
        drawn: drawn.filter((d) => d.outline || d.ring),
        line: parseFloat(s.borderBottomWidth) + (foot ? -foot.y : 0),
        background: s.backgroundColor,
    };
}

function backgroundOf(selector) {
    const el = document.querySelector(selector);
    const host = el?.closest('.form-control, .palette__field') ?? el;
    return host ? getComputedStyle(host).backgroundColor : null;
}

async function clickField(p, label, selector, theme, shot = null) {
    const target = p.locator(selector).first();
    if ((await target.count()) === 0) return check(`${theme}: ${label} present`, false, selector);
    await target.scrollIntoViewIfNeeded();
    await p.mouse.move(0, 0);
    await p.evaluate(() => document.activeElement?.blur?.());
    await p.waitForTimeout(SETTLE_MS);
    const before = await p.evaluate(backgroundOf, selector);
    if (selector.includes('select')) await target.focus(); // a click opens the native list; focus it as the click would
    else await target.click();
    await p.waitForTimeout(SETTLE_MS);
    const info = await p.evaluate(inspectFocus);
    check(`${theme}: ${label} — no outline or ring when clicked`, info.drawn.length === 0, info.drawn.map((d) => `${d.tag} ${d.outline ? 'outline' : ''} ${d.ring ? d.shadow : ''}`).join('; '));
    check(`${theme}: ${label} — its line is 2px`, Math.abs(info.line - 2) < 0.1, `${info.line}px`);
    check(`${theme}: ${label} — a tint settles behind it`, before !== info.background, `${before} → ${info.background}`);
    if (shot) {
        const box = await target.evaluate((el) => {
            const field = el.closest('[data-form-field], .chat-composer, .form-field') ?? el.closest('.form-control') ?? el;
            const r = field.getBoundingClientRect();
            return { x: r.x, y: r.y, width: r.width, height: r.height };
        });
        const pad = 24;
        await p.screenshot({ path: join(OUT, `${shot}-${theme}.png`), clip: { x: Math.max(0, box.x - pad), y: Math.max(0, box.y - pad), width: box.width + pad * 2, height: box.height + pad * 2 } });
    }
    return info;
}

const editorState = await signIn(accounts.editor);
const memberState = await signIn(accounts.member);

for (const theme of THEMES) {
    // Sign-in: email and password; then the keyboard ring on the button.
    {
        const ctx = await context(theme);
        const p = await page(ctx, '/login');
        check(`${theme}: sign-in in the ${theme} theme`, (await p.evaluate(() => document.documentElement.dataset.theme)) === theme);
        await clickField(p, 'sign-in email', 'input[name=email]', theme, 'sign-in');
        await clickField(p, 'sign-in password', 'input[name=password]', theme);

        // Keyboard: Tab into a text field draws no ring either; Tab on to a button draws the 2px ring offset 2px.
        await p.locator('body').click({ position: { x: 5, y: 5 } });
        await p.focus('input[name=email]');
        await p.keyboard.press('Shift+Tab');
        await p.keyboard.press('Tab');
        await p.waitForTimeout(SETTLE_MS);
        const tabbed = await p.evaluate(inspectFocus);
        check(`${theme}: sign-in email — no ring when tabbed to`, tabbed.drawn.length === 0 && Math.abs(tabbed.line - 2) < 0.1, JSON.stringify(tabbed.drawn));
        let ring = null;
        for (let i = 0; i < 12; i++) {
            await p.keyboard.press('Tab');
            const hit = await p.evaluate(() => {
                const el = document.activeElement;
                if (!(el instanceof HTMLButtonElement) || el.type !== 'submit' || el.hasAttribute('data-theme-toggle-button')) return null;
                const s = getComputedStyle(el);
                return { text: el.textContent.trim(), style: s.outlineStyle, width: s.outlineWidth, offset: s.outlineOffset, visible: el.matches(':focus-visible') };
            });
            if (hit) { ring = hit; break; }
        }
        check(`${theme}: keyboard focus on "${ring?.text ?? '?'}" shows the 2px ring offset 2px`, ring !== null && ring.visible && ring.style === 'solid' && ring.width === '2px' && ring.offset === '2px', JSON.stringify(ring));
        await p.screenshot({ path: join(OUT, `keyboard-button-${theme}.png`), clip: await p.locator(':focus').evaluate((el) => { const r = el.getBoundingClientRect(); return { x: r.x - 24, y: r.y - 24, width: r.width + 48, height: r.height + 48 }; }) });

        await ctx.close();
    }

    // Community signup: text, email, url, select and textarea, whichever the form has.
    {
        const ctx = await context(theme);
        const p = await page(ctx, '/community');
        for (const [label, selector] of [
            ['signup text field', 'main input.form-control__input[type=text]'],
            ['signup email field', 'main input.form-control__input[type=email]'],
            ['signup url field', 'main input.form-control__input[type=url]'],
            ['signup select', 'main select.form-control__input'],
            ['signup textarea', 'main textarea.form-control__input'],
        ]) {
            if ((await p.locator(selector).count()) > 0) await clickField(p, label, selector, theme);
        }
        await ctx.close();
    }

    // The search dialog, opened from the masthead.
    {
        const ctx = await context(theme);
        const p = await page(ctx, '/');
        await p.click('.site-search-toggle');
        await p.waitForSelector('#site-search-input', { state: 'visible' });
        await p.waitForTimeout(1200);
        await clickField(p, 'search dialog field', '#site-search-input', theme);
        await ctx.close();
    }

    // The chat composer.
    {
        const ctx = await context(theme, memberState);
        const p = await page(ctx, '/community/chat');
        await clickField(p, 'chat composer', 'textarea.chat-composer__input', theme, 'chat-composer');
        await ctx.close();
    }

    // An admin form: text, url, number, select, textarea and the rich text editor.
    {
        const ctx = await context(theme, editorState);
        const p = await page(ctx, '/admin/content/publications/create');
        await clickField(p, 'admin text field', 'main input.form-control__input[type=text]', theme, 'admin-field');
        for (const [label, selector] of [
            ['admin url field', 'main input.form-control__input[type=url]'],
            ['admin number field', 'main input.form-control__input[inputmode=numeric], main input.form-control__input[type=number]'],
            ['admin select', 'main select.form-control__input'],
            ['admin textarea', 'main textarea.form-control__input'],
        ]) {
            if ((await p.locator(selector).count()) > 0) await clickField(p, label, selector, theme);
        }
        // The rich text editor and the tags field, on the commentary form.
        await p.goto(`${BASE}/admin/content/commentary/create`, { waitUntil: 'networkidle' });
        await clickField(p, 'admin rich text editor', 'main .form-control .ProseMirror', theme, 'admin-editor');
        if ((await p.locator('main .form-control--tags input').count()) > 0) await clickField(p, 'admin tags field', 'main .form-control--tags input', theme);
        // The command palette's search.
        await p.keyboard.press(process.platform === 'darwin' ? 'Meta+k' : 'Control+k');
        if ((await p.locator('.palette__input').count()) > 0) {
            await p.waitForSelector('.palette__input', { state: 'visible' });
            await clickField(p, 'command palette search', '.palette__input', theme);
        }
        await ctx.close();
    }
}

// The theme toggle: one button, no menu, switching at once.
{
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 }, colorScheme: 'dark' });
    const p = await page(ctx, '/');
    const state = () => p.evaluate(() => {
        const button = document.querySelector('#theme-switch-masthead');
        return {
            theme: document.documentElement.getAttribute('data-theme'),
            label: button.getAttribute('aria-label'),
            pressed: button.getAttribute('aria-pressed'),
            menus: document.querySelectorAll('[role=menu] [role=menuitemradio]').length,
            stored: localStorage.getItem('theme'),
            cookie: (document.cookie.match(/(?:^|;\s*)theme=([a-z]+)/) || [])[1] ?? null,
            sun: getComputedStyle(button.querySelector('.theme-toggle__icon--sun')).display !== 'none',
        };
    });
    let s = await state();
    check('first visit follows the device (dark) without saving anything', s.theme === null && s.label === 'Switch to light theme' && s.pressed === 'true' && s.sun && s.stored === null && s.cookie === null, JSON.stringify(s));
    check('no theme menu anywhere', s.menus === 0);
    await p.click('#theme-switch-masthead');
    await p.waitForTimeout(100);
    s = await state();
    check('a click switches to light at once and keeps it', s.theme === 'light' && s.label === 'Switch to dark theme' && s.pressed === 'false' && !s.sun && s.stored === 'light' && s.cookie === 'light', JSON.stringify(s));
    check('focus stays on the toggle', await p.evaluate(() => document.activeElement?.id === 'theme-switch-masthead'));
    await p.keyboard.press('Enter');
    await p.waitForTimeout(100);
    s = await state();
    check('Enter switches back to dark', s.theme === 'dark' && s.pressed === 'true' && s.cookie === 'dark', JSON.stringify(s));
    await p.keyboard.press('Space');
    await p.waitForTimeout(100);
    s = await state();
    check('Space switches to light', s.theme === 'light' && s.pressed === 'false', JSON.stringify(s));
    await p.reload({ waitUntil: 'networkidle' });
    s = await state();
    check('the choice survives a reload (the server prints it)', s.theme === 'light' && s.label === 'Switch to dark theme', JSON.stringify(s));
    await p.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    check('the choice is used on the sign-in pages too', (await p.evaluate(() => document.documentElement.dataset.theme)) === 'light');
    await ctx.close();

    // A device still remembering the retired "system" is pinned to the theme it resolves to.
    const legacy = await browser.newContext({ ignoreHTTPSErrors: true, colorScheme: 'dark' });
    await legacy.addCookies([{ name: 'theme', value: 'system', domain: HOST, path: '/', sameSite: 'Lax' }]);
    const lp = await page(legacy, '/');
    const pinned = await lp.evaluate(() => ({ theme: document.documentElement.dataset.theme, stored: localStorage.getItem('theme'), cookie: (document.cookie.match(/(?:^|;\s*)theme=([a-z]+)/) || [])[1] }));
    check('a legacy "system" cookie becomes the resolved theme', pinned.theme === 'dark' && pinned.stored === 'dark' && pinned.cookie === 'dark', JSON.stringify(pinned));
    await legacy.close();

    // The account page has the same toggle and no three-way choice.
    const account = await browser.newContext({ ignoreHTTPSErrors: true, storageState: memberState, colorScheme: 'light' });
    const ap = await page(account, '/account');
    const toggles = await ap.evaluate(() => ({ toggle: !!document.querySelector('#theme-switch-account[aria-pressed]'), radios: document.querySelectorAll('input[name=theme][type=radio]').length }));
    check('the account page offers the same toggle, no radios', toggles.toggle && toggles.radios === 0, JSON.stringify(toggles));
    await ap.click('#theme-switch-account');
    await ap.waitForResponse((r) => r.url().endsWith('/theme') && r.request().method() === 'PATCH');
    await account.close();
}

check('no page errors', errors.length === 0, errors.slice(0, 5).join(' | '));

await browser.close();
setup({ QA_TEARDOWN: '1' });
writeFileSync(join(OUT, 'report.json'), JSON.stringify({ base: BASE, generated: new Date().toISOString(), failures, results }, null, 2));
console.log(`\n${results.length - failures}/${results.length} checks passed. Screenshots in ${OUT}/`);
process.exit(failures === 0 ? 0 : 1);
