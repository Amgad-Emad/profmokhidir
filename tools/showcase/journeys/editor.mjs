/*
 | Editor: sign in, read the dashboard's "Needs your attention" cards, add a
 | publication with a PDF (preview, publish, see it on the site), change a line of
 | page text in the Pages editor (draft, preview, publish, then put the original
 | wording back), and approve the tribute the author left, then see it on the wall.
 */
import { context, go, issue, settle, shot, signIn, step } from '../lib/browser.mjs';
import { TRIBUTE } from './tributes.mjs';

const J = 'editor';
const NEW_TAGLINE = 'A teacher of the land, and of everyone who came to learn from it.';

async function toast(page, text) {
    return page.locator('[data-toast]', { hasText: text }).last().waitFor({ timeout: 15000 }).then(() => true).catch(() => false);
}

export default async function editor(browser, shared) {
    // The sign-in screen itself, before any account is used.
    const login = await context(browser, { journey: J });
    await step(J, 'sign-in screen', async () => {
        await go(login.page, '/login');
        await login.page.fill('input[name="email"]', 'editor@example.com');
        await shot(login.page, 'editor-01-sign-in');
    });
    await login.ctx.close();

    const session = await signIn(browser, 'editor@example.com', shared.password, J);
    if (session.landed !== '/admin') issue(J, `the editor landed on ${session.landed} after signing in, not the dashboard`);
    const { ctx, page } = await context(browser, { state: session.state, journey: J });

    await step(J, 'dashboard', async () => {
        await go(page, '/admin');
        await shot(page, 'editor-02-dashboard');
        const attention = page.locator('#attention-heading');
        if (await attention.count()) {
            await page.evaluate(() => window.scrollTo(0, document.querySelector('#attention-heading').getBoundingClientRect().top + window.scrollY - 110));
            await shot(page, 'editor-03-attention-cards');
        } else {
            issue(J, 'no "Needs your attention" section on the editor dashboard');
        }
    });

    await step(J, 'publication', async () => {
        await go(page, '/admin/content/publications/create');
        await page.fill('#form-title', shared.publicationTitle);
        await page.selectOption('#form-type', 'journal_article').catch(() => issue(J, 'could not choose the publication type'));
        await page.fill('#form-year', '1979');
        await page.fill('#form-authors', 'Khidir, M. O.; Hassan, A. M.');
        await page.fill('#form-venue-publisher', 'Sudan Journal of Agricultural Research');
        await page.locator('#form-venue-publisher').blur();
        await page.waitForFunction(() => document.querySelector('[data-save-note]')?.textContent?.startsWith('Saved'), null, { timeout: 15000 })
            .catch(() => issue(J, 'the new publication did not autosave'));
        await shot(page, 'editor-04-publication-form');

        await page.locator('input[type=file]').first().setInputFiles(shared.samples.pdf);
        if (!(await toast(page, 'attached'))) issue(J, 'no confirmation after attaching the PDF');
        await page.locator('[data-current-file]').first().scrollIntoViewIfNeeded().catch(() => {});
        await page.evaluate(() => window.scrollBy(0, -200));
        await shot(page, 'editor-05-publication-pdf');

        const [preview] = await Promise.all([ctx.waitForEvent('page'), page.click('[data-preview]')]);
        await preview.waitForURL((url) => url.href !== 'about:blank', { timeout: 15000 }).catch(() => {});
        await settle(preview, 800);
        await preview.locator('main').getByText(shared.publicationTitle).first().scrollIntoViewIfNeeded().catch(() => {});
        await preview.evaluate(() => window.scrollBy(0, -240));
        await shot(preview, 'editor-06-publication-preview');
        await preview.close();

        await page.locator('[data-publish]').first().click();
        if (!(await toast(page, 'published'))) issue(J, 'no "published" confirmation for the publication');
        await shot(page, 'editor-07-publication-published');

        await go(page, '/publications?type=journal_article');
        const entry = page.locator('main').getByText(shared.publicationTitle).first();
        if (await entry.count()) {
            await entry.scrollIntoViewIfNeeded();
            await page.evaluate(() => window.scrollBy(0, -260));
            await shot(page, 'editor-08-publication-live');
        } else {
            issue(J, 'the published publication is not on the public Publications page');
        }
    });

    await step(J, 'pages', async () => {
        await go(page, '/admin/content/pages');
        await shot(page, 'editor-09-pages-list');
        await go(page, '/admin/content/pages/home/edit');
        const frame = page.frameLocator('[data-page-preview] iframe');
        await frame.locator('#hero-title').waitFor({ timeout: 15000 }).catch(() => issue(J, 'the page preview did not load'));
        const tagline = page.getByLabel('Hero tagline', { exact: true });
        const original = await tagline.inputValue();
        await tagline.fill(NEW_TAGLINE);
        await tagline.blur();
        await page.locator('[data-save-note]', { hasText: 'Saved' }).waitFor({ timeout: 15000 }).catch(() => issue(J, 'the page draft did not autosave'));
        await frame.locator('.hero', { hasText: NEW_TAGLINE }).waitFor({ timeout: 15000 }).catch(() => issue(J, 'the preview did not show the draft wording'));
        await shot(page, 'editor-10-page-editor-preview');

        const early = await context(browser, { journey: J });
        await go(early.page, '/');
        if ((await early.page.content()).includes(NEW_TAGLINE)) issue(J, 'the draft wording was public before it was published');
        await early.ctx.close();

        await page.locator('[data-publish]').first().click();
        if (!(await toast(page, 'ublished'))) issue(J, 'no confirmation after publishing the page');
        const visitor = await context(browser, { journey: J });
        await go(visitor.page, '/');
        if (!(await visitor.page.content()).includes(NEW_TAGLINE)) issue(J, 'the published wording is not on the home page');
        await shot(visitor.page, 'editor-11-page-published');
        await visitor.ctx.close();

        // Put the original wording back so the site is left as it was.
        await tagline.fill(original);
        await tagline.blur();
        await page.locator('[data-save-note]', { hasText: 'Saved' }).waitFor({ timeout: 15000 }).catch(() => {});
        await page.locator('[data-publish]').first().click();
        await toast(page, 'ublished');
    });

    await step(J, 'tributes', async () => {
        await go(page, '/admin/tributes');
        const card = page.locator('[data-tribute]', { hasText: TRIBUTE.name }).first();
        if (!(await card.count())) {
            issue(J, 'the new tribute is not in the moderation queue');
            return;
        }
        await card.scrollIntoViewIfNeeded();
        await page.evaluate(() => window.scrollBy(0, -140));
        await shot(page, 'tribute-05-moderation');
        await card.locator('[data-approve]').click();
        if (!(await toast(page, 'approved'))) issue(J, 'no confirmation after approving the tribute');
        await shot(page, 'tribute-06-approved');

        const visitor = await context(browser, { journey: J });
        await go(visitor.page, '/tributes');
        const entry = visitor.page.getByText('look before we measured').first();
        if (await entry.count()) {
            await entry.scrollIntoViewIfNeeded();
            await visitor.page.evaluate(() => window.scrollBy(0, -260));
            await shot(visitor.page, 'tribute-07-wall-after-approval');
        } else {
            issue(J, 'the approved tribute is not on the public wall');
        }
        await visitor.ctx.close();
    });

    await step(J, 'dark dashboard', async () => {
        const dark = await context(browser, { state: session.state, theme: 'dark', journey: J });
        await go(dark.page, '/admin');
        await dark.page.locator('[data-theme-toggle-button]:visible').first().click();
        await dark.page.waitForTimeout(800);
        await shot(dark.page, 'editor-12-dashboard-dark');
        await dark.ctx.close();
    });

    shared.editorState = session.state;
    await ctx.close();
}
