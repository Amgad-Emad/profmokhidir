/*
 | Accessibility audit: axe-core (WCAG 2.0/2.1/2.2 A and AA) through Playwright
 | against every public page, the auth screens, the main admin screens and the
 | community chat, at a phone width and a desktop width. Beyond axe it checks
 | the heading outline, a keyboard walk (every stop visible, with a focus
 | outline, and not hidden under the fixed header) and reduced-motion rendering.
 |
 |   npm run a11y
 |
 | Environment: A11Y_BASE_URL (default https://profmokhidir.test). Signed-in
 | screens need A11Y_EDITOR_EMAIL / A11Y_EDITOR_PASSWORD, A11Y_ADMIN_EMAIL /
 | A11Y_ADMIN_PASSWORD / A11Y_ADMIN_RECOVERY (a recovery code, since admins must
 | pass the two-factor challenge) and A11Y_MEMBER_EMAIL / A11Y_MEMBER_PASSWORD.
 | Without them those screens are skipped. Public detail pages are read from
 | /sitemap.xml. A11Y_PUBLIC=0 skips the public pages and A11Y_ROLES=admin,editor
 | limits the signed-in sessions. Exit code 1 when anything fails.
 */
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';

const BASE = (process.env.A11Y_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const OUT = process.env.A11Y_REPORT ?? 'storage/app/a11y/axe-report.json';
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];
const VIEWPORTS = { mobile: { width: 360, height: 740 }, desktop: { width: 1366, height: 900 } };
const MAX_TABS = 400;

const PUBLIC_PAGES = [
    '/', '/about', '/cv', '/publications', '/awards', '/videos', '/pictures', '/teaching',
    '/commentary', '/community', '/other', '/tributes', '/tributes/new', '/tributes/thank-you',
    '/login', '/forgot-password', '/this-page-does-not-exist',
];
const EDITOR_PAGES = [
    '/admin', '/admin/content/profile', '/admin/content/cv', '/admin/content/cv/create',
    '/admin/content/publications', '/admin/content/publications/create', '/admin/content/awards',
    '/admin/content/commentary', '/admin/content/commentary/create', '/admin/content/teaching',
    '/admin/content/other', '/admin/media/library', '/admin/media/albums', '/admin/media/albums/create',
    '/admin/media/videos', '/admin/media/videos/create', '/admin/tributes', '/account',
];
const ADMIN_PAGES = ['/admin/community', '/admin/community/members', '/admin/community/reports', '/admin/settings/users'];
const MEMBER_PAGES = ['/community/chat'];

const results = [];
let failures = 0;

function report(page, viewport, check, ok, details = []) {
    results.push({ page, viewport, check, ok, details });
    if (!ok) failures += 1;
    const mark = ok ? 'ok ' : 'FAIL';
    console.log(`${mark} ${viewport.padEnd(7)} ${page.padEnd(44)} ${check}${details.length ? ' (' + details.length + ')' : ''}`);
    for (const line of details) console.log('       - ' + line);
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

async function login(context, email, password, recovery) {
    const page = await context.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
    if (page.url().includes('two-factor-challenge') && recovery) {
        await page.click('text=Use a recovery code').catch(() => {});
        await page.fill('input[name="recovery_code"]', recovery);
        await Promise.all([page.waitForNavigation(), page.click('form:has(input[name="recovery_code"]) button[type="submit"]')]);
    }
    const ok = !page.url().includes('/login') && !page.url().includes('two-factor-challenge');
    await page.close();
    return ok;
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
    await page.waitForTimeout(200);
}

async function runAxe(page, path, viewport, label = 'axe') {
    await settle(page);
    const axe = await new AxeBuilder({ page }).withTags(TAGS).analyze();
    const details = axe.violations.flatMap((v) =>
        v.nodes.slice(0, 5).map((n) => `${v.id} [${v.impact}] ${n.target.join(' ')}: ${n.failureSummary?.split('\n')[1]?.trim() ?? v.help}`),
    );
    report(path, viewport, label, axe.violations.length === 0, details);
}

async function checkHeadings(page, path, viewport) {
    const levels = await page.$$eval('h1, h2, h3, h4, h5, h6', (els) =>
        els.filter((el) => el.getClientRects().length > 0 || el.classList.contains('sr-only')).map((el) => Number(el.tagName[1])),
    );
    const problems = [];
    if (levels.filter((l) => l === 1).length !== 1) problems.push(`expected one h1, found ${levels.filter((l) => l === 1).length}`);
    for (let i = 1; i < levels.length; i += 1) {
        if (levels[i] > levels[i - 1] + 1) problems.push(`h${levels[i - 1]} followed by h${levels[i]} (position ${i})`);
    }
    report(path, viewport, 'heading order', problems.length === 0, problems);
}

async function checkKeyboard(page, path, viewport) {
    const problems = [];
    const headerBottom = await page.evaluate(() => {
        const header = document.querySelector('header.site-header, .admin-shell > header, .site-header');
        return header ? header.getBoundingClientRect().bottom : 0;
    });
    const expected = await page.$$eval(
        'a[href], button, input, select, textarea, summary, [tabindex]:not([tabindex="-1"])',
        (els) => {
            // A radio group is one Tab stop, and controls inside a closed <details> (other than its summary) are skipped by Tab.
            const groups = new Set();
            return els.filter((el) => {
                const style = getComputedStyle(el);
                const closed = el.closest('details:not([open])');
                if (closed && el.tagName !== 'SUMMARY') return false;
                if (el.type === 'radio') {
                    const group = `${el.form ? 'f' : ''}:${el.name}`;
                    if (groups.has(group)) return false;
                    groups.add(group);
                }
                return !el.disabled && style.display !== 'none' && style.visibility !== 'hidden' && el.closest('[x-cloak]') === null && el.getClientRects().length > 0;
            }).length;
        },
    );
    await page.addStyleTag({ content: '*, html { scroll-behavior: auto !important; }' });
    await page.evaluate(() => { window.__a11ySeen = new Set(); });
    const seen = new Set();
    let outside = 0;
    for (let i = 0; i < MAX_TABS; i += 1) {
        await page.keyboard.press('Tab');
        const info = await page.evaluate((headerBottom) => {
            const el = document.activeElement;
            if (!el || el === document.body) return { body: true };
            if (window.__a11ySeen.has(el)) return { repeat: true };
            window.__a11ySeen.add(el);
            const rect = el.getBoundingClientRect();
            const hit = document.elementFromPoint(Math.min(window.innerWidth - 1, Math.max(0, rect.left + rect.width / 2)), Math.min(window.innerHeight - 1, Math.max(0, rect.top + rect.height / 2)));
            const covered = hit !== null && hit !== el && !el.contains(hit) && !hit.contains(el);
            const style = getComputedStyle(el);
            const outline = style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
            const shadow = style.boxShadow !== 'none';
            const text = (el.getAttribute('aria-label') || el.textContent || el.getAttribute('name') || '').trim().replace(/\s+/g, ' ').slice(0, 40);
            const key = `${el.tagName}:${el.id || ''}:${text}:${Math.round(rect.top + window.scrollY)}`;
            const inFixed = el.closest('.site-header, .admin-sidebar, .admin-shell > header, .chat-header, [role="dialog"]') !== null;
            return {
                key,
                label: `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''} "${text}"`,
                visibleFocus: outline || shadow,
                obscured: !inFixed && rect.height > 0 && rect.top < headerBottom - 1 && covered,
                offscreen: rect.bottom < 0 || rect.top > window.innerHeight,
                width: rect.width,
                height: rect.height,
            };
        }, headerBottom);
        if (info.body) { outside += 1; if (outside > 1) break; continue; }
        if (info.repeat) break;
        seen.add(info.key);
        if (!info.visibleFocus) problems.push(`no visible focus: ${info.label}`);
        if (info.obscured) problems.push(`focus under fixed header: ${info.label}`);
        if (info.offscreen) problems.push(`focused element scrolled out of view: ${info.label}`);
    }
    if (seen.size === 0) problems.push('nothing received focus');
    if (seen.size < expected * 0.5) problems.push(`only ${seen.size} of ~${expected} controls reached by Tab`);
    report(path, viewport, 'keyboard walk', problems.length === 0, problems);
}

async function checkReducedMotion(browser, path) {
    const context = await browser.newContext({ ignoreHTTPSErrors: true, reducedMotion: 'reduce', viewport: VIEWPORTS.mobile });
    const page = await context.newPage();
    await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
    const hidden = await page.$$eval('[data-rise], [data-rise-group] > *', (els) =>
        els.filter((el) => Number(getComputedStyle(el).opacity) < 1).map((el) => el.tagName.toLowerCase() + ' "' + (el.textContent || '').trim().slice(0, 30) + '"'),
    );
    report(path, 'reduced', 'content visible with reduced motion', hidden.length === 0, hidden);
    await context.close();
}

async function auditPage(browser, path, viewportName, session = null) {
    const viewport = VIEWPORTS[viewportName];
    const context = session ?? (await browser.newContext({ ignoreHTTPSErrors: true, viewport }));
    const page = await context.newPage();
    await page.setViewportSize(viewport);
    const response = await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    const status = response?.status() ?? 0;
    const okStatus = path.includes('does-not-exist') ? status === 404 : status === 200;
    report(path, viewportName, `status ${status}`, okStatus);
    if (status < 500) {
        await runAxe(page, path, viewportName);
        await checkHeadings(page, path, viewportName);
        await checkKeyboard(page, path, viewportName);
        if (viewportName === 'mobile' && (await page.$('button[aria-controls="site-menu"]'))) {
            await page.click('button[aria-controls="site-menu"]');
            await page.waitForTimeout(300);
            await runAxe(page, path, viewportName, 'axe (menu open)');
            await page.keyboard.press('Escape');
        }
    }
    await page.close();
    if (!session) await context.close();
}

const browser = await chromium.launch({ channel: process.env.A11Y_CHANNEL ?? 'chrome', headless: true });

try {
    const publicPages = process.env.A11Y_PUBLIC === '0' ? [] : [...PUBLIC_PAGES, ...(await sitemapUrls())];
    for (const path of publicPages) {
        for (const viewportName of Object.keys(VIEWPORTS)) await auditPage(browser, path, viewportName);
        if (!path.startsWith('/login') && !path.startsWith('/forgot')) await checkReducedMotion(browser, path);
    }

    const sessions = [
        ['editor', EDITOR_PAGES, process.env.A11Y_EDITOR_EMAIL, process.env.A11Y_EDITOR_PASSWORD, null],
        ['admin', ADMIN_PAGES, process.env.A11Y_ADMIN_EMAIL, process.env.A11Y_ADMIN_PASSWORD, process.env.A11Y_ADMIN_RECOVERY],
        ['member', MEMBER_PAGES, process.env.A11Y_MEMBER_EMAIL, process.env.A11Y_MEMBER_PASSWORD, null],
    ];
    const roles = (process.env.A11Y_ROLES ?? 'editor,admin,member').split(',');
    for (const [role, pages, email, password, recovery] of sessions) {
        if (!roles.includes(role)) continue;
        if (!email || !password) { console.log(`skip ${role} screens (no credentials)`); continue; }
        const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: VIEWPORTS.desktop });
        if (!(await login(context, email, password, recovery))) { report('/login', 'desktop', `${role} login`, false, ['could not sign in']); await context.close(); continue; }
        for (const path of pages) {
            for (const viewportName of Object.keys(VIEWPORTS)) await auditPage(browser, path, viewportName, context);
        }
        await context.close();
    }
} finally {
    await browser.close();
}

mkdirSync(dirname(OUT), { recursive: true });
writeFileSync(OUT, JSON.stringify({ base: BASE, generated: new Date().toISOString(), failures, results }, null, 2));
console.log(`\n${results.length} checks, ${failures} failing. Report: ${OUT}`);
process.exit(failures === 0 ? 0 : 1);
