/*
 | Section 1: one page per persona. Every "can" and "cannot" was checked against
 | the role and permission table (app/Constants/Permissions), the route
 | middleware, the policies and the community status rules.
 */
import { facts, figure, h1, h2, p } from '../lib/docx-kit.mjs';

const PERSONAS = [
    {
        title: 'Public visitor',
        intro: 'A student, a researcher or a member of the public who finds the site through a search engine, a shared link or word of mouth.',
        facts: [
            ['Who they are', 'Anyone on the internet. No account and no sign-in are needed.'],
            ['What they want', 'To learn about Professor Khidir: his story, his academic record, his publications, his photographs and lectures, and what others remember of him.'],
            ['What they can do', [
                'Read every published section: About, CV and Achievements, Publications, Awards, Videos, Pictures, Teaching Materials, Commentary Corner, Other Information and Tributes.',
                'Filter publications by type, year range and topic, and copy a ready-made citation (APA, MLA, Chicago or BibTeX).',
                'Open photographs in a full-screen viewer with captions, and play videos.',
                'Search the whole site, and switch between the light and dark appearance.',
                'Download public publication files and public teaching materials.',
                'Leave a tribute, or apply to join the Scientific Community.',
            ]],
            ['What they cannot do', [
                'See drafts or anything not yet published.',
                'Download the CV: it is shown on the page only, by design.',
                'Comment on commentary pieces (there are no visitor comments).',
                'See the community chat or download teaching files marked for members only.',
            ]],
            ['What gives them access', 'Nothing: every published page is public. Unpublished content stays inside the dashboard.'],
        ],
        fig: { id: 'visitor-01-home', caption: 'The home page a visitor arrives on, desktop' },
    },
    {
        title: 'Tribute author',
        intro: 'A former student, colleague, family member or friend who wants to leave a few words of remembrance.',
        facts: [
            ['Who they are', 'A visitor who chooses "Leave a tribute". No account is needed.'],
            ['What they want', 'To share a memory, a lesson or a moment, and see it on the Tributes page among others.'],
            ['What they can do', [
                'Write a tribute with their name, how they knew him (student, colleague, family, friend or other), where they are writing from, and their message.',
                'Optionally leave an email address so the team can reach them about the tribute.',
                'Preview the tribute exactly as it will appear, change it, then agree and send it.',
                'Once approved, share a card image of their tribute.',
            ]],
            ['What they cannot do', [
                'Publish directly: every tribute waits for an editor or administrator to approve it.',
                'Edit a tribute after sending it (an editor can correct it on their behalf).',
            ]],
            ['What gives them access', 'Nothing: the tribute form is public. Their email address is shown only to moderators, never on the site. A hidden spam trap and a sending limit keep automated submissions out.'],
        ],
        fig: { id: 'tribute-03-preview', caption: 'A tribute author previews their tribute before sending it, desktop' },
    },
    {
        title: 'Community applicant',
        subtitle: 'Pending, rejected or suspended',
        intro: 'A researcher, practitioner or former student who has asked to join the Scientific Community and is waiting for a decision, or who has been turned away.',
        facts: [
            ['Who they are', 'Someone who filled in the application on the Scientific Community page. They have an account with the Applicant role, which carries no permissions.'],
            ['What they want', 'To be admitted to the private chat with former students and colleagues.'],
            ['What they can do', [
                'Apply with their name, email, password, affiliation, academic field and why they would like to join, after agreeing to the conduct rules and the privacy note.',
                'Confirm their email address from the link they are sent.',
                'Sign in and see their status: "Pending review", "Rejected" or "Suspended", each with a plain explanation.',
                'Manage their account: name, email, password, appearance and two-factor sign-in.',
            ]],
            ['What they cannot do', [
                'Open the chat, read any message or send one. This is checked by the server on every request, not only hidden from the menu.',
                'A rejected applicant cannot apply again from the page; they are asked to write to the administrator. A suspended member keeps their account but loses chat access until an administrator reinstates them.',
            ]],
            ['What gives them access', 'Chat access comes only from an administrator\'s approval, which gives the Member role. Pending, rejected and suspended accounts have no permissions at all.'],
        ],
        fig: { id: 'applicant-04-pending', caption: 'An applicant whose application is pending sees their status, desktop' },
    },
    {
        title: 'Approved community member',
        intro: 'A colleague or former student whose application an administrator has approved.',
        facts: [
            ['Who they are', 'A signed-in member with the Member role.'],
            ['What they want', 'To keep in touch, share work in progress, and continue the conversations Professor Khidir began.'],
            ['What they can do', [
                'Read and send messages in the private Scientific Community room, reply to a message, and format text (including mathematical notation).',
                'Attach up to five photographs or documents per message (pictures, PDF, Word, Excel, PowerPoint, CSV and ZIP). Location data is removed from photos automatically.',
                'Edit or delete their own messages, report someone else\'s message to the administrators, search the conversation and see who else is a member.',
                'Download teaching materials marked for members only.',
                'Manage their account and sign out.',
            ]],
            ['What they cannot do', [
                'Pin messages, remove other people\'s messages or suspend members: these are administrator tasks.',
                'Open the dashboard.',
            ]],
            ['What gives them access', 'The Member role, which carries exactly one permission: "Use the community chat". It is checked on every chat request.'],
        ],
        fig: { id: 'member-01-chat', caption: 'The private Scientific Community room as a member sees it, desktop' },
    },
    {
        title: 'Editor',
        intro: 'A member of the content team who keeps the site up to date and reviews tributes.',
        facts: [
            ['Who they are', 'A staff member invited by an administrator, with the Editor role.'],
            ['What they want', 'To add and correct content quickly, see what needs attention, and keep the tribute wall moderated.'],
            ['What they can do', [
                'Create, edit, reorder, publish, unpublish and move to the trash every type of content: profile, CV entries, publications, awards, commentary, teaching materials, other information, home-page quotes, videos and photo albums.',
                'Edit the wording of every public page in the Pages editor, with a live side-by-side preview, drafts and history.',
                'Upload files and photos, add alt text and captions in the media library.',
                'Approve, edit, feature or decline tributes.',
                'Open the visitor analytics report (once analytics is switched on).',
            ]],
            ['What they cannot do', [
                'Review community applications or manage members.',
                'Handle chat reports.',
                'Invite or change staff accounts, or change site settings.',
            ]],
            ['What gives them access', 'The Editor role: "Open the dashboard", "Manage content", "Moderate tributes" and "View analytics".'],
        ],
        fig: { id: 'editor-02-dashboard', caption: 'The editor\'s dashboard after signing in, desktop' },
    },
    {
        title: 'Administrator',
        intro: 'The site owner or a trusted delegate responsible for people, settings and the community.',
        facts: [
            ['Who they are', 'A staff member with the Administrator role. The first administrator is created during installation; others are invited.'],
            ['What they want', 'To decide who may join the community, keep the chat safe, manage the staff team and look after the site\'s settings.'],
            ['What they can do', [
                'Everything an editor can do.',
                'Approve or reject community applications (with an optional note in the email), and suspend, reinstate or remove members.',
                'Handle chat reports: resolve them, remove the message or suspend its author.',
                'Invite editors and administrators, change roles, deactivate and reactivate staff accounts, and resend invitations.',
                'Change site settings: public contact details, where staff notices are sent, the email sender, chat upload limits and visitor analytics.',
                'Turn on two-factor sign-in for their own account (recommended for every administrator).',
            ]],
            ['What they cannot do', [
                'Change their own role or deactivate themselves.',
                'Demote or deactivate the last active administrator: the site always keeps one.',
            ]],
            ['What gives them access', 'The Administrator role, which holds all nine permissions. Every administrative action is written to the activity log.'],
        ],
        fig: { id: 'admin-01-dashboard', caption: 'The administrator\'s dashboard with the two-factor reminder and three queues, desktop' },
    },
];

export default function personas() {
    const out = [h1('1. Who uses the site')];
    out.push(p('Six kinds of people use profmokhidir.com. Each has a page below: who they are, what they want, what they can and cannot do, and which role gives them that access. Access is enforced by the server on every request, not only by hiding buttons.'));
    out.push(p('Roles in brief: visitors and tribute authors need no account; applicants have an account but no permissions; members may use the chat; editors manage content and tributes; administrators manage everything, including people and settings.'));

    PERSONAS.forEach((persona, index) => {
        out.push(h2(`1.${index + 1} ${persona.title}`, { pageBreakBefore: true }));
        if (persona.subtitle) out.push(p(persona.subtitle, { spacing: { after: 60 } }));
        out.push(p(persona.intro));
        out.push(facts(persona.facts));
        out.push(...figure({ ...persona.fig, width: 560 }));
    });

    return out;
}
