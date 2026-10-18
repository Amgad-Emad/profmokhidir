# profmokhidir.com: Claude Code build prompts

Run these prompts in order, one per Claude Code session (or one after another in the same session), from your project folder `~/Sites/profmokhidir` (Laravel 13, served by Valet at https://profmokhidir.test). Each prompt is self-contained, but Prompt 1 also writes a `CLAUDE.md` that Claude Code reads automatically in every later session, so the brand and scope rules stay in force throughout.

Review the diff and commit yourself after each step. Every prompt ends by asking Claude Code to stop and summarise, so you stay in control.

**Stack:** your existing Laravel 13 project, Blade, Livewire (chat and admin forms), Alpine.js, Tailwind CSS v4 via Vite, MySQL 8 (local and production), PHPUnit (already installed). Laravel Fortify for auth with custom Blade views, since it works headless and includes two-factor login. No admin package, so the dashboard can follow the brand exactly.

**Scope source:** the developer specification and client brief (10 sections, view-only CV, gated Scientific Community chat) plus the brand guidelines. Tributes come from the brand mock ("Leave a tribute") and are added as an 11th, moderated section. Drop Prompt 7 if the client does not want them.

| # | Prompt | Result |
|---|--------|--------|
| 1 | Project setup and CLAUDE.md | Laravel app, packages, rules file |
| 2 | Brand tokens and base styles | Tailwind theme, fonts, motion utilities |
| 3 | MK mark, layout, header, footer, navigation | Shared Blade components |
| 4 | Content model and seed data | Migrations, models, factories, seeders |
| 5 | Home page and About | Hero from the brand mock |
| 6 | Academic sections | CV, Publications, Awards, Teaching, Commentary, Other |
| 7 | Tributes | Public form, moderated wall |
| 8 | Media sections | Videos, Pictures with duotone treatment |
| 9 | Roles and admin dashboard shell | Auth, roles, dashboard home |
| 10 | Admin content management | CRUD for every content type |
| 11 | Scientific Community | Signup, approval queue, private chat |
| 12 | SEO, accessibility, performance | Meta, sitemap, WCAG 2.2 AA pass |
| 13 | Tests and final QA | Feature tests, browser checks |

---

## Prompt 1: Configure your project and write CLAUDE.md

```text
This folder is a fresh Laravel 13 project (created with composer create-project, served by Laravel Valet at https://profmokhidir.test). Do not create a new project. Configure it for profmokhidir.com, an academic profile and tribute website for Professor Mohamed Osman Khidir, with an admin dashboard.

First, move the database from SQLite to MySQL:
- Create a MySQL database named `profmokhidir` (utf8mb4, utf8mb4_unicode_ci). Ask me for the local MySQL username and password if you cannot connect with root and no password.
- In .env and .env.example set DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=profmokhidir and the credentials; set APP_NAME="Prof. Mohamed Osman Khidir" and APP_URL=https://profmokhidir.test.
- Run php artisan migrate:fresh against MySQL, then delete database/database.sqlite.

Then set up the testing database:
- Create a second MySQL database `profmokhidir_testing`.
- Create .env.testing (APP_ENV=testing, DB_DATABASE=profmokhidir_testing, CACHE_STORE=array, SESSION_DRIVER=array, QUEUE_CONNECTION=sync, MAIL_MAILER=array) and set DB_CONNECTION=mysql and DB_DATABASE=profmokhidir_testing in phpunit.xml, removing the default SQLite in-memory lines.
- Run `php artisan test` and confirm the example tests pass on profmokhidir_testing.

Then create the architecture skeleton described in the CLAUDE.md "Architecture" section: app/Constants, app/Repositories/Contracts, app/Repositories/Eloquent, app/Services, and app/Providers/RepositoryServiceProvider (registered in bootstrap/providers.php). Add a BaseRepositoryInterface and BaseEloquentRepository (find, paginate, create, update, delete, published scope helpers) and a working example for User: Constants/Roles, UserRepositoryInterface, EloquentUserRepository, UserService, UserFactory states, UserSeeder, plus one unit test and one feature test, so later steps can copy the pattern.

Stack:
- Laravel 13 (already installed), Blade templates. Check composer.json for the exact versions and use APIs that match them.
- Laravel Fortify for authentication (login, password reset, email verification, two-factor), with our own Blade views. No starter kit.
- Livewire (latest version compatible with Laravel 13) for interactive pieces (admin forms, community chat).
- Alpine.js (comes with Livewire) for small UI behaviour.
- Tailwind CSS v4 through Vite.
- MySQL 8 for local development and production.
- PHPUnit for tests (already installed).

Then create a CLAUDE.md at the project root containing the rules below verbatim, so every later session follows them:

## Product
Public academic profile website for Prof. Mohamed Osman Khidir (profmokhidir.com) with an admin dashboard for content editors. English only for release 1. No visitor analytics or tracking.

Public sections (in navigation order):
1. About Prof. Mokhidir: biography, portrait, title and affiliation, expertise, key facts.
2. CV and Achievements: VIEW-ONLY on-page CV. Never offer a CV download or expose a CV file.
3. Publications: type, year, authors, venue, DOI/citation, links/files. Type and year filters.
4. Awards: name, organisation, year, optional description and source link.
5. Videos: title, date, event/location, description, accessible playback or link.
6. Pictures: captioned photos with alt text, grouped in albums.
7. Teaching Materials: grouped by course/topic/type, with description, version/date, file type, download/link.
8. Commentary Corner: list and detail pages, topic tags. No visitor comments.
9. Scientific Community: signup application, admin approval, private chat for approved members only.
10. Other Information: structured items, not a catch-all.
11. Tributes: visitors leave a tribute; published only after an admin approves it.

Rules for every content type: render only populated fields; consistent heading, date, link and metadata patterns; each type has published/draft status and a display order; every list has a designed empty state.

Roles: admin (everything, including users and community approvals), editor (content and tribute moderation), member (approved community user), pending/rejected applicants (no chat access). Enforce chat access on every request, server side.

## Brand (follow exactly)
Colours, as Tailwind theme tokens, and no others:
- evergreen #13221C (primary, ~40% of surfaces)
- ivory #F3F1EA (primary, ~45%)
- cypress #2F4A3E (support, ~10%)
- brass #A8864F (accent, ~5%: the ring, thin rules, small labels on dark only; never large text or fills)
- champagne #DCCDA8 (text on evergreen)
- linen #E7E5DC, stone #D3D1C5, ash #575A55, bronze #6F5A34 (bronze replaces brass for small text on ivory, since brass lacks contrast under 24px)
No gradients except a soft brass radial glow on evergreen. No bright accent colours.

Type:
- Cormorant Garamond 300 and 300 italic: display, headings, quotes.
- Hanken Grotesk 300/400/500: body and UI (300 for reading, 500 for emphasis).
- JetBrains Mono: technical specs only.
- Scale: display 96px, heading 48px, quote 32px italic, body 18px, label 12px uppercase with 0.3em tracking.

Mark: "MK" in Cormorant Garamond Light inside a 1px brass hairline ring. Below 32px use a solid evergreen disc with ivory MK. Clear space is one sixth of the ring diameter on every side. Never fill, recolour, tilt or add effects to the mark. Lockups: stacked (mark over "Professor / Mohamed Osman Khidir") and horizontal (for nav, footer).

Motion: unhurried and scroll-linked. Content rises 40px and fades in over 1200ms, staggered 120ms, easing cubic-bezier(0.16,1,0.3,1). Hero elements scale 1.0 to 0.7 with scroll. Brass rules draw left to right over 1600ms ease-in-out to open sections. Nothing bounces, spins or flashes. Respect prefers-reduced-motion (show content instantly).

Photography: portraits in warm monochrome (desaturate, warm with ~12% ivory tone); archive photos in an evergreen-to-ivory duotone; detail crops of books and handwriting. No filters, vignettes, heavy grain or bright colour. Every photo has a caption with place and year in the label style.

UI: 4px radius cards; pill-shaped buttons, either ivory filled or outline; fixed glassy evergreen header (translucent evergreen with backdrop blur). Generous whitespace, measured and quiet.

Tagline voice examples: "Teacher, scholar and mentor. A life devoted to knowledge and to those who sought it." / "Knowledge is the light we pass on." / "His lessons continue in every student he taught."

## Engineering
- Accessibility target WCAG 2.2 AA: semantic HTML, keyboard support, visible focus (brass outline on dark, bronze on light), descriptive links, alt text.
- Responsive at mobile, tablet and desktop. No horizontal scroll at 360px.
- Optimise and lazy-load images; never load large media before needed.
- Public pages are plain Blade + Tailwind + small Alpine/vanilla JS and must not load Livewire scripts. Livewire is used only on /admin and /community/chat (inject its assets only in those layouts). Cache public page queries in services (cache keys in app/Constants/CacheKeys) and clear them when content is saved.
- Use Form Requests for validation, Policies for authorisation, Eloquent scopes for published content.

## Architecture (always, for every feature)
- app/Constants: one final class per concept holding fixed values (e.g. Roles, ContentStatus, CommunityStatus, PublicationType, TributeStatus, CacheKeys). Never hardcode these strings elsewhere; use backed PHP enums inside app/Constants where a value set needs casting.
- app/Repositories: all database access goes through repositories. Each has an interface in app/Repositories/Contracts and an Eloquent implementation in app/Repositories/Eloquent, bound in a RepositoryServiceProvider. Controllers and Livewire components never query models directly.
- app/Services: business logic (publishing, approvals, uploads, notifications, audit logging) lives in services that depend on repository interfaces. Controllers and Livewire components stay thin: validate (Form Request), authorise (Policy), call a service, return a view or response.
- Every model has a factory (with useful states such as published(), draft(), pending(), approved()) and a seeder, registered in DatabaseSeeder. No feature is done without both.
- Tests: unit tests in tests/Unit for every service and repository; feature tests in tests/Feature for every route, Livewire component and permission rule. Tests always run against the separate MySQL testing database `profmokhidir_testing` (configured in phpunit.xml and .env.testing), never the development database, using RefreshDatabase.
- Definition of done for any step: constants, repository (interface + implementation + binding), service, factory, seeder and tests exist for what was built, and `php artisan test` passes.
- Do not commit or push; the owner reviews and commits.

After setup: confirm migrations ran on MySQL, `npm run dev` builds cleanly and https://profmokhidir.test loads, then stop and summarise what you installed.
```

---

## Prompt 2: Brand tokens and base styles

```text
Read CLAUDE.md. Set up the brand design system in Tailwind CSS v4 (resources/css/app.css, using @theme).

1. Colour tokens, exactly and only: evergreen #13221C, ivory #F3F1EA, cypress #2F4A3E, brass #A8864F, champagne #DCCDA8, linen #E7E5DC, stone #D3D1C5, ash #575A55, bronze #6F5A34. Remove or override Tailwind's default palette so other colours cannot be used by accident (keep white/black/transparent/current only).
2. Fonts: load Cormorant Garamond (300, 300 italic), Hanken Grotesk (300, 400, 500) and JetBrains Mono (400) from Google Fonts with display=swap and preconnect. Define font-display, font-sans, font-mono tokens.
3. Type scale utilities or component classes: .t-display (96px, Cormorant 300, tight leading, scaling down fluidly to ~52px on mobile with clamp), .t-heading (48px → ~34px), .t-quote (32px italic Cormorant), .t-body (18px Hanken 300, leading 1.7), .t-label (12px Hanken 500 uppercase, letter-spacing 0.3em).
4. Base styles: body is ivory with evergreen text; links underline on hover; visible focus ring (bronze on light surfaces, brass on evergreen surfaces); selection colour in linen.
5. Components as CSS classes: .btn-pill-filled (ivory fill, evergreen text), .btn-pill-outline (1px border in current colour), .card (4px radius, linen or ivory surface, stone hairline border), .rule-brass (1px brass line), .glow-brass (soft radial brass glow, only for use on evergreen backgrounds).
6. Motion, in resources/js/motion.js with no animation library:
   - [data-rise] elements fade in and rise 40px over 1200ms with cubic-bezier(0.16,1,0.3,1) when they enter the viewport (IntersectionObserver); children of [data-rise-group] stagger by 120ms.
   - [data-rule] brass rules draw left to right (scaleX 0 → 1, transform-origin left) over 1600ms ease-in-out when in view.
   - [data-scroll-scale] elements scale from 1.0 to 0.7 and fade slightly as the hero scrolls out (requestAnimationFrame, passive scroll listener).
   - All of it is disabled under prefers-reduced-motion: content shows immediately.
   - Nothing bounces, spins or flashes.
7. Image treatments as CSS classes: .photo-portrait (grayscale then warm sepia tint to approximate warm monochrome with ~12% ivory), .photo-archive (evergreen-to-ivory duotone via an inline SVG filter defined once in the layout). No vignettes or grain.
8. Build a hidden route /styleguide (only in local environment) showing every colour, type style, button, card, rule and motion example, so I can review the system.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise. Tell me the URL of the style guide.
```

---

## Prompt 3: MK mark, layout, header, footer, navigation

```text
Read CLAUDE.md. Build the shared layout and brand mark as Blade components.

1. <x-brand.mark size="..." tone="ivory|evergreen" />: an inline SVG "MK" set in Cormorant Garamond Light, centred in a 1px Antique Brass (#A8864F) hairline ring. The ring stays 1px at every size (vector-effect: non-scaling-stroke). Include clear space of one sixth of the ring diameter on every side as padding. When size is under 32px, render a solid evergreen (#13221C) disc with ivory MK instead, no ring. Never apply fills, shadows, rotation or effects. Include an accessible title "Professor Mohamed Osman Khidir".
2. <x-brand.lockup variant="stacked|horizontal" tone="..." />: stacked = mark above the label "PROFESSOR" (label style) and "Mohamed Osman Khidir" (Cormorant 300). Horizontal = mark left of the same text, for nav and footer.
3. Favicon set from the mark: favicon.svg (small-size disc version), 32px and 180px PNG apple-touch icon, site.webmanifest with evergreen theme colour.
4. layouts/public.blade.php:
   - Fixed glassy header: translucent evergreen (about 80% opacity) with backdrop blur, champagne text, horizontal lockup on the left.
   - Primary nav: About, CV & Achievements, Publications, Awards, Videos, Pictures, Teaching, Commentary, Community, Tributes, plus Other Information in the footer and a "More" menu if space runs out. Use label style for nav items. The active section is marked with a short brass rule under it and aria-current="page".
   - On tablet and mobile, a full-screen evergreen menu opened by an accessible button (aria-expanded, focus trap, Escape closes, focus returns to the button). Menu items rise in with the 120ms stagger.
   - "Skip to content" link as the first focusable element.
   - Footer on evergreen: stacked lockup, the line "His lessons continue in every student he taught." in Cormorant italic champagne, section links, "profmokhidir.com" in label style, copyright. No social tracking widgets.
5. A <x-section-header label="Chapter One — Early Years" title="..." /> component: brass label (bronze when on ivory), a brass rule that draws left to right, and the heading in .t-heading.
6. A <x-empty-state message="..." /> component for lists with no published content: quiet Cormorant italic line and a short brass rule.
7. Placeholder routes for every section using the layout, so navigation works end to end.

Check the header and menu at 360px, 768px and 1440px wide, and with keyboard only.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 4: Content model and seed data

```text
Read CLAUDE.md. Create the database schema, models, factories and seeders for all content types. Target MySQL 8 (use json columns, fulltext indexes on titles/summaries for admin search, and foreign keys with sensible onDelete rules). Every content type gets: status (draft|published), published_at, sort_order, timestamps, and a `published()` scope ordered by sort_order then date. Use slugs where a type has detail pages. Media uses Laravel's public disk with a `media` table (path, alt_text, caption, place, year, credit, permissions_note) referenced by the types that need images.

Tables and fields:
- profile (single row): name, title, affiliation, short_bio, full_bio (rich text), expertise (json list), key_facts (json list of label/value), portrait_media_id, contact_email_public (nullable, shown only if set), contact_note.
- cv_entries: section (education|appointment|leadership|project|achievement), title, organisation, role_description, start_year, end_year (nullable = present), last_updated_at.
- publications: title, type (book|journal_article|chapter|conference_paper|report|other), authors, year, venue_publisher, doi, citation, abstract, url, file_path (nullable).
- awards: name, organisation, year, description, source_url.
- videos: title, date, event, location, description, provider (youtube|vimeo|file|link), media_url, file_path, captions_url, thumbnail_media_id.
- albums: title, slug, description, cover_media_id. album_photos: album_id, media_id, sort_order.
- teaching_materials: title, course, topic, type (slides|notes|reading|exercise|syllabus|other), description, version, material_date, file_path, external_url, access (public|members).
- commentaries: title, slug, date, summary, body (rich text), external_url, tags (json), byline.
- other_items: title, item_type, summary, body, date, category, media_id, destination_url.
- tributes: name, relationship (student|colleague|family|friend|other), location, message (max 2000 chars), email (private, never displayed), status (pending|approved|rejected), approved_by, approved_at, ip_hash.
- community applications live on users: add role (admin|editor|member|applicant), community_status (none|pending|approved|rejected|suspended), affiliation, academic_field, reason_for_joining, reviewed_by, reviewed_at, review_note.
- chat_channels: name, slug, description, last_message_id. chat_messages: id (bigint auto-increment, the only ordering key), channel_id, user_id, client_uuid (unique together with user_id, for idempotent sends), body (utf8mb4, so emoji and Arabic text work), version (int, for optimistic locking on edits), edited_at, soft deletes; index (channel_id, id). chat_reads: channel_id, user_id, last_read_message_id, unique (channel_id, user_id). chat_reports: message_id, reporter_id, reason, resolved_at, unique (message_id, reporter_id).
- audit_logs: user_id, action, subject_type, subject_id, meta (json), created_at. Log approvals, rejections, deletions and role changes.

For every table: a model with casts using the enums/constants in app/Constants, a factory with states (published, draft, and per-status states such as pending/approved/rejected), a seeder, a repository interface plus Eloquent implementation bound in RepositoryServiceProvider, and a service for its business logic (even if thin for now). Constants classes needed here include ContentStatus, PublicationType, CvSection, TeachingMaterialType, VideoProvider, TributeStatus, TributeRelationship, Roles, CommunityStatus and AuditAction.

Seeders: a realistic but clearly placeholder dataset (mark every bio and text with "[Placeholder]" so nobody mistakes it for approved content): the profile, 12 CV entries, 15 publications across types and years, 6 awards, 4 videos, 2 albums with grey placeholder images, 8 teaching materials, 5 commentaries, 3 other items, 6 approved and 3 pending tributes, one admin (admin@example.com) and one editor (editor@example.com) with passwords printed to the console, two approved members, two pending applicants, one "General" chat channel with a few messages.

Run migrate:fresh --seed against the MySQL database.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise the schema.
```

---

## Prompt 5: Home page and About

```text
Read CLAUDE.md. Build the home page and the About page using the layout and components already in the project.

Home page, following the brand guidelines' "Website hero" mock:
1. Hero on evergreen, full viewport height, with a soft brass glow (the only gradient allowed). Left column: label "IN HONOUR OF" in brass, then "Professor" and "Mohamed Osman Khidir" in the display style (champagne/ivory), the line "Teacher, scholar and mentor. A life devoted to knowledge and to those who sought it." in body style, and two pill buttons: "Read his story" (ivory filled, links to About) and "Leave a tribute" (outline, links to the tribute form). Right column: the profile portrait in the warm-monochrome treatment inside a 4px-radius frame, with a place/year caption in label style. Hero text and portrait use data-scroll-scale so they recede from 1.0 to 0.7 as the reader scrolls.
2. A quote band on ivory: "Scholarship is the one inheritance that grows each time it is shared." in the quote style, with a brass rule drawing above it.
3. Three short chapters, each opened with <x-section-header>: "Life" (short bio and link to About), "Work" (the 3 latest publications and a count of awards, linking to Publications and CV), "Tributes" (3 approved tributes as quote cards, linking to the Tributes page).
4. A row linking to the remaining sections (Videos, Pictures, Teaching, Commentary, Community) as quiet cards with label-style titles.
5. Everything below the hero uses data-rise with the 120ms stagger.

About page (/about):
- Portrait (warm monochrome) with caption, name, title and affiliation.
- Full biography in body style with a comfortable reading width (about 68ch), Cormorant headings inside it, and a pull quote in the quote style.
- Expertise as a quiet list of label-style tags, key facts as a definition list.
- Public contact details only if the field is filled.
- Links on to CV, Publications and Awards.

All content comes from the database (no hardcoded bio text). Render only populated fields. Check both pages at 360px, 768px and 1440px and with reduced motion on.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 6: Academic sections

```text
Read CLAUDE.md. Build these public sections on ivory backgrounds with the shared section header, data-rise motion and empty states. Render only populated fields throughout, and use one consistent pattern for dates, metadata and external links (external links say where they go and open in the same tab unless they are files; file links show type and size, e.g. "PDF · 2.1 MB").

1. CV and Achievements (/cv): view-only, on-page CV grouped by section (Education, Appointments, Leadership, Projects, Achievements). Each entry shows years in label style on the left, title and organisation in Cormorant, description in body style. Show "Last updated" at the top. Do NOT add any download or print-to-PDF button, and do not store or link any CV file. Add a summary strip at the top with counts per section.
2. Publications (/publications): list grouped by year (newest first) with filters for type and year as accessible buttons/selects that update the URL query string (works without JavaScript). Each item: title, authors, venue, year, type label, DOI as a link to doi.org, citation in a collapsible "Cite" disclosure with a copy button, and the file or URL if present. Paginate at 20.
3. Awards (/awards): timeline layout with a brass hairline down the left, year in label style, award name in Cormorant, organisation and description, source link.
4. Teaching Materials (/teaching): grouped by course, filterable by topic and type. Each card: title, type, description, version/date, file type and size, download or external link. Items with access "members" show only to approved community members; others see a quiet note that the item is for members.
5. Commentary Corner (/commentary): archive list with date, title, summary and tags; tag filter. Detail page (/commentary/{slug}) with long-form reading layout (~68ch), byline, date, tags, external link if set, and previous/next links. No comments.
6. Other Information (/other): items grouped by category with title, type, summary and destination link or detail.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 7: Tributes

```text
Read CLAUDE.md. Build the Tributes section.

1. Public page (/tributes): opens on evergreen with the label "REMEMBERING" in brass and the heading "He taught us how to ask the right questions." in the quote style. Approved tributes appear as cards: the message in Cormorant italic, then name, relationship and location in label style. Masonry or two-column layout on desktop, single column on mobile, rising in with the stagger. Paginate at 24. Never show email addresses.
2. Tribute form (/tributes/new, also reached from "Leave a tribute"): fields name, relationship (select), location (optional), message (textarea, 2000-character limit with a live counter), email (optional, "never shown publicly"), consent checkbox. Form Request validation, a honeypot field and rate limiting (3 per hour per IP). Store the IP only as a hash. New tributes are saved as pending.
3. After submitting, show a calm confirmation: "Thank you. Your tribute will appear once it has been reviewed." Do not reveal whether moderation is quick or slow.
4. Notify admins and editors by email (log mailer locally) when a tribute is waiting.
5. Share card: a static Open Graph image for /tributes following the brand's social card (evergreen, MK mark, the quote "Knowledge is the light we pass on.", profmokhidir.com in label style), 1200×630, generated once as a file, not at request time.

Moderation screens come later in the admin prompts.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 8: Media sections

```text
Read CLAUDE.md. Build Videos and Pictures.

1. Videos (/videos): grid of cards with thumbnail (archive duotone treatment when it is a historic image), title, date, event and location in label style, and short description. Detail or modal player:
   - YouTube embeds use youtube-nocookie.com and load only after the visitor clicks play (a lightweight facade), so nothing third-party loads on page view. Vimeo uses dnt=1.
   - Uploaded files use <video> with controls, preload="none" and a captions <track> if provided.
   - Plain links open the external page with a clear label.
2. Pictures (/pictures): album index with cover images, then album pages (/pictures/{album}) with a responsive grid. Every image has alt text and a caption with place and year in label style. Lightbox: accessible dialog with keyboard navigation (arrows, Escape), focus trapping, caption visible, swipe on touch. No zoom bounce effects; transitions are soft fades.
3. Image processing on upload (used by the admin later): generate responsive WebP sizes (480, 960, 1600) with intervention/image, serve with srcset and loading="lazy", width/height set to prevent layout shift. Apply the portrait (warm monochrome) or archive (duotone) treatment through CSS classes chosen per image, not by altering the stored original.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 9: Roles and admin dashboard shell

```text
Read CLAUDE.md. Build authentication, roles and the admin dashboard shell.

1. Wire up Laravel Fortify with our own Blade views for login, forgot/reset password, email verification and the two-factor challenge, styled to the brand: ivory page, centred card (4px radius), stacked lockup above the form, pill buttons, label-style field labels. Disable Fortify's public registration feature (community signup is separate and comes later). Add a simple account page for changing name, email and password.
2. Roles on users: admin, editor, member, applicant. Middleware `role:admin,editor` for the dashboard. Gates/Policies for every model: editors manage content and tributes; only admins manage users, roles, community applications and chat moderation.
3. Dashboard layout at /admin: evergreen sidebar with the horizontal lockup, champagne text, label-style group headings (Content, Media, Community, Tributes, Settings), active item marked by a brass rule. Main area on ivory/linen. Collapsible sidebar on mobile. Uses the same type and colour tokens as the public site; no other colours (use cypress and bronze for status instead of red/green: e.g. pending = bronze, approved = cypress, rejected = ash). Error states still need clear text and icons, not colour alone.
4. Dashboard home: stat cards (published items per section, tributes pending, community applications pending, chat reports open), a "Needs attention" list linking to each queue, and recent activity from the audit log.
5. Require Fortify's two-factor authentication (TOTP) for admin accounts, rate-limit logins, and log admin sign-ins to the audit log.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise, including how to log in with the seeded admin.
```

---

## Prompt 10: Admin content management

```text
Read CLAUDE.md. Build the admin CRUD screens for every content type, using Livewire components inside the /admin layout.

For each of: Profile (single edit form), CV entries, Publications, Awards, Videos, Albums and photos, Teaching materials, Commentaries, Other items:
1. Index table with search, filters (status, type, year as relevant), sortable columns, and drag-and-drop reordering that saves sort_order (keyboard-accessible alternative: move up/down buttons).
2. Create/edit form using Form Requests, with only the fields relevant to that type, inline validation messages, draft/published toggle, and a "View on site" link for published items.
3. Rich text for full_bio and commentary body using a lightweight editor (Tiptap via Alpine) limited to headings, bold, italic, links, lists and quotes; sanitise HTML on save (allowlist).
4. Media library: upload images (validate type and size, max 10 MB), require alt text, caption, place and year before an image can be published; choose the portrait or archive treatment; show where an image is used before deleting it.
5. File uploads for publications and teaching materials (PDF, DOCX, PPTX, ZIP; max 50 MB), stored with original name and size shown. The CV has no file field anywhere.
6. Soft delete with a confirmation dialog and a Trash view to restore. Every create, publish, unpublish and delete is written to the audit log.

Tributes moderation (/admin/tributes): tabs for Pending, Approved, Rejected; approve, reject or edit typos (keeping the original stored in the audit log); bulk approve/reject; show the email to moderators only.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 11: Scientific Community

```text
Read CLAUDE.md. Build the Scientific Community: public signup, admin approval, and a private chat for approved members only.

1. Public page (/community): explains what the community is, who may join and the conduct rules, then a signup form: name, email, password, affiliation, academic field, reason for joining, agreement to the conduct rules and to the privacy note explaining how the data is used and kept. Creates a user with role applicant and community_status pending. Verify email. Honeypot and rate limit.
2. Applicant states: after signup and login, a pending applicant sees "Your application is being reviewed"; a rejected one sees a polite message with no chat access; a suspended member sees a suspension notice.
3. Admin approval queue (/admin/community): pending applications with all fields, approve or reject with an optional note, email the applicant the decision, record reviewed_by/reviewed_at and an audit log entry. Admins can suspend or reinstate members and remove them.
4. Chat (/community/chat), Livewire:
   - Channels list and a message view. Messages show name, affiliation and time; newest at the bottom; load older on scroll.
   - Send with Enter, Shift+Enter for new line, 2000-character limit, plain text with links auto-linked (escape everything else).
   - Live updates with wire:poll every 5 seconds by default. Make it easy to switch to Laravel Reverb later behind a config flag, but do not require Reverb now.
   - Members can edit or delete their own messages and report others' messages with a reason. Admins can delete any message, resolve reports and suspend authors from the report.
   - Rate limit sending (e.g. 20 messages per minute).
5. Access control: a `community.member` middleware AND policy checks on every Livewire action and every message query. Pending, rejected, suspended and logged-out users must not be able to read messages or send them, including through direct Livewire requests. Chat pages are noindex.
6. Chat reliability. Handle every one of these common chat problems in ChatService and ChatMessageRepository, not in the component:
   - Two people sending at the same moment: insert each message in its own short DB transaction and order ONLY by the auto-increment id, never by created_at (timestamps collide at the same second). Both messages must always appear, in the same order, for every viewer.
   - Missed messages between polls: each client keeps the last message id it has seen and asks for `id > last_seen_id` (keyset cursor), never "messages since time X". After a send, fetch from the cursor so the sender's own message and anything that arrived just before it both show.
   - Duplicate sends (double click, Enter pressed twice, slow network retry, two tabs): the client generates a UUID per message; the server uses firstOrCreate on (user_id, client_uuid) under the unique index, so a retry returns the existing message instead of creating a second one. Disable the send button while a send is in flight.
   - Optimistic UI: show the message immediately as "sending", replace it with the saved one by client_uuid when confirmed, and show "Not sent · Retry" on failure without losing the text.
   - Edits and deletes from other people: poll also returns messages changed since the last check (by an updated cursor), so edits and deletions appear for everyone. Deleted messages show "Message removed" rather than vanishing and shifting the conversation.
   - Two edits of the same message (two tabs): optimistic locking with the version column; a stale edit is rejected with "This message was changed elsewhere, reload it".
   - Editing or reporting a message that was just deleted: return a clear message, never a 500.
   - Scroll: keep the reader's position when older messages load or new ones arrive while they are scrolled up; show a "New messages" pill instead of jumping. Auto-scroll only when they are already at the bottom.
   - Unread state: update chat_reads.last_read_message_id (only ever increasing, using GREATEST) and show an unread divider and counts per channel.
   - Access changes mid-session: if a member is suspended or removed, the very next poll or send is refused and the page shows the suspension notice.
   - Session expiry or lost connection: detect 419/401 and network errors, pause polling, show "Reconnecting…", back off (5s, 10s, 30s) and resume from the cursor without duplicating messages. Pause polling when the tab is hidden and catch up when it returns.
   - Safety: escape all message text, auto-link URLs with rel="nofollow noopener", trim whitespace, reject empty messages, enforce the 2000-character limit on the server, rate-limit sends per user, and eager-load users to avoid N+1 queries.
   - Times: store UTC, display in the viewer's timezone, and group messages by day.
   These rules must work unchanged when Reverb is switched on (broadcast after commit with ShouldBroadcastAfterCommit, and still fetch from the cursor on reconnect).
7. Write PHPUnit feature tests proving: an applicant cannot load the chat page or call the send action; a rejected user cannot; an approved member can read and send; a suspended member loses access immediately; logged-out users are redirected. Also test the reliability rules: two users sending in the same second both appear in id order; resending the same client_uuid creates only one message; a cursor fetch returns exactly the messages after it; a stale edit is rejected; editing a deleted message fails gracefully; last_read_message_id never moves backwards. Add unit tests for ChatService covering the same cases.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 12: SEO, accessibility and performance

```text
Read CLAUDE.md. Do a pass across the whole public site for SEO, accessibility and performance.

1. SEO: every public page has a unique <title> ("Section · Professor Mohamed Osman Khidir") and meta description from its content; canonical URLs; Open Graph and Twitter tags using the branded share card; schema.org Person JSON-LD on About and ScholarlyArticle on publications; sitemap.xml of published content; robots.txt that disallows /admin and /community/chat.
2. Accessibility (WCAG 2.2 AA): run axe-core with Playwright against every public page and the main admin screens, and fix every violation. Check colour contrast of every text/background pair in use and switch brass to bronze where it fails on ivory. Confirm a logical heading order, keyboard access to every control, visible focus, focus not obscured by the fixed header (scroll-padding-top), target sizes of at least 24px, and reduced-motion behaviour.
3. Performance: Lighthouse on home, About, Publications and Pictures; aim for 90+ in every category on mobile. Preload the display font, subset fonts if useful, lazy-load below-the-fold images, cache headers for built assets, and make sure no third-party request happens before user interaction.
4. Error pages (404, 403, 419, 500, 503) in the brand: evergreen, MK mark, a short Cormorant line and a pill button home.

Give me a table of what you found and fixed.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise.
```

---

## Prompt 13: Tests and final QA

```text
Read CLAUDE.md. Prepare the project for client review.

1. PHPUnit feature tests for: each public section rendering seeded content and its empty state; draft content never appearing publicly; the CV page having no download links and no CV file route; publication filters; tribute submission (validation, rate limit, pending until approved, email never shown); editor vs admin permissions on every admin route; community access rules (keep the tests from the community prompt).
2. Playwright smoke test: visit every public page at 360px, 768px and 1440px, take screenshots into storage/qa/, fail on console errors, broken links or horizontal scroll.
3. Brand audit: scan the built CSS and Blade templates for any colour value outside the nine brand tokens, any font family outside the three brand fonts, any bounce/spin animations, and any gradient other than the brass glow. Report and fix.
4. Write README.md: requirements, local setup, seeded logins, how to add content, how to switch the chat to Reverb, deployment notes for a standard Laravel host (queue worker for mail, storage:link, scheduler, backups), and the open client decisions still listed in the client brief (section names, contact details, community eligibility and moderation, hosting, analytics).
5. Run the full test suite and report the results honestly, including anything that fails.

Follow the Architecture rules in CLAUDE.md for everything in this step: constants in app/Constants, repository interface and Eloquent implementation bound in RepositoryServiceProvider, logic in a service, a factory and seeder for every model, unit tests for services and repositories, feature tests for routes and components, all run with `php artisan test` on the profmokhidir_testing database. Make sure the full suite passes before you stop.

Stop and summarise what is ready and what still needs client content or decisions.
```

---

## Tips

- If a step goes wrong, ask Claude Code to "revert the changes from this step" before retrying, or reset with git.
- To add the brand guidelines file itself to the project for reference, put it at `docs/brand-guidelines.html` and add a line to CLAUDE.md saying "Brand source of truth: docs/brand-guidelines.html".
- Replace the "[Placeholder]" seed content with approved text and images before showing the client.
