/*
 | Administrator: the dashboard, invite an editor, approve the community applicant,
 | handle the member's chat report, change a site setting, turn on two-factor
 | authentication (QR code, setup key and recovery codes blurred) and open the
 | analytics report. Then the approved applicant's first chat visit and the
 | rejected and suspended applicants' status pages.
 */
import { context, go, issue, settle, shot, signIn, step, totp } from '../lib/browser.mjs';
import { APPLICANT, firstVisit, standings } from './community.mjs';

const J = 'admin';

async function toast(page, text) {
    return page.locator('[data-toast]', { hasText: text }).last().waitFor({ timeout: 15000 }).then(() => true).catch(() => false);
}

export default async function admin(browser, shared) {
    const session = await signIn(browser, 'admin@example.com', shared.password, J);
    if (session.landed !== '/admin') issue(J, `the administrator landed on ${session.landed} after signing in`);
    const { ctx, page } = await context(browser, { state: session.state, journey: J });

    await step(J, 'dashboard', async () => {
        await go(page, '/admin');
        await shot(page, 'admin-01-dashboard');
    });

    await step(J, 'invite', async () => {
        await go(page, '/admin/users');
        await shot(page, 'admin-02-users');
        await page.locator('[data-invite]').first().click();
        const dialog = page.locator('[data-invite-dialog]');
        await dialog.waitFor();
        await dialog.getByLabel('Name').fill('Nadia Osman');
        await dialog.getByLabel('Email').fill('showcase.editor@example.com');
        await dialog.locator('select').selectOption('editor');
        await shot(page, 'admin-03-invite');
        await dialog.locator('[data-send-invite]').click();
        if (!(await toast(page, 'Nadia'))) issue(J, 'no confirmation after sending the invitation');
        await settle(page, 800);
        await shot(page, 'admin-04-invited');
    });

    await step(J, 'applications', async () => {
        await go(page, '/admin/community');
        const row = page.locator('[data-user]', { hasText: APPLICANT.name }).first();
        if (!(await row.count())) throw new Error('the applicant is not in the applications queue');
        await shot(page, 'admin-05-applications');
        await row.locator('[data-approve]').click();
        const dialog = page.locator('[data-decision-dialog]');
        await dialog.waitFor({ timeout: 8000 });
        await shot(page, 'admin-06-approve-dialog');
        await dialog.locator('[data-confirm]').click();
        if (!(await toast(page, 'pprov'))) issue(J, 'no confirmation after approving the applicant');
        await shot(page, 'admin-07-approved');
    });

    await step(J, 'reports', async () => {
        await go(page, '/admin/community/reports');
        await shot(page, 'admin-08-reports');
        const report = page.locator('[data-report]', { hasText: 'unpublished thesis' }).first();
        if (!(await report.count())) throw new Error('the member\'s report is not in the open reports');
        await report.locator('[data-resolve]').click();
        await settle(page, 1200);
        await shot(page, 'admin-09-report-resolved');
    });

    await step(J, 'settings', async () => {
        await go(page, '/admin/settings');
        await shot(page, 'admin-10-settings');
        await page.getByLabel('Public contact phone').fill('+249 183 000 000');
        await page.locator('[data-save]').first().click();
        if (!(await toast(page, 'aved'))) issue(J, 'no confirmation after saving the settings');
        await shot(page, 'admin-11-settings-saved');
        await page.locator('#analytics').scrollIntoViewIfNeeded();
        await page.evaluate(() => window.scrollBy(0, -120));
        await shot(page, 'admin-12-analytics-setting');
    });

    await step(J, 'two-factor', async () => {
        await go(page, '/account#two-factor');
        await page.locator('#two-factor').scrollIntoViewIfNeeded();
        await shot(page, 'admin-13-two-factor-off');
        await page.locator('form[action$="/user/two-factor-authentication"] button[type="submit"]').first().click();
        await page.waitForLoadState('load');
        if (page.url().includes('confirm-password')) {
            await page.fill('input[name="password"]', shared.password);
            await shot(page, 'admin-13b-confirm-password');
            await Promise.all([page.waitForURL((url) => !url.pathname.includes('confirm-password')), page.locator('form[action$="/user/confirm-password"] button[type="submit"]').click()]);
            await settle(page);
            await page.locator('form[action$="/user/two-factor-authentication"] button[type="submit"]').first().click().catch(() => {});
            await page.waitForLoadState('load');
        }
        await settle(page);
        const key = (await page.locator('[data-setup-key]').innerText()).replace(/\s+/g, '');
        await page.locator('#two-factor').scrollIntoViewIfNeeded();
        await shot(page, 'admin-14-two-factor-qr', { blur: ['.qr-code', '[data-setup-key]'] });
        const code = totp(key);
        const confirm = page.locator('form[action$="/user/confirmed-two-factor-authentication"]');
        await confirm.locator('input:not([type=hidden])').first().click();
        await page.keyboard.type(code, { delay: 40 });
        await Promise.all([page.waitForNavigation({ timeout: 15000 }).catch(() => {}), confirm.locator('button[type="submit"]').click()]);
        await page.waitForLoadState('load');
        await settle(page);
        if (!(await page.locator('[data-two-factor="confirmed"]').count())) issue(J, 'two-factor authentication did not switch on');
        await page.locator('[data-recovery-codes]').evaluate((node) => { node.open = true; }).catch(() => {});
        await page.locator('#two-factor').scrollIntoViewIfNeeded();
        await shot(page, 'admin-15-two-factor-on', { blur: ['[data-recovery-codes]'] });
    });

    await step(J, 'analytics', async () => {
        await go(page, '/admin/analytics');
        await shot(page, 'admin-16-analytics');
    });

    await ctx.close();

    await firstVisit(browser, shared);
    await standings(browser, shared);
}
