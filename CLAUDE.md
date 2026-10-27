# profmokhidir.com — Claude Code project rules

Laravel 13 application served locally by Laravel Valet at https://profmokhidir.test.
Stack: Laravel 13, Blade, Laravel Fortify (own Blade views in resources/views/auth, no starter kit),
Livewire 4, Alpine.js, Tailwind CSS v4 via Vite, MySQL 8 (`profmokhidir` dev / `profmokhidir_testing` tests), PHPUnit.
Roles and permissions use spatie/laravel-permission; all media uses spatie/laravel-medialibrary. Access media and roles only through repositories and services.
Check composer.json and package.json for exact versions and use APIs that match them.

## Product
Public academic profile website for Prof. Mohamed Osman Khidir (profmokhidir.com) with an admin dashboard for content editors. English and Arabic (RTL); English is the default (Arabic under /ar; admin, chat and sign-in follow the user's saved language). Analytics: first-party, cookieless, off by default; enabled only after client approval.

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

Functional exception: danger red, only for destructive actions and errors (never decoration), approved by the owner:
- danger-light #9B2C2C on light surfaces (6.7:1 on ivory, 6.0:1 on linen), danger-dark #EFB0A5 on dark surfaces (9.0:1 on evergreen, 5.3:1 on cypress).
- Declared once in tokens.css (--functional-*, App\Constants\Brand::functionalColours()) and used only through the semantic tokens --danger (text, borders, icons), --danger-surface (the solid fill), --danger-ink (text on that fill) and --danger-wash (behind an error summary).
- Destructive actions use `<x-button variant="danger">` (outline; trash icon on deletes); `variant="danger-filled"` only for the final confirm inside a confirmation dialog. Irreversible actions confirm in `<x-admin.confirm-dialog danger>` (danger icon, clear title, what will happen, Cancel focused); reversible ones (move to trash) act at once with the 10-second Undo toast.
- Form error text, error borders, error summaries and error alerts (StatusTone::Danger) use the danger tokens.

Type:
- Cormorant Garamond 300 and 300 italic: display, headings, quotes.
- Hanken Grotesk 400 for body, 500 for emphasis, labels and UI; 300 is not used for running text. Body 18px desktop / 17px mobile at line-height 1.6; small text never below 15px.
- JetBrains Mono: technical specs only.
- Scale: display 96px, heading 48px, quote 32px italic, body 18px (17px mobile), label 12px uppercase at weight 500 with 0.24em tracking.

Arabic type (a documented exception: Cormorant Garamond and Hanken Grotesk have no Arabic glyphs; rules in resources/css/locale.css, App\Constants\Typography and BrandAudit):
- Display and headings: Amiri 400 and 700, wherever Cormorant is used. Body and UI: IBM Plex Sans Arabic 400 and 500, wherever Hanken Grotesk is used. JetBrains Mono unchanged. These two are the only extra families allowed.
- Loaded from Google Fonts with display=swap, only on Arabic pages. In the stacks the self-hosted Latin brand font is listed before its Arabic partner (the Latin files cover Latin only, so English words, Western digits and Latin punctuation stay in the brand fonts and Arabic letters fall through to Amiri / IBM Plex Sans Arabic; Google's Arabic CSS also ships Latin subsets, which must not take over).
- Arabic has no letter-spacing and no uppercase: labels drop the tracking and text-transform. Slightly larger sizes and line-height: body about 19px at line-height 1.8 (18px on phones), labels about 14px; small text never below 15px still holds.
- No italics in Arabic: the quote style keeps its colour and size, upright; <em> is weight and ink, not oblique.
- Old-style figures, small caps, extra ligatures and drop caps are Latin-only: in Arabic use lining figures and, instead of a drop cap, the first word in Amiri 700.
- Western digits (0–9) by default in both languages; a site setting may switch Arabic running text to Arabic-Indic digits (years, citations, DOIs and data stay Western).
- Mirror directional icons (.rtl-mirror: arrows, chevrons, back, breadcrumbs separators), never the MK mark, logos, media players, maps, charts' time axes or code. Use logical CSS properties; :dir(rtl) only where they cannot express it.

Mark: "MK" in Cormorant Garamond Light inside a 1px brass hairline ring. Below 32px use a solid evergreen disc with ivory MK. Clear space is one sixth of the ring diameter on every side. Never fill, recolour, tilt or add effects to the mark. Lockups: stacked (mark over "Professor / Mohamed Osman Khidir") and horizontal (for nav, footer).

Motion: unhurried and scroll-linked. Content rises 40px and fades in over 1200ms, staggered 120ms, easing cubic-bezier(0.16,1,0.3,1). Hero elements scale 1.0 to 0.7 with scroll. Brass rules draw left to right (right to left on Arabic pages) over 1600ms ease-in-out to open sections. Nothing bounces, spins or flashes. Respect prefers-reduced-motion (show content instantly).

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
- Roles and permissions use spatie/laravel-permission; all media uses spatie/laravel-medialibrary. Access media and roles only through repositories and services (RoleRepository/RoleService and MediaRepository/MediaService): no HasRoles or addMedia() calls in controllers, Livewire components, views or seeders. Role and permission names live in app/Constants/Roles and app/Constants/Permissions; policies check permissions, never role names. Media is attached through named collections (app/Constants/MediaCollections) with the custom properties in app/Constants/MediaProperties (alt_text, caption, place, year, credit, permissions_note, treatment); images get queued responsive WebP conversions at 480/960/1600 (app/Constants/MediaConversions). Public content uses the `public` disk, members-only teaching files the `private` disk (app/Constants/Disks).
- app/Constants: one final class per concept holding fixed values (e.g. Roles, ContentStatus, CommunityStatus, PublicationType, TributeStatus, CacheKeys). Never hardcode these strings elsewhere; use backed PHP enums inside app/Constants where a value set needs casting.
- app/Repositories: all database access goes through repositories. Each has an interface in app/Repositories/Contracts and an Eloquent implementation in app/Repositories/Eloquent, bound in a RepositoryServiceProvider. Controllers and Livewire components never query models directly.
- app/Services: business logic (publishing, approvals, uploads, notifications, audit logging) lives in services that depend on repository interfaces. Controllers and Livewire components stay thin: validate (Form Request), authorise (Policy), call a service, return a view or response.
- Every model has a factory (with useful states such as published(), draft(), pending(), approved()) and a seeder, registered in DatabaseSeeder. No feature is done without both.
- Tests: unit tests in tests/Unit for every service and repository; feature tests in tests/Feature for every route, Livewire component and permission rule. Tests always run against the separate MySQL testing database `profmokhidir_testing` (configured in phpunit.xml and .env.testing), never the development database, using RefreshDatabase.
- Definition of done for any step: constants, repository (interface + implementation + binding), service, factory, seeder and tests exist for what was built, and `php artisan test` passes.
- Always create files with the artisan generators, never by hand: php artisan make:migration (and make:migration add_x_to_y_table --table=y for changes), make:model Name -mfs, make:factory, make:seeder, make:controller, make:request, make:policy --model=, make:middleware, make:notification, make:mail, make:job, make:event, make:listener, make:rule, make:enum, make:interface, make:class, make:provider, make:livewire, make:test and make:test --unit. Then fill in the generated file. Never edit a migration that has already run; add a new migration for every schema change.
- Do not commit or push; the owner reviews and commits.
