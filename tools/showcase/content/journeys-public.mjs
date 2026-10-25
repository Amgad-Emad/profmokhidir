/*
 | Section 2, part 1: the visitor's tour of the site and the tribute author's
 | journey, from writing a tribute to seeing it on the wall after approval.
 */
import { h1, p } from '../lib/docx-kit.mjs';
import journey from './journey.mjs';

const VISITOR = {
    title: '2.1 Visitor: exploring the site',
    persona: 'Public visitor (a student researching Sudanese agriculture)',
    goal: 'Learn who Professor Khidir was, find his publications and cite one, look through the archive, and read his commentary.',
    start: 'The home page, profmokhidir.com, reached from a search engine.',
    steps: [
        { text: 'The visitor arrives on the home page: his name, a tagline, a portrait and two clear actions, "Read his story" and "Leave a tribute". The menu groups the sections into Life, Work, Archive, Community and Tributes.', fig: { id: 'visitor-01-home', caption: 'Home page, desktop' } },
        { text: 'Scrolling down, the home page continues the story in chapters and leads on to the About page, the publications and the tributes.', fig: { id: 'visitor-02-home-story', caption: 'Home page chapters further down, desktop' } },
        { text: 'They choose "Read his story" and read the About page: biography, title and affiliation, areas of expertise and key facts.', fig: { id: 'visitor-03-about', caption: 'About Prof. Mokhidir, desktop' } },
        { text: 'Under Work, they open Publications. Selected works sit at the top, then the full catalogue grouped by decade.', fig: { id: 'visitor-04-publications', caption: 'Publications, desktop' } },
        { text: 'They narrow the list with the "Refine" panel: type (here "Journal article"), a year range and a topic.', fig: { id: 'visitor-05-publications-filtered', caption: 'Publications filtered to journal articles, desktop' } },
        { text: 'Under a publication they open "Cite", choose a style (APA, MLA, Chicago or BibTeX) and press "Copy". The button confirms "Copied" and the citation is on their clipboard.', fig: { id: 'visitor-06-citation-copied', caption: 'Copying an APA citation, desktop' } },
        { text: 'They read the CV and Achievements page, arranged by section (education, appointments, leadership, projects, achievements) with a "last updated" date. The CV is for reading on the page; there is no download.', fig: { id: 'visitor-07-cv', caption: 'CV and Achievements, desktop' } },
        { text: 'Under Archive, Pictures shows the photo albums, with a second view that arranges every photograph by decade.', fig: { id: 'visitor-08-pictures', caption: 'Pictures: the albums, desktop' } },
        { text: 'Inside an album each photograph carries its caption, place and year.', fig: { id: 'visitor-09-album', caption: 'An album, desktop' } },
        { text: 'Selecting a photograph opens the full-screen viewer with its caption and credit; the arrow keys move between photos and Escape closes it.', fig: { id: 'visitor-10-lightbox', caption: 'The photo viewer, desktop' } },
        { text: 'Videos lists lectures, interviews and events with their dates and places. Each opens on its own page with an accessible player.', fig: { id: 'visitor-11-videos', caption: 'Videos, desktop' } },
        { text: 'The video page shows the title, the event, the place and the date above the player.', fig: { id: 'visitor-12-video', caption: 'A video page, desktop' } },
        { text: 'In the Commentary Corner the visitor filters essays by topic, then opens one to read in full.', fig: { id: 'visitor-13-commentary', caption: 'Commentary Corner with topic filters, desktop' } },
        { text: 'The essay page is set like a printed essay, with the author, date and reading time. There are no visitor comments.', fig: { id: 'visitor-14-commentary-piece', caption: 'A commentary piece, desktop' } },
        { text: 'The search button (or the "/" key) opens search over the whole site. Results appear as they type, grouped by section.', fig: { id: 'visitor-15-search-dialog', caption: 'Searching for "soil", desktop' } },
        { text: 'Pressing Enter opens the full results page, with the matching words highlighted.', fig: { id: 'visitor-16-search-results', caption: 'Search results page, desktop' } },
        { text: 'The moon button in the header switches the whole site to the dark appearance; the sun switches it back. The choice is remembered on this device.', fig: { id: 'visitor-21-home-dark', caption: 'Home page in the dark appearance, desktop' } },
        { text: 'Every page also works on a phone. The menu opens full-screen with the search box at the top.', figs: Object.assign([
            { id: 'mobile-01-home', label: 'Home' },
            { id: 'mobile-02-menu', label: 'Menu' },
            { id: 'mobile-03-publications', label: 'Publications' },
        ], { caption: 'Home, menu and publications on a phone (390 px wide)' }) },
    ],
    behind: [
        'Only published content is shown. Each list is in the order the editors set, and fields that are empty are simply left out.',
        'Pages are kept ready in a cache so they open quickly; the cache is refreshed the moment an editor saves a change.',
        'Photographs are served in sizes that suit the screen and load only as they scroll into view.',
        'No tracking runs: visitor analytics is off until the client approves it, and even then it sets no cookies and keeps no personal data.',
    ],
    result: 'The visitor has read his story, copied a correctly formatted citation, browsed the photographs and videos, and read a commentary, on desktop or phone, in light or dark.',
};

const TRIBUTE = {
    title: '2.2 Tribute author: leaving a tribute',
    persona: 'Tribute author (a former student)',
    goal: 'Leave a short remembrance and see it published on the Tributes page.',
    start: 'The "Leave a tribute" button, in the header of every page.',
    steps: [
        { text: 'The Tributes page opens on a featured tribute and the book of remembrance, which can be filtered by students, colleagues, family and friends.', fig: { id: 'tribute-01-wall', caption: 'The Tributes page, desktop' } },
        { text: 'The author writes their tribute: name, how they knew him, where they are writing from, the message, and optionally an email address that is never shown publicly. A counter shows how much space is left.', fig: { id: 'tribute-02-form', caption: 'Writing a tribute (step 1 of 2), desktop' } },
        { text: 'They choose "Preview" and see the tribute exactly as it will appear. They can go back and change it, or tick the agreement and send it.', fig: { id: 'tribute-03-preview', caption: 'Previewing the tribute (step 2 of 2), desktop' } },
        { text: 'A thank-you page confirms that the tribute will appear once it has been reviewed.', fig: { id: 'tribute-04-thank-you', caption: 'Thank-you page, desktop' } },
        { text: 'An editor sees the new tribute at the top of the "Pending review" list, with the author\'s email visible only to moderators, and approves it.', fig: { id: 'tribute-05-moderation', caption: 'The tribute waiting in the moderation queue, desktop' } },
        { text: 'The editor is told the tribute is approved and now on the Tributes page.', fig: { id: 'tribute-06-approved', caption: 'Confirmation after approval, desktop' } },
        { text: 'The tribute now appears in the book of remembrance for every visitor.', fig: { id: 'tribute-07-wall-after-approval', caption: 'The approved tribute on the public page, desktop' } },
    ],
    behind: [
        'The tribute is saved as "pending" and is not visible to anyone outside the dashboard until an editor or administrator approves it.',
        'The staff notification address (set in Site settings) is emailed that a tribute is waiting; if none is set, every editor and administrator is told.',
        'A hidden spam trap and a sending limit stop automated submissions.',
        'Moderators can approve, edit, decline or feature a tribute, and approve or decline several at once.',
    ],
    result: 'The author\'s words are published on the Tributes page after review, and the email address they gave stays private.',
};

export default function journeysPublic() {
    return [
        h1('2. User journeys'),
        p('Each journey below was walked through on the running site with the sample accounts, and every screenshot was taken during that walk-through. Steps show what the person does; "Behind the scenes" explains in plain language what the site does for them.'),
        p('All text and images in these screenshots are placeholders, marked "[Placeholder]" where they stand for real content. The people and messages in the community screens are invented for the demonstration.'),
        ...journey(VISITOR, { first: true }),
        ...journey(TRIBUTE),
    ];
}

