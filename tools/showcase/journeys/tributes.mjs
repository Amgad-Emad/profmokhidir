/*
 | Tribute author: open the wall, write a tribute, preview it, agree and send it,
 | and see the thank-you page. The editor journey approves it and shoots the wall
 | with the new tribute on it.
 */
import { context, go, issue, settle, shot, step } from '../lib/browser.mjs';

const J = 'tributes';

export const TRIBUTE = {
    name: 'Dr Amal Hassan',
    location: 'Wad Medani, Sudan',
    message: 'Professor Khidir taught my first soil science class in 1984. He walked us to the edge of the Gezira canals and asked us to look before we measured. I still begin every field visit with his question: what is the land telling you today?',
};

export default async function tributes(browser, shared) {
    const { ctx, page } = await context(browser, { journey: J });

    await step(J, 'wall', async () => {
        await go(page, '/tributes');
        await shot(page, 'tribute-01-wall');
    });

    await step(J, 'write', async () => {
        await go(page, '/tributes/new');
        await page.fill('input[name="name"]', TRIBUTE.name);
        await page.selectOption('select[name="relationship"]', 'student');
        await page.fill('input[name="location"]', TRIBUTE.location);
        await page.fill('textarea[name="message"]', TRIBUTE.message);
        await page.fill('input[name="email"]', shared.tributeEmail);
        await page.locator('textarea[name="message"]').scrollIntoViewIfNeeded();
        await page.evaluate(() => window.scrollBy(0, -160));
        await shot(page, 'tribute-02-form');

        await Promise.all([page.waitForURL('**/tributes/preview**'), page.locator('form[action$="/tributes/preview"] button[type="submit"]').first().click()]);
        await settle(page);
        if (!(await page.content()).includes('look before we measured')) issue(J, 'the preview does not show the message');
        await shot(page, 'tribute-03-preview');

        await page.locator('input[name="consent"]').check({ force: true });
        await Promise.all([page.waitForURL('**/tributes/thank-you**'), page.locator('form[action$="/tributes"] button[type="submit"]').first().click()]);
        await settle(page);
        await shot(page, 'tribute-04-thank-you');

        await go(page, '/tributes');
        if ((await page.content()).includes('look before we measured')) issue(J, 'the tribute is public before it was approved');
    });

    await ctx.close();
}
