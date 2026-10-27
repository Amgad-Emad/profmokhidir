/*
 | Arabic (right-to-left) QA, run against the dev site after `npm run build`:
 |
 |   npm run qa:arabic
 |
 | 1. Every public page under /ar (the sections, the tribute form and every detail page in
 |    /sitemaps/ar.xml) in the light and dark themes at 390px and 1440px: status 200,
 |    <html lang="ar" dir="rtl">, the Arabic fonts requested, no horizontal scroll, no console
 |    errors, axe (WCAG 2.2 AA), and an alignment check: visible text in a right-to-left context
 |    must not be aligned to the left, and nothing may lean on physical left alignment. A full-page
 |    screenshot of each is kept in storage/qa/arabic/pages/<width>-<theme>/.
 | 2. The deliverable screenshots in storage/qa/arabic/: home, About, Publications, a commentary,
 |    the chat (an Arabic member) and an admin edit screen with its Arabic tab open (an Arabic
 |    admin), each in light and dark at 1440px.
 | tests/qa/arabic-setup.php prepares the two Arabic accounts and removes them afterwards.
 | Exit code 1 when any check fails. QA_BASE_URL overrides https://profmokhidir.test; QA_ONLY_SHOTS=1
 | takes only the deliverable screenshots (part 2).
 */
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = (process.env.QA_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;
const OUT = 'storage/qa/arabic';
const THEMES = ['light', 'dark'];
const WIDTHS = { 390: { width: 390, height: 844 }, 1440: { width: 1440, height: 900 } };
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];
const SECTIONS = ['', '/about', '/cv', '/publications', '/awards', '/videos', '/pictures', '/teaching', '/commentary', '/community', '/other', '/tributes', '/tributes/new', '/search?q=%D8%A7%D9%84%D8%B2%D8%B1%D8%A7%D8%B9%D8%A9'];

const results = [];
let failures = 0;
const report = (where, check, ok, details = []) => {
    results.push({ where, check, ok, details });
    if (!ok) failures += 1;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${where.padEnd(52)} ${check}${details.length ? ` (${details.length})` : ''}`);
    for (const line of details.slice(0, 8)) console.log(`       - ${line}`);
};

const tinker = (env = {}) => JSON.parse(execFileSync('php', ['artisan', 'tinker', `--execute=require 'tests/qa/arabic-setup.php';`], { env: { ...process.env, ...env }, encoding: 'utf8' }).trim().split('\n').pop());

async function arabicPaths() {
    const xml = await (await fetch(`${BASE}/sitemaps/ar.xml`)).text();
    const details = [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => new URL(m[1]).pathname).filter((path) => !SECTIONS.includes(path.replace(/^\/ar/, '')));
    return [...SECTIONS.map((path) => `/ar${path}`), ...details];
}

async function context(browser, theme, viewport) {
    const ctx = await browser.newContext({ viewport, colorScheme: theme, ignoreHTTPSErrors: true, reducedMotion: 'reduce' });
    await ctx.addCookies([{ name: 'theme', value: theme, domain: HOST, path: '/', sameSite: 'Lax' }]);
    return ctx;
}

async function settle(page) {
    await page.evaluate(async () => {
        for (let y = 0; y <= document.documentElement.scrollHeight; y += window.innerHeight * 0.8) {
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 30));
        }
        window.scrollTo(0, 0);
    });
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(250);
}

/* Visible text blocks laid out right to left whose text is pushed to the left edge. */
const misaligned = () => {
    const out = [];
    for (const el of document.querySelectorAll('h1,h2,h3,h4,h5,h6,p,li,dt,dd,th,td,label,legend,figcaption,blockquote,summary,caption,.t-label')) {
        if (!el.getClientRects().length || el.closest('[dir="ltr"],[lang="en"],code,pre,.katex,svg')) continue;
        const style = getComputedStyle(el);
        if (style.direction !== 'rtl' || style.visibility === 'hidden') continue;
        if (style.textAlign === 'left' || style.textAlign === '-webkit-left') {
            out.push(`${el.tagName.toLowerCase()}${el.className ? '.' + String(el.className).trim().split(/\s+/).slice(0, 2).join('.') : ''} "${el.textContent.trim().slice(0, 40)}" text-align:${style.textAlign}`);
        }
    }
    return [...new Set(out)];
};

async function auditPublic(browser) {
    const paths = await arabicPaths();
    for (const theme of THEMES) {
        for (const [name, viewport] of Object.entries(WIDTHS)) {
            const ctx = await context(browser, theme, viewport);
            const page = await ctx.newPage();
            const errors = [];
            page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
            page.on('pageerror', (e) => errors.push(e.message));
            mkdirSync(`${OUT}/pages/${name}-${theme}`, { recursive: true });

            for (const path of paths) {
                errors.length = 0;
                const where = `${path} ${name}/${theme}`;
                const response = await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
                await settle(page);

                const facts = await page.evaluate(() => ({
                    lang: document.documentElement.lang,
                    dir: document.documentElement.dir,
                    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
                    fonts: [...document.querySelectorAll('link[rel="stylesheet"]')].some((l) => l.href.includes('fonts.googleapis.com') && l.href.includes('Amiri') && l.href.includes('IBM+Plex+Sans+Arabic')),
                }));
                const problems = [];
                if (response?.status() !== 200) problems.push(`status ${response?.status()}`);
                if (facts.lang !== 'ar' || facts.dir !== 'rtl') problems.push(`html lang=${facts.lang} dir=${facts.dir}`);
                if (facts.overflow > 1) problems.push(`horizontal scroll of ${facts.overflow}px`);
                if (!facts.fonts) problems.push('Arabic fonts not requested');
                problems.push(...errors.map((e) => `console: ${e}`));
                report(where, 'page', problems.length === 0, problems);

                report(where, 'alignment', true, []);
                const wrong = await page.evaluate(misaligned);
                results.at(-1).ok = wrong.length === 0;
                results.at(-1).details = wrong;
                if (wrong.length) { failures += 1; console.log(`FAIL ${where.padEnd(52)} alignment (${wrong.length})`); wrong.slice(0, 6).forEach((w) => console.log(`       - ${w}`)); }

                const axe = await new AxeBuilder({ page }).withTags(TAGS).analyze();
                report(where, 'axe', axe.violations.length === 0, axe.violations.flatMap((v) => v.nodes.slice(0, 3).map((n) => `${v.id}: ${n.target.join(' ')}`)));

                const file = (path.replace(/^\/ar\/?/, '').replace(/[/?=%]+/g, '-') || 'home').slice(0, 80);
                await page.screenshot({ path: `${OUT}/pages/${name}-${theme}/${file}.png`, fullPage: true });
            }
            await ctx.close();
        }
    }
}

async function login(ctx, email, password) {
    const page = await ctx.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await Promise.all([page.waitForNavigation(), page.click('form:has(input[name="password"]) button[type="submit"]')]);
    return page;
}

async function deliverables(browser, accounts) {
    const shots = { home: '/ar', about: '/ar/about', publications: '/ar/publications' };
    const commentary = (await arabicPaths()).find((path) => /^\/ar\/commentary\/[^/]+$/.test(path));
    if (commentary) shots.commentary = commentary;

    for (const theme of THEMES) {
        const ctx = await context(browser, theme, WIDTHS[1440]);
        const page = await ctx.newPage();
        for (const [name, path] of Object.entries(shots)) {
            await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
            await settle(page);
            await page.screenshot({ path: `${OUT}/${name}-${theme}.png`, fullPage: name !== 'home' });
        }
        await ctx.close();

        const member = await context(browser, theme, WIDTHS[1440]);
        const memberPage = await login(member, accounts.member, accounts.password);
        const chat = await memberPage.goto(`${BASE}/community/chat`, { waitUntil: 'networkidle' });
        await memberPage.waitForTimeout(1200);
        const chatDir = await memberPage.evaluate(() => document.documentElement.dir);
        report(`/community/chat 1440/${theme}`, 'chat in Arabic', chat?.status() === 200 && chatDir === 'rtl', [`status ${chat?.status()} dir=${chatDir}`]);
        await memberPage.screenshot({ path: `${OUT}/chat-${theme}.png` });
        await member.close();

        const admin = await context(browser, theme, WIDTHS[1440]);
        const adminPage = await login(admin, accounts.admin, accounts.password);
        const edit = await adminPage.goto(`${BASE}/admin/content/commentary/${accounts.commentary}/edit`, { waitUntil: 'networkidle' });
        await adminPage.locator('[data-locale-tab="ar"]').first().click().catch(() => {});
        await adminPage.waitForTimeout(400);
        const adminDir = await adminPage.evaluate(() => document.documentElement.dir);
        report(`/admin commentary edit 1440/${theme}`, 'admin in Arabic', edit?.status() === 200 && adminDir === 'rtl', [`status ${edit?.status()} dir=${adminDir}`]);
        await adminPage.screenshot({ path: `${OUT}/admin-edit-${theme}.png`, fullPage: true });
        await admin.close();
    }
}

mkdirSync(OUT, { recursive: true });
const accounts = tinker();
const browser = await chromium.launch({ channel: 'chrome' });
try {
    if (process.env.QA_ONLY_SHOTS !== '1') await auditPublic(browser);
    await deliverables(browser, accounts);
} finally {
    await browser.close();
    tinker({ QA_TEARDOWN: '1' });
}

writeFileSync(`${OUT}/report.json`, JSON.stringify({ failures, results }, null, 2));
console.log(`\n${results.length} checks, ${failures} failed. Report: ${OUT}/report.json`);
process.exit(failures ? 1 : 0);
