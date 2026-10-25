/*
 | Shared Playwright helpers for the showcase capture: contexts at the report's
 | widths, sign-in, screenshots that wait for fonts and settled motion, and a log
 | of every problem met on the way (console errors, failed requests, steps that
 | could not be shown) which the capture writes to storage/qa/showcase/issues.json.
 */
import { createHmac } from 'node:crypto';
import { existsSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';

export const BASE = (process.env.SHOWCASE_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
export const OUT = process.env.SHOWCASE_OUT ?? 'docs/client-showcase/screenshots';
export const DESKTOP = { width: 1440, height: 900 };
export const MOBILE = { width: 390, height: 844 };

export const issues = [];
export const captured = [];

export function issue(journey, message) {
    issues.push({ journey, message });
    console.log(`  ! ${journey}: ${message}`);
}

mkdirSync(OUT, { recursive: true });

export async function context(browser, { viewport = DESKTOP, theme = 'light', state, journey = 'general' } = {}) {
    const ctx = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport,
        deviceScaleFactor: viewport.width < 600 ? 2 : 1,
        colorScheme: theme,
        reducedMotion: 'reduce',
        storageState: state,
        permissions: ['clipboard-read', 'clipboard-write'],
        isMobile: viewport.width < 600,
        hasTouch: viewport.width < 600,
    });
    ctx.on('page', (page) => watch(page, journey));
    const page = await ctx.newPage();

    return { ctx, page };
}

function watch(page, journey) {
    page.on('console', (message) => {
        if (message.type() === 'error' && !/Failed to load resource.*(403|404|419|422)/.test(message.text())) {
            issue(journey, `console error on ${page.url()}: ${message.text().slice(0, 180)}`);
        }
    });
    page.on('pageerror', (error) => {
        if (!error.message.includes('Transition was aborted')) issue(journey, `page error on ${page.url()}: ${error.message.slice(0, 180)}`);
    });
    page.on('response', (response) => {
        const url = response.url();
        if (url.startsWith(BASE) && response.status() >= 500) issue(journey, `${response.status()} from ${url}`);
    });
}

export async function go(page, path) {
    await page.goto(path.startsWith('http') ? path : `${BASE}${path}`, { waitUntil: 'load' });
    await settle(page);
}

export async function settle(page, ms = 500) {
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    await page.evaluate(() => document.fonts.ready).catch(() => {});
    await page.waitForTimeout(ms);
}

/**
 * Saves one screenshot as <id>.png. `blur` lists selectors whose content is blurred
 * first (secrets such as a two-factor QR code); `fullPage` shoots the whole page,
 * capped so very tall pages are clipped rather than drawn twice.
 */
export async function shot(page, id, { fullPage = false, blur = [], clip, maxHeight = 2600, element } = {}) {
    await settle(page, 300);
    for (const selector of blur) {
        await page.locator(selector).evaluateAll((nodes) => nodes.forEach((node) => { node.style.filter = 'blur(9px)'; })).catch(() => {});
    }
    const path = join(OUT, `${id}.png`);
    if (element) {
        await page.locator(element).first().screenshot({ path });
    } else if (fullPage) {
        const height = await page.evaluate(() => document.documentElement.scrollHeight);
        const width = page.viewportSize().width;
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({ path, fullPage: true, clip: { x: 0, y: 0, width, height: Math.min(height, maxHeight) } });
    } else {
        await page.screenshot({ path, clip });
    }
    captured.push(id);
    console.log(`  ✓ ${id}`);
}

export async function signIn(browser, email, password, journey) {
    const { ctx, page } = await context(browser, { journey });
    await go(page, '/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.startsWith('/login'), { timeout: 15000 }),
        page.click('form:has(input[name="password"]) button[type="submit"]'),
    ]);
    const state = await ctx.storageState();
    const landed = new URL(page.url()).pathname;
    await ctx.close();

    return { state, landed };
}

/** A time-based one-time code (RFC 6238, SHA-1, 30 s, 6 digits) from a base32 secret. */
export function totp(secret) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';
    for (const char of secret.replace(/=+$/, '').toUpperCase()) bits += alphabet.indexOf(char).toString(2).padStart(5, '0');
    const key = Buffer.from(bits.match(/.{8}/g).map((byte) => parseInt(byte, 2)));
    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
    const hmac = createHmac('sha1', key).update(counter).digest();
    const offset = hmac[hmac.length - 1] & 0xf;
    const code = (hmac.readUInt32BE(offset) & 0x7fffffff) % 1000000;

    return String(code).padStart(6, '0');
}

/** Runs one step of a journey; a failure is logged as an issue and the journey carries on. */
export async function step(journey, label, fn) {
    try {
        return await fn();
    } catch (error) {
        issue(journey, `${label}: ${error.message.split('\n')[0]}`);
        return null;
    }
}

export function exists(id) {
    return existsSync(join(OUT, `${id}.png`));
}
