/*
 | Community applicant: apply, confirm the email address from the link in the
 | (logged) email, and see the pending status. Approved member: read and send
 | messages, reply, attach a photo and a PDF, report a message, open the account
 | page and sign out. `firstVisit` runs after the administrator has approved the
 | applicant; `standings` shows the rejected and suspended applicants.
 */
import { readFileSync } from 'node:fs';
import { MOBILE, context, go, issue, settle, shot, signIn, step } from '../lib/browser.mjs';

const J = 'community';
export const APPLICANT = { name: 'Dr Samira Elamin', email: 'showcase.applicant@example.com' };

function verificationLink(log, email) {
    const text = readFileSync(log, 'utf8');
    const at = text.lastIndexOf(email);
    const links = [...text.slice(Math.max(0, at)).matchAll(/https:\/\/profmokhidir\.test\/email\/verify\/[^\s"'<>)\]]+/g)];

    return links.length ? links[0][0].replace(/&amp;/g, '&').replace(/=3D/g, '=') : null;
}

async function room(page) {
    await go(page, '/community/chat');
    await page.waitForSelector('[data-chat-room]', { timeout: 15000 });
    await settle(page, 800);
}

/** A small on-brand chart to attach, so the photo in the chat is not a blank placeholder. */
async function sampleChart(browser) {
    const path = 'storage/qa/showcase/samples/rainfall-1988.png';
    const months = [['May', 8], ['Jun', 31], ['Jul', 96], ['Aug', 142], ['Sep', 74], ['Oct', 19]];
    const page = await browser.newPage({ viewport: { width: 1200, height: 800 } });
    await page.setContent(`<body style="margin:0;background:#F3F1EA;font-family:Georgia,serif;color:#13221C">
        <div style="padding:64px 80px"><p style="letter-spacing:.24em;font:500 18px Arial;color:#6F5A34;text-transform:uppercase">Wad Medani · 1988</p>
        <h1 style="font-weight:300;font-size:56px;margin:8px 0 48px">Monthly rainfall (mm)</h1>
        <div style="display:flex;align-items:flex-end;gap:40px;height:420px;border-bottom:2px solid #A8864F">
        ${months.map(([m, v]) => `<div style="flex:1;text-align:center"><div style="font:20px Arial;margin-bottom:8px">${v}</div><div style="height:${v * 2.6}px;background:#2F4A3E"></div><div style="font:20px Arial;margin-top:12px;position:relative;top:44px">${m}</div></div>`).join('')}
        </div></div></body>`);
    await page.screenshot({ path });
    await page.close();

    return path;
}

export default async function community(browser, shared) {
    const { ctx, page } = await context(browser, { journey: J });

    await step(J, 'apply', async () => {
        await go(page, '/community');
        await page.fill('input[name="name"]', APPLICANT.name);
        await page.fill('input[name="email"]', APPLICANT.email);
        await page.fill('input[name="password"]', shared.password);
        await page.fill('input[name="password_confirmation"]', shared.password);
        await page.fill('input[name="affiliation"]', 'University of Gezira');
        await page.fill('input[name="academic_field"]', 'Soil science');
        await page.fill('textarea[name="reason_for_joining"]', 'I studied irrigation management under Professor Khidir and would like to share field data with his former students.');
        await page.locator('input[name="conduct_agreed"]').check({ force: true });
        await page.locator('input[name="privacy_agreed"]').check({ force: true });
        await page.evaluate(() => window.scrollTo(0, document.querySelector('input[name="affiliation"]').getBoundingClientRect().top + window.scrollY - 230));
        await shot(page, 'applicant-01-form');

        await Promise.all([page.waitForURL('**/community**'), page.locator('form[data-apply-form] button[type="submit"]').click()]);
        await settle(page);
        await shot(page, 'applicant-02-submitted');

        await go(page, '/community/chat');
        await shot(page, 'applicant-03-verify-email');
    });

    await step(J, 'verify', async () => {
        const link = verificationLink(shared.log, APPLICANT.email);
        if (!link) throw new Error('no verification link found in the mail log');
        await go(page, link);
        await go(page, '/community');
        await shot(page, 'applicant-04-pending');
        await go(page, '/community/chat');
        const blocked = !(await page.locator('[data-chat-room]').count());
        if (!blocked) issue(J, 'a pending applicant could open the chat');
        await shot(page, 'applicant-05-chat-refused');
        shared.applicantState = await ctx.storageState();
    });
    await ctx.close();

    const session = await signIn(browser, 'member1@example.com', shared.password, J);
    const member = await context(browser, { state: session.state, journey: J });
    const m = member.page;

    await step(J, 'chat', async () => {
        await room(m);
        await shot(m, 'member-01-chat');
    });

    await step(J, 'reply', async () => {
        const question = m.locator('.chat-message', { hasText: 'rainfall series for the 1988' }).last();
        await question.hover();
        await question.locator('[data-reply]').click();
        await m.locator('[data-reply-preview]').waitFor({ timeout: 5000 });
        await m.locator('#chat-draft').click();
        await m.keyboard.type('Yes, I have it. I will upload the series and my notes from the field visit now.', { delay: 8 });
        await shot(m, 'member-02-reply');
        await m.keyboard.press('Enter');
        await m.waitForFunction(() => !document.querySelector('.chat-message.is-sending'), null, { timeout: 20000 }).catch(() => {});
        await m.waitForTimeout(1200);
        await shot(m, 'member-03-reply-sent');
    });

    await step(J, 'attach', async () => {
        await m.setInputFiles('[data-file-input]', [await sampleChart(browser), shared.samples.pdf]);
        await m.locator('[data-tray-item]').first().waitFor({ timeout: 15000 });
        await m.waitForTimeout(2500);
        const alt = m.locator('[data-tray-item] input[type="text"], [data-tray-item] textarea').first();
        if (await alt.count()) await alt.fill('Bar chart of monthly rainfall at Wad Medani in 1988');
        await m.locator('#chat-draft').click();
        await m.keyboard.type('The 1988 rainfall series, and my notes from Thursday\'s field visit.', { delay: 8 });
        await shot(m, 'member-04-attachments');
        await m.keyboard.press('Enter');
        await m.waitForFunction(() => !document.querySelector('.chat-message.is-sending'), null, { timeout: 30000 }).catch(() => {});
        await m.waitForTimeout(5000);
        await shot(m, 'member-05-attachments-sent');
    });

    await step(J, 'report', async () => {
        const post = m.locator('.chat-message', { hasText: 'unpublished thesis' }).last();
        await post.hover();
        await post.locator('[data-report]').click();
        await m.locator('#report-reason').waitFor();
        await m.locator('#report-reason').fill('This shares a colleague\'s unpublished thesis data without his permission.');
        await shot(m, 'member-06-report');
        await m.locator('[data-report-submit]').click();
        await m.waitForTimeout(1500);
        await shot(m, 'member-07-reported');
    });

    await step(J, 'account and sign out', async () => {
        await go(m, '/account');
        await shot(m, 'member-08-account');
        await m.locator('[data-account-sign-out] button[type="submit"]').first().click();
        await m.waitForLoadState('load');
        await settle(m);
        await shot(m, 'member-09-signed-out');
    });
    await member.ctx.close();

    // The chat at phone width and in the dark theme, as the second member.
    const second = await signIn(browser, 'member2@example.com', shared.password, J);
    await step(J, 'chat views', async () => {
        const phone = await context(browser, { state: second.state, viewport: MOBILE, journey: J });
        await room(phone.page);
        await shot(phone.page, 'mobile-05-chat');
        await phone.ctx.close();
        const dark = await context(browser, { state: second.state, theme: 'dark', journey: J });
        await room(dark.page);
        await dark.page.locator('[data-theme-toggle-button]:visible').first().click();
        await dark.page.waitForTimeout(800);
        await shot(dark.page, 'member-10-chat-dark');
        await dark.ctx.close();
    });
}

/** The applicant's first visit once an administrator has approved them. */
export async function firstVisit(browser, shared) {
    if (!shared.applicantState) return issue(J, 'no applicant session to show the first chat visit');
    const { ctx, page } = await context(browser, { state: shared.applicantState, journey: J });
    await step(J, 'first visit', async () => {
        await go(page, '/community');
        await shot(page, 'applicant-06-approved');
        await room(page);
        await shot(page, 'applicant-07-first-chat');
    });
    await ctx.close();
}

/** What a rejected and a suspended applicant see. */
export async function standings(browser, shared) {
    for (const [email, id] of [['showcase.rejected@example.com', 'applicant-08-rejected'], ['showcase.suspended@example.com', 'applicant-09-suspended']]) {
        await step(J, id, async () => {
            const session = await signIn(browser, email, shared.password, J);
            const { ctx, page } = await context(browser, { state: session.state, journey: J });
            await go(page, '/community');
            await shot(page, id);
            await ctx.close();
        });
    }
}
