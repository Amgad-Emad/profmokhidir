/*
 | Public visitor: the home page, his story, publications (filter and cite), the
 | CV, the archive (albums, lightbox, videos), a commentary, search, the other
 | sections, the light/dark switch, and a few phone-width views.
 */
import { MOBILE, context, go, issue, settle, shot, step } from '../lib/browser.mjs';

const J = 'visitor';

export default async function visitor(browser) {
    const { ctx, page } = await context(browser, { journey: J });

    await step(J, 'home', async () => {
        await go(page, '/');
        await shot(page, 'visitor-01-home');
        await page.evaluate(() => window.scrollTo(0, window.innerHeight * 1.1));
        await shot(page, 'visitor-02-home-story');
    });

    await step(J, 'about', async () => {
        await go(page, '/about');
        await shot(page, 'visitor-03-about');
    });

    await step(J, 'publications', async () => {
        await go(page, '/publications');
        await shot(page, 'visitor-04-publications');
        await go(page, '/publications?type=journal_article');
        const filters = page.locator('#catalogue-filters');
        if (await filters.count()) await filters.scrollIntoViewIfNeeded();
        await page.evaluate(() => window.scrollTo(0, Math.max(0, (document.querySelector('#catalogue-filters')?.getBoundingClientRect().top ?? 0) + window.scrollY - 120)));
        await shot(page, 'visitor-05-publications-filtered');

        const cite = page.locator('details.cite').first();
        await cite.scrollIntoViewIfNeeded();
        await cite.locator('summary').click();
        await page.waitForTimeout(300);
        await cite.locator('button.filter-pill', { hasText: 'Copy' }).click();
        await page.waitForTimeout(300);
        const copied = await page.evaluate(() => navigator.clipboard.readText()).catch(() => '');
        if (!copied) issue(J, 'Copy citation did not put any text on the clipboard');
        await page.evaluate(() => {
            const open = document.querySelector('details.cite[open]');
            window.scrollBy(0, open.getBoundingClientRect().top - 260);
        });
        await shot(page, 'visitor-06-citation-copied');
    });

    await step(J, 'cv', async () => {
        await go(page, '/cv');
        await shot(page, 'visitor-07-cv');
    });

    await step(J, 'pictures', async () => {
        await go(page, '/pictures');
        await shot(page, 'visitor-08-pictures');
        await go(page, '/pictures/in-the-field');
        await shot(page, 'visitor-09-album');
        await page.locator('[data-lightbox-item]').first().click();
        await page.waitForTimeout(1200);
        await shot(page, 'visitor-10-lightbox');
        await page.keyboard.press('Escape');
    });

    await step(J, 'videos', async () => {
        await go(page, '/videos');
        await shot(page, 'visitor-11-videos');
        const first = page.locator('main a[href*="/videos/"]').first();
        await Promise.all([page.waitForURL('**/videos/*'), first.click()]);
        await settle(page);
        await shot(page, 'visitor-12-video');
    });

    await step(J, 'commentary', async () => {
        await go(page, '/commentary');
        await shot(page, 'visitor-13-commentary');
        await go(page, '/commentary/placeholder-what-the-gezira-taught-us-about-patience');
        await shot(page, 'visitor-14-commentary-piece');
    });

    await step(J, 'search', async () => {
        await go(page, '/');
        await page.click('#site-search-opener');
        await page.waitForTimeout(800);
        const input = page.locator('#site-search input').first();
        if (await input.isVisible().catch(() => false)) {
            await input.type('soil', { delay: 60 });
            await page.waitForTimeout(1200);
            await shot(page, 'visitor-15-search-dialog');
            await page.keyboard.press('Escape');
        } else {
            issue(J, 'the search button did not open the search dialog');
        }
        await go(page, '/search?q=soil');
        await shot(page, 'visitor-16-search-results');
    });

    await step(J, 'other sections', async () => {
        for (const [id, path] of [['visitor-17-awards', '/awards'], ['visitor-18-teaching', '/teaching'], ['visitor-19-other', '/other'], ['visitor-20-community', '/community']]) {
            await go(page, path);
            await shot(page, id);
        }
    });

    await step(J, 'theme switch', async () => {
        await go(page, '/');
        await page.locator('#theme-switch-masthead').click();
        await page.waitForTimeout(900);
        const theme = await page.evaluate(() => document.documentElement.dataset.theme);
        if (theme !== 'dark') issue(J, `the theme switch left the page in "${theme}"`);
        await shot(page, 'visitor-21-home-dark');
        await go(page, '/publications');
        await shot(page, 'visitor-22-publications-dark');
        await page.locator('#theme-switch-masthead').click();
        await page.waitForTimeout(600);
    });

    await ctx.close();

    const phone = await context(browser, { viewport: MOBILE, journey: J });
    await step(J, 'mobile', async () => {
        await go(phone.page, '/');
        await shot(phone.page, 'mobile-01-home');
        await phone.page.locator('.site-menu-toggle:visible').first().click();
        await phone.page.waitForTimeout(900);
        await shot(phone.page, 'mobile-02-menu');
        await go(phone.page, '/publications');
        await shot(phone.page, 'mobile-03-publications', { fullPage: true, maxHeight: 1500 });
        await go(phone.page, '/pictures/in-the-field');
        await shot(phone.page, 'mobile-04-album', { fullPage: true, maxHeight: 1500 });
    });
    await phone.ctx.close();
}
