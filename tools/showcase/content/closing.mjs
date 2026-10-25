/*
 | Sections 5 to 7 and the appendix: the measured quality numbers (from
 | quality.json, written after each measuring run), the decisions and content
 | needed from the client, the next steps before launch, and the technical stack.
 */
import { readFileSync } from 'node:fs';
import { bullets, h1, h2, h3, label, p, table } from '../lib/docx-kit.mjs';

const quality = JSON.parse(readFileSync(new URL('../quality.json', import.meta.url), 'utf8'));

export function qualitySummary() {
    const q = quality;
    const out = [
        h1('5. Quality summary'),
        p(`These numbers were measured on ${q.measuredLabel} on the development copy of the site, with the same placeholder content shown in this report. They will be measured again on the live server with the real content before launch.`),
        h2('5.1 Automated tests'),
        p(`${q.tests.total.toLocaleString('en-GB')} automated tests check the site\'s rules, from "a pending applicant cannot read the chat" to "there is no CV download". ${q.tests.passed.toLocaleString('en-GB')} passed, ${q.tests.failed} failed and ${q.tests.skipped} was skipped (${q.tests.skippedReason}). Together they make ${q.tests.assertions.toLocaleString('en-GB')} individual checks.`),
        h2('5.2 Accessibility'),
        p(q.axe.summary),
        table(['What was checked', 'Result'], q.axe.rows, [0.62, 0.38]),
        h2('5.3 Speed on a mobile phone'),
        p(q.lighthouse.summary),
        table(['Page', 'Performance', 'Accessibility', 'Best practices', 'Search engines'], q.lighthouse.rows, [0.28, 0.18, 0.18, 0.18, 0.18]),
        h2('5.4 Whole-site check'),
        p(q.smoke.summary),
        table(['What was checked', 'Result'], q.smoke.rows, [0.62, 0.38]),
    ];
    if (q.notes?.length) {
        out.push(label('Notes'));
        out.push(...bullets(q.notes));
    }

    return out;
}

const DECISIONS = [
    ['Section names and menu', 'The ten sections are grouped under Life, Work, Archive, Community and Tributes. Please confirm the names, in particular "Commentary Corner", "Scientific Community" and "Other Information", and what belongs in Other Information.'],
    ['Contact details', 'The public email address, phone number and postal address for the About page, and the address that should receive staff notices.'],
    ['Community eligibility', 'Who may join, the application fields, and the wording of "Who may join", the conduct rules and the privacy note.'],
    ['Community moderation', 'Who reviews applications and reports, how quickly, and the rules for suspending and removing members. How long account and chat data are kept.'],
    ['Analytics', 'Whether to switch on the built-in, cookieless visitor analytics. If yes, the privacy page is published at the same moment.'],
    ['Hosting and domain', 'Where the site will run, who manages the domain, backups, uptime and ongoing support.'],
    ['Email provider', 'The service that sends account, approval and notification emails, and the sender name and address (for example a profmokhidir.com address).'],
    ['Videos and rights', 'Where videos are hosted (YouTube, Vimeo or on the site) and confirmation of the rights to every photo, video and publication file.'],
    ['Members-only teaching files', 'Which teaching materials are public and which are for community members only.'],
];

const CONTENT = [
    'Biography, title and affiliation, areas of expertise and key facts for the About page.',
    'A professional portrait, and the portrait for the home page.',
    'The CV: education, appointments, leadership, projects and achievements, with dates.',
    'The publication list (type, year, authors, venue, DOI or citation) and the PDF files that may be shared.',
    'Awards with organisation, year and, where available, a description and source link.',
    'Videos with title, date, event and place, and their captions or transcripts.',
    'Photographs with a caption, place, year, credit and a short description for people who cannot see them, grouped into albums.',
    'Teaching materials with course, topic, type, version or date, and the files.',
    'Commentary pieces with title, date, summary and full text or link, and their topics.',
    'Items for Other Information, the home-page quotes and the taglines.',
    'Any tributes already collected that should appear at launch.',
];

export function decisions() {
    return [
        h1('6. Decisions and content needed from the client'),
        p('All text and images on the site today are placeholders. Every sample record starts with "[Placeholder]" and every photograph is a grey stand-in, so nothing can be mistaken for approved copy. The sample tributes, community members and chat messages are invented and will be removed before launch.'),
        h2('6.1 Decisions'),
        table(['Topic', 'What we need'], DECISIONS, [0.26, 0.74]),
        h2('6.2 Content'),
        p('Content can be sent in any form (documents, spreadsheets, photos); the editors will enter it through the dashboard. For each item:'),
        ...bullets(CONTENT),
    ];
}

const NEXT = [
    ['Decisions', 'Receive the decisions in section 6, in particular hosting, the email provider and the community rules.'],
    ['Server', 'Set up the production server and database, the domain and its secure (https) certificate, the background worker for emails and image sizes, and nightly backups.'],
    ['Email', 'Connect the chosen email provider and send test messages for sign-up, password reset, approvals and staff notices.'],
    ['First administrator', 'Create the first administrator account; they set their password from the emailed link and turn on two-factor sign-in, then invite the editors.'],
    ['Real content', 'Remove the placeholder records and enter the real content. The dashboard\'s "Before launch" checklist (11 of 21 ready today) tracks the portrait, the contact details, the sample text left in each section and every section having something published.'],
    ['Content review', 'The client reviews every page on desktop and phone and approves the wording, photos and videos.'],
    ['Final checks', 'Repeat the accessibility, speed and whole-site checks in section 5 on the live server with the real content, and check the site in current versions of Chrome, Safari, Firefox and Edge.'],
    ['Analytics and privacy', 'If approved, switch on analytics, which also publishes the privacy page.'],
    ['Launch', 'Point the domain at the new server, submit the sitemap to search engines, and announce the site.'],
    ['After launch', 'Agree the support arrangement: who moderates tributes and the community, and who to contact for changes.'],
];

export function nextSteps() {
    return [
        h1('7. Next steps before launch'),
        table(['Step', 'What happens'], NEXT, [0.22, 0.78]),
    ];
}

export function appendix() {
    return [
        h1('Appendix: technical summary'),
        p('For the client\'s technical adviser or hosting provider.'),
        h3('Platform'),
        ...bullets([
            'Laravel 13 (PHP 8.3 or newer), MySQL 8.',
            'Public pages: server-rendered Blade templates with Tailwind CSS 4 and small Alpine.js scripts. The dashboard and the chat use Livewire 4.',
            'Accounts and two-factor sign-in: Laravel Fortify. Roles and permissions: spatie/laravel-permission. Files and image sizes: spatie/laravel-medialibrary.',
            'Chat updates by polling every five seconds; it can be switched to WebSockets (Laravel Reverb) without changing any access rules.',
        ]),
        h3('Hosting requirements'),
        ...bullets([
            'A Linux server or managed Laravel host with PHP 8.3+, MySQL 8, Composer and Node.js for the build.',
            'A long-running queue worker (emails and image sizes) and the scheduler (nightly analytics roll-up, if analytics is on).',
            'An email provider (SMTP or API) with a sender address on the site\'s domain.',
            'HTTPS, and daily database and file backups.',
        ]),
        h3('Quality tooling'),
        ...bullets([
            'PHPUnit feature and unit tests against a separate test database.',
            'Playwright with axe-core for accessibility, Lighthouse for speed, and a whole-site smoke test at three screen widths in both appearances.',
            'This report is generated from fresh screenshots by a script, so it can be produced again at any stage.',
        ]),
    ];
}
