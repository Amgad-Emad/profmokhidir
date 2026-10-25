/*
 | Section 2, part 3: the editor's working session and the administrator's.
 */
import journey from './journey.mjs';

const EDITOR = {
    title: '2.5 Editor: adding content and moderating',
    persona: 'Editor',
    goal: 'Add a newly found 1979 journal article with its PDF, update a line of text on the home page, and approve a waiting tribute.',
    start: 'The "Sign in" link at the top of every page.',
    steps: [
        { text: 'The editor signs in with their email and password.', fig: { id: 'editor-01-sign-in', caption: 'Sign in, desktop' } },
        { text: 'The dashboard greets them, and "Needs your attention" lists only what they can act on: here, tributes waiting for review. Shortcuts start the most common tasks, and "Search or jump to" (Ctrl+K or ⌘K) finds any screen or record.', fig: { id: 'editor-02-dashboard', caption: 'The editor\'s dashboard, desktop' } },
        { text: 'Further down, "Before launch" counts what still needs doing before the site is announced (11 of 21 items are ready today), next to recent activity.', fig: { id: 'editor-03-attention-cards', caption: 'Needs your attention, quick actions and the launch checklist, desktop' } },
        { text: 'They choose "New publication" and fill in the title, type, year and authors. The draft saves itself as soon as they leave a field ("Saved just now").', fig: { id: 'editor-04-publication-form', caption: 'A new publication, desktop' } },
        { text: 'In "Full text" they drop in the PDF. The file name and size appear, with options to open, replace or remove it.', fig: { id: 'editor-05-publication-pdf', caption: 'The PDF attached, desktop' } },
        { text: '"Preview" opens the real Publications page in a new tab, showing the draft where it will sit, with a notice that visitors cannot see it yet.', fig: { id: 'editor-06-publication-preview', caption: 'Previewing the draft on the public page, desktop' } },
        { text: 'They choose "Publish". A message confirms "Publication published. It is on the public site now." and the bar now offers Update and Unpublish.', fig: { id: 'editor-07-publication-published', caption: 'Published, desktop' } },
        { text: 'The article is on the public Publications page, under the 1970s, with its PDF to download.', fig: { id: 'editor-08-publication-live', caption: 'The new publication on the public site, desktop' } },
        { text: 'Next, the wording of the home page. "Pages" lists every public page whose text can be changed, with its status and when it was last saved.', fig: { id: 'editor-09-pages-list', caption: 'Pages, desktop' } },
        { text: 'In the Home editor they change the hero tagline. The draft saves itself, and the preview beside the form shows the new wording (desktop or phone view). Visitors still see the old wording.', fig: { id: 'editor-10-page-editor-preview', caption: 'Editing the home page with the live preview, desktop' } },
        { text: 'They choose "Publish changes", and the new tagline is on the public home page. (For this report the original wording was then put back.)', fig: { id: 'editor-11-page-published', caption: 'The new tagline on the home page, desktop' } },
        { text: 'Finally, "Review tributes" opens the moderation queue, where they approve the waiting tribute (shown in journey 2.2, figures for steps 5 to 7).' },
        { text: 'Editors can work in the dark appearance too.', fig: { id: 'editor-12-dashboard-dark', caption: 'The dashboard in the dark appearance, desktop' } },
    ],
    behind: [
        'Nothing is public until it is published. Drafts, previews and history stay in the dashboard; previews use private links that expire.',
        'Each save is kept in the history, so an earlier version can be looked at and restored. Moving something to the trash can be undone for ten seconds from the message that confirms it.',
        'Publishing clears the cached public pages at once, so visitors see the change on their next visit.',
        'Images need alt text before they can be published, and are resized for every screen size in the background.',
        'Every change is written to the activity log with the editor\'s name.',
    ],
    result: 'A new publication with its PDF is live, the home page wording was changed and published without touching any design, and the tribute queue is clear.',
};

const ADMIN = {
    title: '2.6 Administrator: people, safety and settings',
    persona: 'Administrator',
    goal: 'Invite a new editor, deal with a chat report, update the contact details, secure their own account with two-factor sign-in, and look at the analytics report.',
    start: 'The dashboard, after signing in.',
    steps: [
        { text: 'The administrator\'s dashboard recommends turning on two-factor sign-in and shows three queues: tributes, community applications and chat reports.', fig: { id: 'admin-01-dashboard', caption: 'The administrator\'s dashboard, desktop' } },
        { text: '"Users & roles" lists every staff and community account, with search and filters by role and community status.', fig: { id: 'admin-02-users', caption: 'Users & roles, desktop' } },
        { text: '"Invite a staff member" asks for a name, an email address and a role. No password is set or sent: the new editor chooses their own from the link in the email.', fig: { id: 'admin-03-invite', caption: 'Inviting an editor, desktop' } },
        { text: 'A message confirms the invitation was sent, and the person appears in the list.', fig: { id: 'admin-04-invited', caption: 'Invitation sent, desktop' } },
        { text: 'Community applications are reviewed as shown in journey 2.3. Next, "Chat reports" lists each report with the reason, the reporter and the message itself.', fig: { id: 'admin-08-reports', caption: 'Open chat reports, desktop' } },
        { text: 'For each report they can resolve it, remove the message, or suspend its author (removing and suspending ask for confirmation first). Here they mark it as resolved.', fig: { id: 'admin-09-report-resolved', caption: 'A report resolved, desktop' } },
        { text: '"Site settings" holds the public contact details, the address that receives staff notices and how emails are signed.', fig: { id: 'admin-10-settings', caption: 'Site settings, desktop' } },
        { text: 'They add a public contact phone number and save. The same screen holds the chat upload limits and the visitor analytics switch, which is off.', fig: { id: 'admin-12-analytics-setting', caption: 'Settings saved; chat uploads and visitor analytics, desktop' } },
        { text: 'In "Your account" they choose "Enable two-factor authentication" and confirm their password.', fig: { id: 'admin-13b-confirm-password', caption: 'Confirming the password, desktop' } },
        { text: 'They scan the QR code with an authenticator app (or type the key) and enter the six-digit code. (The code and key are blurred in this report.)', fig: { id: 'admin-14-two-factor-qr', caption: 'Setting up two-factor sign-in, desktop' } },
        { text: 'Two-factor sign-in is on, and a set of single-use recovery codes is shown to keep somewhere safe (blurred here).', fig: { id: 'admin-15-two-factor-on', caption: 'Two-factor sign-in turned on, desktop' } },
        { text: 'Available once the client approves analytics: "Analytics" reports visitors, page views, reading time and returning visitors, where readers come from and which pages hold their attention. It states clearly that recording is switched off. The figures in this screenshot are test data generated on the development computer, not real visitors.', fig: { id: 'admin-16-analytics', caption: 'The analytics report with test data (analytics is switched off), desktop' } },
    ],
    behind: [
        'The invitation link uses the same secure mechanism as "forgot password" and expires; administrators are reminded to set up two-factor sign-in.',
        'The site always keeps at least one active administrator, and nobody can change their own role or deactivate themselves.',
        'Every decision on people, reports and settings is written to the activity log.',
        'Once two-factor sign-in is on, signing in also asks for a code from the authenticator app (or a recovery code).',
        'Analytics, when switched on, uses no cookies and stores no IP addresses or personal data; visitors are counted once a day and page views are deleted after 30 days.',
    ],
    result: 'A new editor is invited, the report is handled, the contact details are current, the administrator\'s account is protected by two-factor sign-in, and the analytics report is ready for when the client approves it.',
};

export default function journeysStaff() {
    return [...journey(EDITOR), ...journey(ADMIN)];
}
