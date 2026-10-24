# profmokhidir.com

Public academic profile and tribute website for Professor Mohamed Osman Khidir, with an
admin dashboard for content editors and a private Scientific Community chat.

Laravel 13 · Blade · Livewire 4 (admin and chat only) · Alpine.js · Tailwind CSS v4 · MySQL 8 ·
Laravel Fortify (own Blade views, two-factor for admins) · spatie/laravel-permission ·
spatie/laravel-medialibrary · PHPUnit 12.

All product, brand, engineering and architecture rules live in `CLAUDE.md`. Read it before
changing anything.

## Requirements

| Tool | Version |
|------|---------|
| PHP | 8.3 or newer (8.4 locally), with the `gd` or `imagick`, `intl`, `mbstring`, `pdo_mysql`, `fileinfo` and `zip` extensions |
| Composer | 2.x |
| Node.js | 20 or newer (25 locally) and npm |
| MySQL | 8.0 (utf8mb4) |
| Google Chrome | only for the QA scripts (`npm run qa:smoke`, `npm run a11y`, `npm run perf`) |

Locally the site is served by Laravel Valet at <https://profmokhidir.test>.

## Local setup

```bash
composer install
cp .env.example .env            # then set DB_USERNAME / DB_PASSWORD if not root with no password
php artisan key:generate
```

Create two databases (utf8mb4 / utf8mb4_unicode_ci): `profmokhidir` for development and
`profmokhidir_testing` for the test suite. `.env.testing` and `phpunit.xml` already point the
tests at the second one, so `php artisan test` never touches development data.

```bash
php artisan migrate --seed       # placeholder content; prints the seeded passwords once
php artisan storage:link         # public media is served from public/storage
npm install
npm run build
```

While working, keep `composer run dev` running. Valet serves the site; the command starts the
queue worker (`queue:listen`), the log tail (`pail`) and Vite (`npm run dev`) together. The queue
matters: image thumbnails (chat pictures, media library) and mail are queued
(`QUEUE_CONNECTION=database`), so without a worker no WebP thumbnail is ever made. The chat then
shows the full-size original in its place. If you only build assets, run
`php artisan queue:work --stop-when-empty` after seeding or uploading to process the backlog.

Then open <https://profmokhidir.test>. The design-system review page is at `/styleguide`
(local environment only; 404 elsewhere).

Every seeded text field starts with `[Placeholder]` so nothing is mistaken for approved copy.
`php artisan migrate:fresh --seed` resets everything.

## Seeded logins

The seeder generates a random password for each account and prints them in a table at the end
of `php artisan db:seed`. They are not stored anywhere else; re-seeding leaves existing accounts
untouched, so run `migrate:fresh --seed` (or set a password with `php artisan tinker`) if you
lose them.

| Email | Role | Notes |
|-------|------|-------|
| admin@example.com | Administrator | Everything, including community approvals and chat reports. Must set up two-factor authentication at `/account` on first sign-in before `/admin` opens. |
| editor@example.com | Editor | Content screens, media library and tribute moderation. |
| member1@example.com, member2@example.com | Community member | Approved; can use `/community/chat`. |
| applicant1@example.com, applicant2@example.com | Applicant (pending) | Signed up; waiting in the admin approval queue. No chat access. |

Accounts created by factories in tests use the password `password`.

## How to add content

Sign in at `/login` and open `/admin`. The sidebar groups follow the public sections:

- **Content**: About & profile (biography, portrait, expertise, key facts, public contact),
  CV entries (by section: education, appointments, achievements, leadership, projects),
  Publications (type, year, authors, venue, DOI, citation, link or file), Awards, Commentary
  (rich text, tags), Teaching materials (course, topic, type, version/date, file or link; files
  marked *members only* go to the private disk and are only served to approved members),
  Other information (structured items with a category and either a body or a destination link).
- **Media**: Videos (YouTube, Vimeo, uploaded file or plain link, with thumbnail and
  captions), Pictures & albums (captioned photos with alt text, place and year; set a cover),
  Media library (every uploaded file, where it is used, alt text and captions).
- **Tributes**: visitor tributes arrive as *pending*; approve, edit or reject them. Only
  approved tributes appear on the public wall and the home page. The submitter's email is
  never shown publicly.
- **Community**: applications (approve or reject with an optional note; the applicant is
  emailed), members (suspend, reinstate or remove) and chat reports. Admins only.

Rules that apply to every content type:

- Each record is **draft** or **published**. Only published records appear on the public site,
  in the sitemap or in the home-page chapters. Drafts are visible in the dashboard only.
- Each list has a **display order**; detail pages exist for videos, albums, commentaries and
  other items with a body.
- Images need **alt text**; photos should carry a **caption, place and year** (shown in the label
  style under the picture). Portraits use the warm monochrome treatment, archive photos the
  evergreen duotone. Uploads are limited to 10 MB for images and 50 MB for documents
  (`app/Constants/Uploads.php`); responsive WebP conversions are generated by the queue worker.
- Public page queries are cached and the cache is cleared whenever content is saved.
- The CV is view-only on the page. There is deliberately no CV download or file route, and the
  test suite fails if one is added.

Editors can do all of the above except community management, user administration and site
settings.

- **Users & roles** (`/admin/users`, admins only): every staff and community account with
  search and filters by role and community status. Invite an administrator or editor by name,
  email and role; they receive a set-password link (the same password-broker token as "forgot
  password", so no password is ever shown or emailed) and administrators are sent to set up
  two-factor authentication on first sign-in. Change roles, deactivate and reactivate staff
  accounts (a deactivated account cannot sign in and is signed out on its next request) and
  resend a pending invitation. Nobody can change their own role or deactivate themselves, and
  the last active administrator can never be demoted or deactivated. Everything is written to
  the audit log.
- **Site settings** (`/admin/settings`, admins only): the public contact email, phone and
  address (shown on the About page when the profile has no contact email of its own), the
  address that receives "tribute awaiting review" and "community application received"
  notices (every admin and editor is emailed when it is blank), and the mail sender name and
  address (overriding `MAIL_FROM_*` once set). Stored in the `settings` table and cached.

## Switching the community chat to Reverb

The chat works out of the box by polling (`wire:poll` every 5 seconds). Clients always fetch
from their own message cursor, so switching the transport changes only how quickly they are
nudged; every access and reliability rule stays the same.

1. Install Reverb and the browser client:
   ```bash
   composer require laravel/reverb
   php artisan install:broadcasting      # publishes config/reverb.php and routes/channels.php
   npm install laravel-echo pusher-js
   ```
2. In `.env`:
   ```
   BROADCAST_CONNECTION=reverb
   COMMUNITY_CHAT_TRANSPORT=broadcast
   REVERB_APP_ID=... REVERB_APP_KEY=... REVERB_APP_SECRET=...
   REVERB_HOST=profmokhidir.com REVERB_PORT=443 REVERB_SCHEME=https
   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}" VITE_REVERB_HOST="${REVERB_HOST}"
   VITE_REVERB_PORT="${REVERB_PORT}" VITE_REVERB_SCHEME="${REVERB_SCHEME}"
   ```
3. Authorise the private channel in `routes/channels.php` with the same check the chat
   middleware uses (`ChatSettings::BROADCAST_CHANNEL_PREFIX` + channel id):
   ```php
   Broadcast::channel('community.chat.{channelId}', fn (\App\Models\User $user, int $channelId) => $user->canAccessChat());
   ```
4. Create `window.Echo` on the chat page only. Add the Echo bootstrap to
   `resources/js/chat.js` (it is loaded only by the chat layout) and rebuild with `npm run build`.
   `chat.js` already subscribes to `community.chat.{id}` when
   `config.transport === 'broadcast'` and `window.Echo` exists; `App\Events\ChatMessageChanged`
   is already broadcast after commit.
5. Run the Reverb server (`php artisan reverb:start`) under Supervisor and proxy
   `/app` websocket traffic to it from Nginx. Keep polling as a fallback by leaving
   `COMMUNITY_CHAT_POLL_INTERVAL` at its default.

To go back, set `COMMUNITY_CHAT_TRANSPORT=poll`.

## Deployment on a standard Laravel host

Any host that runs PHP 8.3+, MySQL 8, Composer, Node and a long-running queue worker is enough.

**Environment.** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://profmokhidir.com`,
the production database, `SESSION_DRIVER=database`, `CACHE_STORE=database`,
`QUEUE_CONNECTION=database` (or redis) and a real mailer (`MAIL_MAILER=smtp` or a provider
driver) with `MAIL_FROM_ADDRESS` set to an address on the site's domain. Mail is used for
email verification, password resets, "tribute awaiting review" notices to staff and community
application decisions.

**Build and release.**

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link           # once per server: public/storage -> storage/app/public
php artisan optimize               # config, route, view and event caches
php artisan db:seed --class=RolesAndPermissionsSeeder   # first deploy only
```

Do **not** run the full `DatabaseSeeder` in production; it creates placeholder content.

**First administrator.** With the mailer configured, run

```bash
php artisan admin:create-first              # asks for the name and email
php artisan admin:create-first --name="Site Owner" --email=owner@profmokhidir.com
```

It syncs the roles and permissions (so the `RolesAndPermissionsSeeder` step above is optional),
creates the account as an administrator and emails a set-password link; no password is generated
or printed. The link expires after 60 minutes (`php artisan queue:work` must be running if mail
is queued); an existing admin can resend it from **Users & roles**. The command refuses to run
once an active administrator exists (`--force` overrides). After setting their password the
person signs in and is asked to set up two-factor authentication at `/account` before `/admin`
opens. Further staff are invited from **Users & roles**, and the contact details, notification
address and mail sender are set on **Site settings**.

**Queue worker.** Mail notifications and media conversions are queued. Run
`php artisan queue:work --tries=3 --max-time=3600` under Supervisor (or the host's
"daemon"/"worker" feature) and restart it on every deploy with `php artisan queue:restart`.

**Scheduler.** Add one cron entry: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`.
It runs the hourly chat-upload clean-up, the nightly analytics rollup and retention job
(00:30) and the weekly GeoLite2 update (Wednesdays 03:15), and is there for backups and
housekeeping (`queue:prune-failed`, `auth:clear-resets`) when they are added.

**Storage.** `storage/app/public` holds public media (served through the symlink);
`storage/app/private/media` holds members-only teaching files and is never web-served.
Both must be writable by PHP and must persist across deploys. Set `upload_max_filesize`
and `post_max_size` to at least 64M for document uploads.

**Backups.** Back up the database and `storage/app` daily. Either use the host's managed
backups or install `spatie/laravel-backup` and schedule `backup:run` and `backup:clean`
(the scheduler cron above). Keep a copy off the server.

**Security.** Serve over HTTPS only; `/admin` and `/community/chat` are disallowed in
`robots.txt`; admin sessions require confirmed two-factor authentication; login is rate
limited; tributes and community sign-ups are rate limited per address and protected by a
honeypot. `.env` is never committed.

## Visitor analytics (off until the client approves it)

The site has its own first-party, cookieless analytics: visitors, countries and the pages that
hold attention, on the dashboard's **Audience** section and at `/admin/analytics` (admins and
editors, permission `view analytics`). It is **off by default** and records nothing until an
administrator switches on **Site settings → Visitor analytics**. While it is on, the footer links
to a `/privacy` page that explains it; while it is off that page does not exist.

How it works:

- `resources/js/analytics.js` is loaded only on public pages, only while analytics is on, and
  never for signed-in staff or browsers that send Do Not Track / Global Privacy Control. It
  sends one page view and, when the tab is hidden or the page left, the engaged time (tab in
  view and the visitor active within 30 seconds) and scroll depth (25/50/75/100%) with
  `navigator.sendBeacon` to `POST /a/collect`.
- The endpoint is rate limited, CSRF-exempt, validates every field and ignores bots (user
  agent and missing JavaScript signals), staff, DNT/GPC and excluded paths (`/admin`,
  `/community/chat`, `/account`, sign-in screens).
- No cookies and no personal data: unique visitors are an HMAC of IP + user agent + site id
  under a salt that is replaced every day (the previous day's salt is deleted), so a visitor
  cannot be followed from one day to the next. The IP address and user agent are never stored.
- Country: the `CF-IPCountry` header when `ANALYTICS_BEHIND_CLOUDFLARE=true`, otherwise a local
  MaxMind GeoLite2 Country database, otherwise "Unknown". For the database, create a free
  MaxMind account, put the licence key in `.env` as `MAXMIND_LICENSE_KEY` and run
  `php artisan analytics:update-geoip` once (the scheduler then refreshes it weekly). The file
  lives in `storage/app/geoip/` and is not committed. Behind a proxy or Cloudflare, configure
  Laravel's trusted proxies so `request()->ip()` is the visitor's address.
- Raw page views (`analytics_events`) are rolled up nightly by `App\Jobs\RollUpAnalytics` into
  `analytics_daily_*` tables and deleted after 30 days; the daily rollups are kept 25 months.
- The first deploy after this change must run `php artisan db:seed --class=RolesAndPermissionsSeeder`
  (or `admin:create-first`) to add the `view analytics` permission to admins and editors.

Locally, `php artisan db:seed --class=AnalyticsDemoSeeder` (also part of `DatabaseSeeder`) fills
90 days of realistic demo figures so the report can be reviewed; it refuses to run outside the
`local` environment. To see the live script on the Valet site, switch analytics on in Site
settings and browse while signed out. The world map's outlines come from `@svg-maps/world`
(CC BY 4.0), simplified by `npm run world-map` into `resources/data/world-map.json`.

**Legal note.** The design is meant to need no consent banner: it sets no cookies, stores
nothing on the visitor's device and keeps no personal data. Whether that is enough depends on
the client's jurisdiction and its rules for websites (for example the GDPR and ePrivacy rules in
Europe, PECR in the UK, or local law in Sudan and the Gulf). **The client should confirm with
their own legal advice** that no banner or further notice is needed before analytics is
switched on, and approve the wording of the Privacy page.

## Tests and QA

```bash
php artisan test               # full PHPUnit suite on profmokhidir_testing (RefreshDatabase)
npm run qa:smoke               # Playwright: every public page at 360/768/1440px, screenshots in storage/qa/
npm run a11y                   # axe-core WCAG 2.2 AA + heading order + keyboard walk + reduced motion
npm run perf                   # Lighthouse (mobile) on home, About, Publications, Pictures
```

The smoke test fails on console errors, failed same-origin requests, broken internal links or
horizontal scroll. Broken *external* links are warnings by default because the placeholder
content links to example DOIs; set `SMOKE_EXTERNAL=1` to make them fatal once real content is in.
All three scripts run against the Valet site by default (`SMOKE_BASE_URL`, `A11Y_BASE_URL`,
`PERF_BASE_URL` override it) and use Google Chrome.

The brand audit (`tests/Unit/Design/BrandAuditTest.php`, rules in `app/Constants/BrandAudit.php`)
runs with the suite and fails on any colour outside the nine brand tokens, any font outside the
three brand fonts, any bounce/spin/flash animation and any gradient other than the brass glow,
in the source stylesheets, scripts, Blade templates and the built CSS.

## Open client decisions

These were left open in the client brief and still need an answer before launch:

1. **Section names.** The navigation currently reads About, CV & Achievements, Publications,
   Awards, Videos, Pictures, Teaching, Commentary, Community, Other Information (footer) and
   Tributes. Confirm the labels and page titles (`app/Constants/Sections.php`), whether
   "Commentary Corner" and "Other Information" keep those names, and whether the Tributes
   section stays (it comes from the brand mock, not the original ten-section brief).
2. **Contact details.** No public contact is shown yet: the About page's "Contact" block is
   empty until a public email and note are entered in the admin profile form, and the
   `MAIL_FROM_ADDRESS` is still `hello@example.com`. Decide which address (if any) appears
   publicly, who receives tribute and community notifications, and the sender address.
3. **Community eligibility and moderation.** The "Who may join", conduct and privacy texts in
   `app/Constants/CommunityCopy.php` are drafts. Confirm who qualifies, who reviews
   applications (admins only at present), the suspension and removal policy, whether reported
   messages are removed automatically or only after review, and whether a single "General"
   channel is enough.
4. **Hosting.** Choose the host and domain setup, the transactional email provider, whether to
   run Reverb for the chat (see above) or keep polling, and who holds the backups.
5. **Analytics.** The brief says no visitor tracking until requirements and notices are agreed.
   A first-party, cookieless analytics is built but **switched off** (see "Visitor analytics"
   above). Before switching it on the client must approve collecting it at all, what is
   recorded and the retention periods, the Privacy page wording and footer link, the country
   source (Cloudflare or a MaxMind GeoLite2 account, whose licence terms apply), and confirm
   with their own legal advice that no consent banner is needed.
6. **Content.** Every seeded record is placeholder text or a generated image. Approved
   biography, CV entries, publication list, awards, photographs (with permission notes),
   videos, teaching files, commentaries and other items are needed, together with the portrait
   for the home page and the share card.
