/*
 | Section 3 (feature overview by area) and section 4 (traceability to the client
 | brief's ten sections and the developer specification's FR-01 to FR-12).
 */
import { h1, h2, p, table } from '../lib/docx-kit.mjs';

const DONE = { text: 'Done', status: true };
const PLACEHOLDER = { text: 'Done with placeholder content', status: true };
const WAITING = { text: 'Waiting on client decision', status: true };

const FEATURES = [
    ['Public site', [
        ['Home page', 'His name, portrait, tagline, chapters of his story and the way into every section.', PLACEHOLDER],
        ['Navigation', 'Header menu grouping the sections under Life, Work, Archive, Community and Tributes; full-screen phone menu; footer; breadcrumbs; current section marked.', DONE],
        ['About Prof. Mokhidir', 'Biography, portrait, title and affiliation, expertise, key facts and links to related sections.', PLACEHOLDER],
        ['CV and Achievements', 'On-page CV by section (education, appointments, leadership, projects, achievements) with a "last updated" date. No download.', PLACEHOLDER],
        ['Publications', 'Selected works, catalogue by decade, filters by type, years and topic, citations in four styles, DOI, links and files.', PLACEHOLDER],
        ['Awards', 'Name, organisation, year, optional description and source link.', PLACEHOLDER],
        ['Teaching Materials', 'Grouped by course, filterable by topic and type; description, version or date, file type; public or members-only files.', PLACEHOLDER],
        ['Commentary Corner', 'List with topic filters and an essay page for each piece; no visitor comments.', PLACEHOLDER],
        ['Other Information', 'Structured items in categories (announcements, archive, in the press), each with a date and either a full text or a link.', PLACEHOLDER],
        ['Site search', 'Search across every section from the header or the "/" key, with instant results and a full results page.', DONE],
        ['Light and dark appearance', 'One-click switch, remembered on the device and on the account.', DONE],
        ['Empty states and error pages', 'Every list has a designed "nothing here yet" message; designed pages for not found, no access and maintenance.', DONE],
        ['Search engines and sharing', 'A title and description for every page, a sitemap, search-engine rules, and preview cards when a page is shared.', DONE],
    ]],
    ['Tributes', [
        ['Leave a tribute', 'Two-step form (write, then preview and agree) with a character counter and spam protection.', DONE],
        ['Moderation', 'Pending, approved and declined lists; approve, edit, decline, feature; several at once.', DONE],
        ['Book of remembrance', 'Public page with a featured tribute and filters by relationship; email addresses never shown.', PLACEHOLDER],
        ['Share card', 'An image card of an approved tribute for sharing.', DONE],
        ['Staff notice', 'Email to the notification address when a tribute is waiting.', WAITING],
    ]],
    ['Media archive', [
        ['Photo albums', 'Albums with a cover, captioned photos with alt text, place, year and credit; a view by decade.', PLACEHOLDER],
        ['Photo viewer', 'Full-screen viewer with captions, keyboard and swipe navigation.', DONE],
        ['Photo treatment', 'Portraits in warm monochrome and archive photos in the evergreen-to-ivory duotone, applied by the site.', DONE],
        ['Videos', 'YouTube, Vimeo, an uploaded file or a plain link, with thumbnail, captions, event, place and date.', PLACEHOLDER],
        ['Media library', 'Every uploaded file in one place, where it is used, and a filter for images missing alt text.', DONE],
        ['Responsive images', 'Each photo is prepared in three sizes and loaded only when needed.', DONE],
    ]],
    ['Scientific Community', [
        ['Application', 'Name, email, password, affiliation, academic field and reason, with agreement to the conduct rules and privacy note.', DONE],
        ['Email confirmation', 'The applicant confirms their address before anything else.', DONE],
        ['Approval', 'Administrators approve or reject with an optional note; the decision is emailed. Suspend, reinstate and remove members.', DONE],
        ['Status pages', 'Clear pages for pending, approved, rejected, suspended and removed accounts.', DONE],
        ['Private chat room', 'One room for approved members; replies, formatting and mathematics, edit and delete own messages, search, pinned messages, member list; updates every few seconds.', DONE],
        ['Attachments', 'Up to five pictures or documents per message; private storage; photo location data removed.', DONE],
        ['Reports and moderation', 'Members report messages; administrators resolve, remove the message or suspend the author.', DONE],
        ['Eligibility, conduct rules and privacy note', 'The wording on the Community page and in the room.', WAITING],
    ]],
    ['Admin and CMS', [
        ['Dashboard', 'Greeting, "Needs your attention", quick actions, launch checklist and recent activity.', DONE],
        ['Content editors', 'One editor per content type with autosave, preview, publish, unpublish, display order, history and a trash with undo.', DONE],
        ['Pages editor', 'Change the wording of every public page with a live desktop or phone preview, drafts and history.', DONE],
        ['Users and roles', 'Invite editors and administrators by email; change roles; deactivate and reactivate; last-administrator protection.', DONE],
        ['Site settings', 'Contact details, staff notification address, email sender, chat uploads and analytics.', DONE],
        ['Search and shortcuts', '"Search or jump to" (Ctrl+K or ⌘K) and keyboard shortcuts for frequent actions.', DONE],
        ['Activity log', 'Who did what and when, for content, people and settings.', DONE],
        ['Visitor analytics', 'First-party, cookieless report of visitors, pages, reading time and referrers. Built and switched off.', WAITING],
    ]],
    ['Accessibility and performance', [
        ['WCAG 2.2 AA target', 'Semantic pages, keyboard use, visible focus, alt text, descriptive links; checked automatically (see section 5).', DONE],
        ['Reduced motion', 'Visitors who ask their device for less motion see every page without animation.', DONE],
        ['Responsive layout', 'Phone, tablet and desktop; no sideways scrolling at 360 px.', DONE],
        ['Speed', 'Light public pages, cached content, lazy-loaded images; mobile scores in section 5.', DONE],
    ]],
    ['Security and privacy', [
        ['Roles and permissions', 'Every page and action checks the person\'s permission on the server.', DONE],
        ['Two-factor sign-in', 'Available to every account and recommended to administrators on the dashboard.', DONE],
        ['Safe accounts', 'Invitations with set-password links, password reset, sign-in limits, deactivated accounts signed out.', DONE],
        ['Spam and abuse limits', 'Spam traps and rate limits on tributes, applications, sign-in, chat messages and uploads.', DONE],
        ['Private files', 'Members-only teaching files and chat attachments stored privately and served through expiring links.', DONE],
        ['No CV download', 'There is no CV file or download link; an automated test fails if one is ever added.', DONE],
        ['Privacy by default', 'No tracking until analytics is approved; a privacy page appears only when it is switched on.', WAITING],
    ]],
];

const SECTIONS = [
    ['1. About Prof. Mokhidir', 'About page', 'Content › About & profile; Pages › About', PLACEHOLDER],
    ['2. CV and Achievements', 'CV and Achievements page (view only)', 'Content › CV entries', PLACEHOLDER],
    ['3. Publications', 'Publications page (filters, citations, files)', 'Content › Publications', PLACEHOLDER],
    ['4. Awards', 'Awards page', 'Content › Awards', PLACEHOLDER],
    ['5. Videos', 'Videos page and a page per video', 'Media › Videos', PLACEHOLDER],
    ['6. Pictures', 'Pictures page, album pages, photo viewer', 'Media › Pictures & albums; Media library', PLACEHOLDER],
    ['7. Teaching Materials', 'Teaching Materials page and downloads', 'Content › Teaching materials', PLACEHOLDER],
    ['8. Commentary Corner', 'Commentary list and essay pages', 'Content › Commentary', PLACEHOLDER],
    ['9. Scientific Community', 'Community page, application, private chat room', 'Community › Applications, Members, Chat reports', DONE],
    ['10. Other Information', 'Other Information page and item pages', 'Content › Other information', PLACEHOLDER],
];

const REQUIREMENTS = [
    ['FR-01 Global navigation', 'Header menu on every page, full-screen phone menu, footer, breadcrumbs; current section underlined; usable by keyboard.', 'Every page', DONE],
    ['FR-02 Content fields', 'Empty fields are left out; the same heading, date, link and label patterns in every section.', 'Every section', PLACEHOLDER],
    ['FR-03 View-only CV', 'CV shown on the page; no file, no download, guarded by an automated test.', 'CV and Achievements', DONE],
    ['FR-04 Publications', 'Types, newest-first catalogue by decade, filters by type, years and topic, selected works.', 'Publications; Content › Publications', DONE],
    ['FR-05 Media', 'Photos and videos with captions and metadata; YouTube, Vimeo, upload or link.', 'Videos, Pictures; Media screens', WAITING],
    ['FR-06 Teaching resources', 'Title, type, summary, version or date, file type and download or link.', 'Teaching Materials', PLACEHOLDER],
    ['FR-07 Commentary', 'List with topic filters and a detail page; no visitor comments.', 'Commentary Corner', DONE],
    ['FR-08 CMS / editor workflow', 'Editors create, update, reorder, publish and remove content; roles for editors and administrators.', 'Dashboard', DONE],
    ['FR-09 External destinations', 'Clear link labels; downloads show file type and size; external links name the website they lead to.', 'Every section', DONE],
    ['FR-10 SEO basics', 'Title and description on every page; sitemap; private pages kept out of search engines.', 'Every page', DONE],
    ['FR-11 Signup and approval', 'Application form; accounts start pending; administrators approve or reject.', 'Community; Community › Applications', WAITING],
    ['FR-12 Private scientific chat', 'Only approved, signed-in members can read or send; pending, rejected and signed-out people cannot.', 'Community chat', DONE],
];

const ADDITIONS = [
    ['Tributes', 'Visitors leave tributes that appear after approval, with a book of remembrance and share cards.', 'Tributes pages; Tributes › Review tributes', DONE],
    ['Dark appearance', 'A full dark version of the public site, the chat and the dashboard.', 'Every page', DONE],
    ['Visitor analytics', 'Cookieless first-party analytics with a report for staff; off until approved.', 'Audience › Analytics; Site settings', WAITING],
    ['Pages editor (CMS)', 'Editors change the wording of every public page, with preview, drafts and history.', 'Content › Pages', DONE],
];

export function features() {
    const out = [h1('3. Feature overview')];
    out.push(p('Every feature that exists in the site today, grouped by area. "Done" means built and tested. "Done with placeholder content" means the feature works but shows sample text and images until the real content arrives. "Waiting on client decision" means it is built and needs an approval or a choice before launch.'));
    FEATURES.forEach(([area, rows], index) => {
        out.push(h2(`3.${index + 1} ${area}`));
        out.push(table(['Feature', 'What it does', 'Status'], rows, [0.24, 0.53, 0.23]));
    });

    return out;
}

export function traceability() {
    return [
        h1('4. Traceability to the client\'s documents'),
        p('How each part of the client brief and each requirement of the developer specification (version 1.0, 17 October 2026) is delivered. "Admin screen" names where in the dashboard the content is managed.'),
        h2('4.1 The ten sections of the brief'),
        table(['Section', 'Public page', 'Admin screen', 'Status'], SECTIONS, [0.22, 0.3, 0.27, 0.21]),
        h2('4.2 Shared functional requirements'),
        table(['Requirement', 'How it is delivered', 'Where', 'Status'], REQUIREMENTS, [0.22, 0.39, 0.18, 0.21]),
        p('FR-05 waits on the video hosting and rights rules; FR-11 on the client\'s approval of the signup fields and eligibility. Both work today as described.', { spacing: { before: 160 } }),
        h2('4.3 Additions beyond the original brief'),
        table(['Addition', 'What it does', 'Where', 'Status'], ADDITIONS, [0.2, 0.4, 0.19, 0.21]),
    ];
}
