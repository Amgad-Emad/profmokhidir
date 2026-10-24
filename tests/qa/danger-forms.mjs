/*
 | Browser checks for destructive actions and form alignment, through Playwright and
 | Google Chrome against https://profmokhidir.test and the dev database.
 |
 |   npm run build && npm run qa:danger-forms
 |
 | tests/qa/danger-forms-setup.php prepares an admin and two QA publications (one live,
 | one in the trash) and removes them afterwards. In the light and the dark theme:
 |
 |   trash      the bulk bar's "Move to trash" and the row menu item are danger red (the
 |              computed colour is the --danger token) with the trash icon; moving to the
 |              trash shows the Undo toast and no dialog; Undo restores it.
 |   dialog     "Delete permanently" in the trash opens the danger dialog: the danger icon,
 |              the title naming the type, Cancel focused, the filled danger confirm whose
 |              label reaches 4.5:1 on its fill; Escape closes it and nothing is deleted.
 |   alignment  every form (admin content and settings, users, media, account, sign-in,
 |              community signup, tribute form) at 768, 1024 and 1440px: in every .form-row
 |              the top edges of the controls of fields side by side are equal (0.5px) and the
 |              inputs are the same height; with an error shown under one field (site
 |              settings) its neighbour's control does not move; no horizontal scroll.
 |
 | Screenshots and report.json go to storage/qa/danger-forms/. Exit code 1 on a failure.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.DANGER_QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/danger-forms';
const THEMES = ['light', 'dark'];
const WIDTHS = [768, 1024, 1440];
const TOLERANCE = 0.5;
mkdirSync(OUT, { recursive: true });

const results = [];
let failures = 0;
function check(name, ok, detail = '') {
    results.push({ name, ok: Boolean(ok), detail: String(detail ?? '') });
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${detail ? ` — ${detail}` : ''}`);
}

function setup(env = {}) {
    const out = execFileSync('php', ['artisan', 'tinker', "--execute=require 'tests/qa/danger-forms-setup.php';"], { encoding: 'utf8', env: { ...process.env, ...env } });
    return JSON.parse(out.trim().split('\n').filter((l) => l.startsWith('{')).pop());
}

const qa = setup();
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

async function signIn(theme) {
    const ctx = await context(theme);
    const p = await open(ctx, '/login');
    await p.fill('input[name=email]', qa.email);
    await p.fill('input[name=password]', qa.password);
    await Promise.all([p.waitForNavigation(), p.click('form:has(input[name=password]) button[type=submit]')]);
    const state = await ctx.storageState();
    await ctx.close();
    return state;
}

/* The computed value of a token on an element, resolved by the browser as a colour. */
async function tokenColour(locator, token) {
    return locator.evaluate((el, name) => {
        const probe = document.createElement('span');
        probe.style.color = `var(${name})`;
        el.appendChild(probe);
        const colour = getComputedStyle(probe).color;
        probe.remove();
        return colour;
    }, token);
}

function luminance(rgb) {
    const [r, g, b] = rgb.match(/[\d.]+/g).slice(0, 3).map((v) => {
        const c = Number(v) / 255;
        return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function ratio(a, b) {
    const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (l1 + 0.05) / (l2 + 0.05);
}

/*
 | Every .form-row on the page: its fields grouped by the line they sit on (the field's own top),
 | and in each group the top edge and height of each control (.form-control, or the check/toggle).
 */
async function measureRows(page) {
    return page.evaluate(() => {
        const rows = [];
        document.querySelectorAll('.form-row').forEach((row, index) => {
            if (!row.getClientRects().length) return;
            const lines = new Map();
            row.querySelectorAll(':scope > .form-field').forEach((field) => {
                const control = field.querySelector(':scope > .form-field__control .form-control, :scope > .form-field__control > *, :scope > .form-check, :scope > .form-toggle');
                if (!control) return;
                const top = Math.round(field.getBoundingClientRect().top);
                const box = control.getBoundingClientRect();
                const name = field.querySelector('.form-label')?.textContent.trim() ?? field.textContent.trim().slice(0, 30);
                const entry = { name, top: box.top, height: box.height, input: control.classList.contains('form-control') && !control.querySelector('textarea') };
                if (!lines.has(top)) lines.set(top, []);
                lines.get(top).push(entry);
            });
            for (const fields of lines.values()) {
                if (fields.length > 1) rows.push({ index, fields });
            }
        });
        return { rows, overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth };
    });
}

function assessRows(label, measured) {
    check(`${label} no horizontal scroll`, measured.overflow <= 0, `${measured.overflow}px`);
    for (const { index, fields } of measured.rows) {
        const tops = fields.map((f) => f.top);
        const spread = Math.max(...tops) - Math.min(...tops);
        check(`${label} row ${index + 1} control tops equal (${fields.map((f) => f.name).join(' | ')})`, spread <= TOLERANCE, `spread ${spread.toFixed(2)}px`);
        const inputs = fields.filter((f) => f.input).map((f) => f.height);
        if (inputs.length > 1) {
            const hSpread = Math.max(...inputs) - Math.min(...inputs);
            check(`${label} row ${index + 1} input heights equal`, hSpread <= TOLERANCE, inputs.map((h) => h.toFixed(1)).join(', '));
        }
    }
    return measured.rows.length;
}

const ADMIN_FORMS = [
    '/admin/settings',
    '/admin/content/profile',
    '/admin/content/publications/create',
    '/admin/content/awards/create',
    '/admin/content/commentary/create',
    '/admin/content/cv/create',
    '/admin/content/teaching/create',
    '/admin/content/other/create',
    '/admin/content/home-quotes/create',
    '/admin/media/videos/create',
    '/admin/media/albums/create',
    '/admin/media/library',
    '/admin/users',
    '/account',
];
const PUBLIC_FORMS = ['/login', '/forgot-password', '/community', '/tributes/new'];

for (const theme of THEMES) {
    const state = await signIn(theme);

    /* ---- trash: the danger action, the Undo toast and no dialog. */
    {
        const ctx = await context(theme, { state });
        const p = await open(ctx, `/admin/content/publications?q=${encodeURIComponent('QA danger sample')}`);
        const row = p.locator(`tr[data-id="${qa.liveId}"]`);
        await row.locator('input[type=checkbox]').check();
        const bulkTrash = p.locator('[data-bulk="trash"]');
        await bulkTrash.waitFor();
        const danger = await tokenColour(bulkTrash, '--danger');
        const style = await bulkTrash.evaluate((el) => ({ color: getComputedStyle(el).color, border: getComputedStyle(el).borderTopColor, icon: !!el.querySelector('svg') }));
        check(`${theme} bulk "Move to trash" is danger red`, style.color === danger && style.border === danger, `${style.color} / ${style.border} vs ${danger}`);
        check(`${theme} bulk "Move to trash" has the trash icon`, style.icon);
        await row.locator('.row-menu__button').click();
        const item = row.locator('[data-delete]');
        await item.waitFor();
        check(`${theme} row menu "Move to trash" is danger red`, (await item.evaluate((el) => getComputedStyle(el).color)) === (await tokenColour(item, '--danger')));
        await p.waitForTimeout(450);
        await p.screenshot({ path: `${OUT}/trash-action-${theme}.png`, clip: await clipAround(p, [row, p.locator('[data-bulk-bar]')]) });

        await item.click();
        const toast = p.locator('[data-toast]').filter({ hasText: 'moved to the trash' });
        await toast.waitFor();
        check(`${theme} move to trash opens no dialog`, (await p.locator('[data-confirm-dialog]').count()) === 0);
        check(`${theme} move to trash offers Undo`, await toast.locator('[data-toast-undo]').isVisible());
        await p.screenshot({ path: `${OUT}/trash-undo-toast-${theme}.png`, clip: await clipAround(p, [toast]) });
        await toast.locator('[data-toast-undo]').click();
        await p.locator('[data-toast]').filter({ hasText: 'restored' }).waitFor();
        check(`${theme} Undo restores the publication`, (await p.locator(`tr[data-id="${qa.liveId}"]`).count()) === 1);
        await ctx.close();
    }

    /* ---- the danger confirmation dialog for a permanent delete. */
    {
        const ctx = await context(theme, { state });
        const p = await open(ctx, `/admin/content/publications?view=trash&q=${encodeURIComponent('QA danger sample')}`);
        const purge = p.locator(`tr[data-id="${qa.trashedId}"] [data-purge]`);
        check(`${theme} "Delete permanently" is danger red`, (await purge.evaluate((el) => getComputedStyle(el).color)) === (await tokenColour(purge, '--danger')));
        await purge.click();
        const dialog = p.locator('[data-confirm-dialog] [role=alertdialog]');
        await dialog.waitFor();
        await p.waitForTimeout(300);
        check(`${theme} dialog title names the type`, (await dialog.locator('#confirm-title').textContent()).trim() === 'Delete this publication permanently?');
        check(`${theme} dialog shows the danger icon`, await dialog.locator('[data-danger-icon] svg').isVisible());
        check(`${theme} dialog says what will happen`, (await dialog.locator('#confirm-description').textContent()).includes('This cannot be undone.'));
        check(`${theme} Cancel is focused`, await dialog.locator('[data-cancel]').evaluate((el) => el === document.activeElement));
        const confirm = dialog.locator('[data-confirm]');
        const fill = await confirm.evaluate((el) => ({ bg: getComputedStyle(el).backgroundColor, ink: getComputedStyle(el).color }));
        check(`${theme} confirm is the filled danger pill`, fill.bg === (await tokenColour(confirm, '--danger-surface')), fill.bg);
        check(`${theme} confirm label reaches 4.5:1`, ratio(fill.ink, fill.bg) >= 4.5, `${ratio(fill.ink, fill.bg).toFixed(2)}:1`);
        await p.screenshot({ path: `${OUT}/confirm-dialog-${theme}.png` });
        await p.keyboard.press('Escape');
        await dialog.waitFor({ state: 'detached' });
        check(`${theme} Escape keeps the record`, (await p.locator(`tr[data-id="${qa.trashedId}"]`).count()) === 1);
        await ctx.close();
    }

    /* ---- the reported form: site settings, with and without an error under the phone. */
    {
        const ctx = await context(theme, { state, height: 1100 });
        const p = await open(ctx, '/admin/settings');
        const form = p.locator('[data-site-settings]');
        await form.screenshot({ path: `${OUT}/site-settings-${theme}.png` });
        const phone = p.locator('input[id$="contact_phone"], input[type=tel]').first();
        await phone.fill('call me maybe');
        await p.locator('[data-save]').click();
        await p.locator('[data-form-error]:not(:empty)').first().waitFor({ timeout: 5000 }).catch(() => {});
        await p.waitForTimeout(400);
        const measured = await measureRows(p);
        const shown = await p.locator('[data-form-error]:not(:empty)').count();
        check(`${theme} site settings shows the phone error`, shown > 0, `${shown} message(s)`);
        assessRows(`${theme} site settings with an error`, measured);
        await form.screenshot({ path: `${OUT}/site-settings-error-${theme}.png` });
        await ctx.close();
    }

    /* ---- alignment of every form at every width. */
    for (const width of WIDTHS) {
        const ctx = await context(theme, { state, width, height: 1000 });
        let rows = 0;
        for (const path of ADMIN_FORMS) {
            const p = await open(ctx, path);
            rows += assessRows(`${theme} ${width} ${path}`, await measureRows(p));
            if (path === '/admin/users') {
                await p.locator('[data-invite]').click();
                await p.locator('[data-invite-dialog]').waitFor();
                rows += assessRows(`${theme} ${width} ${path} invite dialog`, await measureRows(p));
            }
            await p.close();
        }
        check(`${theme} ${width} admin rows measured`, rows > 10, `${rows} rows`);
        await ctx.close();

        const guest = await context(theme, { width, height: 1000 });
        for (const path of PUBLIC_FORMS) {
            const p = await open(guest, path);
            assessRows(`${theme} ${width} ${path}`, await measureRows(p));
            await p.close();
        }
        await guest.close();
    }
}

async function clipAround(page, locators) {
    const boxes = (await Promise.all(locators.map((l) => l.boundingBox()))).filter(Boolean);
    const viewport = page.viewportSize();
    const x = Math.max(0, Math.min(...boxes.map((b) => b.x)) - 24);
    const y = Math.max(0, Math.min(...boxes.map((b) => b.y)) - 24);
    const right = Math.min(viewport.width, Math.max(...boxes.map((b) => b.x + b.width)) + 24);
    const bottom = Math.min(viewport.height, Math.max(...boxes.map((b) => b.y + b.height)) + 24);
    return { x, y, width: right - x, height: bottom - y };
}

check('no script errors', errors.length === 0, errors.slice(0, 5).join(' | '));
await browser.close();
setup({ QA_TEARDOWN: '1' });

writeFileSync(`${OUT}/report.json`, JSON.stringify({ failures, results }, null, 2));
console.log(`\n${results.length - failures}/${results.length} checks passed; screenshots in ${OUT}/`);
process.exit(failures ? 1 : 0);
