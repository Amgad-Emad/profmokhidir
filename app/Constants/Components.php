<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The catalogue of Blade components the site is built from, with the usage
 * note each one carries on the style guide (/styleguide, local only). Every
 * component in resources/views/components has an entry; the style guide test
 * fails when one is missing, so the catalogue cannot drift from the code.
 */
final class Components
{
    public const string GROUP_STRUCTURE = 'structure';

    public const string GROUP_TYPOGRAPHY = 'typography';

    public const string GROUP_CONTENT = 'content';

    public const string GROUP_MEDIA = 'media';

    public const string GROUP_WAYFINDING = 'wayfinding';

    public const string GROUP_FORMS = 'forms';

    public const string GROUP_ADMIN = 'admin';

    /**
     * Group key => heading on the style guide, in display order.
     *
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            self::GROUP_STRUCTURE => 'Page structure',
            self::GROUP_TYPOGRAPHY => 'Typography and ornament',
            self::GROUP_CONTENT => 'Content patterns',
            self::GROUP_MEDIA => 'Photography and video',
            self::GROUP_WAYFINDING => 'Wayfinding',
            self::GROUP_FORMS => 'Forms and actions',
            self::GROUP_ADMIN => 'Admin dashboard',
        ];
    }

    /**
     * Every component: its tag, the Blade view that renders it (relative to
     * resources/views/components), its group, what it is for and how to use it.
     *
     * @return list<array{tag: string, view: string, group: string, purpose: string, usage: string}>
     */
    public static function all(): array
    {
        return [
            // Page structure
            ['tag' => 'x-layouts.public', 'view' => 'layouts/public', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Every public page: fixed glass header, breadcrumbs, footer, SEO head.', 'usage' => 'Pass title, description and schema. Set breadcrumb-tone="ivory" when the page opens on evergreen so the trail sits on the same surface. Never loads Livewire.'],
            ['tag' => 'x-layouts.admin', 'view' => 'layouts/admin', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'The dashboard shell: evergreen sidebar, top bar with breadcrumbs and the command palette, linen page, toasts, Livewire assets.', 'usage' => 'Only /admin and /account use it. Open every screen with <x-admin.page-header>; pass crumb to name the record in the breadcrumbs.'],
            ['tag' => 'x-layouts.guest', 'view' => 'layouts/guest', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Sign-in and the other Fortify screens as a private reading room: an evergreen panel with the archive portrait and the epigraph beside (or above) an ivory column with the stacked lockup, the form and the seal.', 'usage' => 'Pass screen (an App\\Constants\\AuthScreens key) for the title, intro, action and busy label; :intro="false" to write the intro in the slot. One form per screen, in the 400px column, no card.'],
            ['tag' => 'x-layouts.chat', 'view' => 'layouts/chat', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'The members-only chat with its own glass bar.', 'usage' => 'Only /community/chat. Membership is checked on every request.'],
            ['tag' => 'x-account.sign-out', 'view' => 'account/sign-out', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Sign out: a POST form with the CSRF token to the logout route; after it the home page shows a quiet notice.', 'usage' => 'An <x-button> by default (variant, size); inside a menu pass `item-class` to draw it as that menu\'s item. Used in the public account menu, the chat and admin menus, the admin sidebar foot and the account page.'],
            ['tag' => 'x-chat.member-menu', 'view' => 'chat/member-menu', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'The member\'s menu in the chat bar: initials in a hairline ring, opening name, affiliation, Account and Sign out.', 'usage' => 'Rendered by the chat layout only; pass the signed-in user. Escape or a click outside closes it.'],
            ['tag' => 'x-layouts.error', 'view' => 'layouts/error', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Error pages on evergreen with the mark, a search box and the chapters.', 'usage' => 'Pass the status code; copy comes from App\\Constants\\ErrorPages. Works without the database.'],
            ['tag' => 'x-site.header', 'view' => 'site/header', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'The masthead: glass bar that compacts on scroll, five chapter mega menus with a featured item each, the utility line, the full-screen menu and the search dialog.', 'usage' => 'Rendered by the public layout only; pass `hero` on pages that open with the evergreen hero. Chapters come from App\\Services\\MastheadService (App\\Constants\\Chapters), item names from NavigationService.'],
            ['tag' => 'x-site.footer', 'view' => 'site/footer', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Evergreen footer: stacked lockup, closing line, the sections.', 'usage' => 'Rendered by the public layout only. The closing line is a tagline constant.'],
            ['tag' => 'x-seo', 'view' => 'seo', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Title, description, canonical, Open Graph and JSON-LD in the head.', 'usage' => 'Used by the public layout. Pass noindex for transactional pages.'],
            ['tag' => 'x-fonts', 'view' => 'fonts', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Preloads the display font and links the self-hosted stylesheet.', 'usage' => 'Once per layout, before @vite. Never replace the font files with Google Fonts builds.'],
            ['tag' => 'x-brand.favicons', 'view' => 'brand/favicons', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Favicon and touch icon links built from the mark.', 'usage' => 'Once per layout.'],
            ['tag' => 'x-theme.head', 'view' => 'theme/head', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'Theme plumbing in the head: CSRF token, theme-color metas for both schemes and the inline script that settles the stored preference before the first paint.', 'usage' => 'Once per layout, before @vite. The layout prints the <html> attributes from App\\Services\\ThemeService::htmlAttributes; pass light/dark brand colour names for the browser chrome.'],
            ['tag' => 'x-duotone-filter', 'view' => 'duotone-filter', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'The SVG filter behind the evergreen-to-ivory archive treatment.', 'usage' => 'Once per layout, directly after <body>; .photo-archive references it by id.'],

            // Typography and ornament
            ['tag' => 'x-brand.mark', 'view' => 'brand/mark', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'The MK mark in its brass hairline ring (a solid disc below 32px).', 'usage' => 'Pass size in pixels and tone (ivory on dark, evergreen on light). Clear space is built in. Never fill, recolour, tilt or add effects.'],
            ['tag' => 'x-brand.lockup', 'view' => 'brand/lockup', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'Mark with the name: stacked (footer, sign-in) or horizontal (header, sidebar).', 'usage' => 'Pass href to make it a link. Use compact in narrow bars so the name never wraps; label-tone="champagne" on the glass header.'],
            ['tag' => 'x-chapter-opening', 'view' => 'chapter-opening', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'Opens every section page: Roman numeral and label, a drawing brass rule, the heading.', 'usage' => 'The one opener for section index and detail pages (number from NavigationService::chapter). Put an <x-epigraph> in the epigraph slot and the intro in the default slot.'],
            ['tag' => 'x-section-header', 'view' => 'section-header', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'A lighter opener for sub-sections and utility pages: label, rule, heading.', 'usage' => 'Inside a page (chat, search, the tribute steps), never as the opener of a section page. tone="ivory" on evergreen.'],
            ['tag' => 'x-epigraph', 'view' => 'epigraph', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'A short italic quotation set right on a narrow measure, attribution in small caps.', 'usage' => 'One per page at most, under a chapter opening. Keep it to a sentence.'],
            ['tag' => 'x-drop-cap', 'view' => 'drop-cap', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'Opens long prose with a three-line Cormorant initial.', 'usage' => 'Wrap the prose (class prose-bio). Only the first paragraph gets the initial; tone="ivory" on evergreen.'],
            ['tag' => 'x-ornament', 'view' => 'ornament', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'Hairline SVG ornaments (fleuron, rule-dot, double-rule) in brass.', 'usage' => 'Between entries of a roll or a book, or to close prose. Strokes only, never filled; keep to one kind per page.'],
            ['tag' => 'x-sidenote', 'view' => 'sidenote', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'A numbered note in the margin on wide screens, inline on narrow ones.', 'usage' => 'Place directly after the word it annotates inside a .sidenote-host. Number them in reading order.'],
            ['tag' => 'x-folio', 'view' => 'folio', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'A quiet running foot: section name and the Professor\'s name either side of a stone rule.', 'usage' => 'At the foot of long pages (position="bottom"). Inner pages already carry breadcrumbs, so the running head is not repeated at the top.'],
            ['tag' => 'x-seal', 'view' => 'seal', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'The mark above a short centred brass rule, closing a page.', 'usage' => 'Once, at the end of a page (or opening the tributes and thank-you pages with class seal--open). Ring only, so at least 32px.'],
            ['tag' => 'x-meta', 'view' => 'meta', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'The one metadata line: small bronze label, parts joined by a middle dot.', 'usage' => 'Pass items in reading order (type, place, date); dates become <time>, empty parts are dropped. tone="ivory" on evergreen.'],
            ['tag' => 'x-date', 'view' => 'date', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'The one way a date is written: <time> with the long format.', 'usage' => 'Never format a date by hand in a view; pass a format constant from DateFormats when the month alone is wanted.'],
            ['tag' => 'x-icon', 'view' => 'icon', 'group' => self::GROUP_TYPOGRAPHY, 'purpose' => 'Small 1.5px line icons in the current colour.', 'usage' => 'Decorative by default; pass label when the icon carries meaning. Never the only sign of a state.'],

            // Content patterns
            ['tag' => 'x-empty-state', 'view' => 'empty-state', 'group' => self::GROUP_CONTENT, 'purpose' => 'The designed empty state: short brass rule, an italic line, optional attribution.', 'usage' => 'Every list renders one when nothing is published or nothing matches. Write the line in the tagline voice.'],
            ['tag' => 'x-tribute-entry', 'view' => 'tribute-entry', 'group' => self::GROUP_CONTENT, 'purpose' => 'One tribute in the book: italic text, the name in small caps, relationship and place.', 'usage' => 'Pass the tribute and its share-card URL. Used on the book pages and the preview step.'],
            ['tag' => 'x-pagination', 'view' => 'pagination', 'group' => self::GROUP_CONTENT, 'purpose' => 'Page links in the pill style for public lists.', 'usage' => '$paginator->links(\'components.pagination\'). Lists set as a book use <x-book-pagination> instead.'],
            ['tag' => 'x-book-pagination', 'view' => 'book-pagination', 'group' => self::GROUP_CONTENT, 'purpose' => '"Page iii of xii" in small caps between previous and next.', 'usage' => '$paginator->links(\'components.book-pagination\'), for the book of remembrance only.'],
            ['tag' => 'x-skeleton', 'view' => 'skeleton', 'group' => self::GROUP_CONTENT, 'purpose' => 'Loading placeholder lines that breathe slowly.', 'usage' => 'Only while results are fetched (the search dialog). Pass lines and tone.'],
            ['tag' => 'x-alert', 'view' => 'alert', 'group' => self::GROUP_CONTENT, 'purpose' => 'An inline notice with an icon: bronze needs action, cypress done, ash neutral.', 'usage' => 'Pass a StatusTone; role="alert" for errors. Keep to one sentence.'],
            ['tag' => 'x-external-link', 'view' => 'external-link', 'group' => self::GROUP_CONTENT, 'purpose' => 'A link to another site: label, then the host, underlined in brass.', 'usage' => 'For source and reference links; opens in a new tab with the warning for screen readers.'],
            ['tag' => 'x-file-link', 'view' => 'file-link', 'group' => self::GROUP_CONTENT, 'purpose' => 'A download link: "Download · PDF · 2.1 MB".', 'usage' => 'Pass the media item; the type and size come from it. Never for the CV, which is view-only.'],

            // Photography and video
            ['tag' => 'x-photo', 'view' => 'photo', 'group' => self::GROUP_MEDIA, 'purpose' => 'A photograph with responsive WebP sources, its brand treatment and a museum label.', 'usage' => 'Pass the media item, sizes and loading. Portraits get the warm monochrome, archive photos the duotone. Every photo carries place and year; :lightbox="true" in galleries.'],
            ['tag' => 'x-museum-label', 'view' => 'museum-label', 'group' => self::GROUP_MEDIA, 'purpose' => 'The caption: title in Cormorant, place and year in small caps, an optional note.', 'usage' => 'Rendered by <x-photo>; use directly only for captions that belong to something other than a photo.'],
            ['tag' => 'x-lightbox', 'view' => 'lightbox', 'group' => self::GROUP_MEDIA, 'purpose' => 'The object view: a full-screen evergreen dialog with the photograph and its label.', 'usage' => 'Once per gallery page; photos with :lightbox="true" open in it. Escape closes, arrows move.'],
            ['tag' => 'x-video-label', 'view' => 'video-label', 'group' => self::GROUP_MEDIA, 'purpose' => 'The label line of a recording: event, place and date.', 'usage' => 'Under a recording\'s title; pass the video.'],
            ['tag' => 'x-video-player', 'view' => 'video-player', 'group' => self::GROUP_MEDIA, 'purpose' => 'A poster with a play ring; the hosted player loads only on request.', 'usage' => 'Pass the player and the title; :featured="true" for the lecture hall. Always keep the "Watch on" link beneath it.'],

            // Wayfinding
            ['tag' => 'x-site.breadcrumbs', 'view' => 'site/breadcrumbs', 'group' => self::GROUP_WAYFINDING, 'purpose' => 'Home / Section / Page in small caps, with BreadcrumbList JSON-LD.', 'usage' => 'Rendered by the public layout from the route; pass breadcrumb-title when the page title differs.'],
            ['tag' => 'x-filter-pills', 'view' => 'filter-pills', 'group' => self::GROUP_WAYFINDING, 'purpose' => 'A row of filter links in the pill style; the current one is filled.', 'usage' => 'Plain links that change the query string. Give it a label; add an "All" option with all-url.'],
            ['tag' => 'x-theme.switch', 'view' => 'theme/switch', 'group' => self::GROUP_WAYFINDING, 'purpose' => 'The appearance control: a sun/moon hairline button opening a three-item menu, System, Light and Dark.', 'usage' => 'In the masthead, the admin bars and on the account page. Driven by resources/js/theme.js; inherits the surrounding ink.'],
            ['tag' => 'x-reading-progress', 'view' => 'reading-progress', 'group' => self::GROUP_WAYFINDING, 'purpose' => 'A 1px brass line under the header that grows as the reader scrolls.', 'usage' => 'Long reading pages only (About, CV, a commentary). Pass the selector of the text it measures.'],

            // Forms and actions
            ['tag' => 'x-button', 'view' => 'button', 'group' => self::GROUP_FORMS, 'purpose' => 'The pill button: outline on light surfaces, ivory filled or outline on evergreen, evergreen filled (variant="primary") for the one main action of a form on ivory; the quiet variant is a text action.', 'usage' => 'Page actions use the default; a sign-in or application form uses variant="primary" with busy for its submitting label; "Back" and "Cancel" use variant="outline" (quiet). Pass href for a link.'],

            ['tag' => 'x-form.field', 'view' => 'form/field', 'group' => self::GROUP_FORMS, 'purpose' => 'The one field anatomy: label in the label style, the hint directly under it starting with "Required" or "Optional", the control, then the counter and the error.', 'usage' => 'Used by every x-form control; reach for it directly only to wrap a control of your own. Pass the array from App\Services\FormFieldService::describe; as="fieldset" for groups.'],
            ['tag' => 'x-form.input', 'view' => 'form/input', 'group' => self::GROUP_FORMS, 'purpose' => 'A single-line field (text, email, url, tel, number, search) in the shared frame, 48px tall with 18px text.', 'usage' => 'Plain forms pass name, Livewire forms model (wire:model.live.blur, or live for search boxes). required prints "Required", otherwise "Optional" (:optional="false" for filters). size="compact" in filter bars, size="display" for the search fields. Other attributes land on the <input>.'],
            ['tag' => 'x-form.textarea', 'view' => 'form/textarea', 'group' => self::GROUP_FORMS, 'purpose' => 'A multi-line field that grows with its text, with a counter that speaks up near the limit.', 'usage' => 'Pass rows and maxlength. mono only for embed codes and other technical text. For editorial text use <x-form.editor>.'],
            ['tag' => 'x-form.select', 'view' => 'form/select', 'group' => self::GROUP_FORMS, 'purpose' => 'A styled native select with a hairline chevron.', 'usage' => 'Options as value => label (or groups). Pass placeholder for an empty first option. For long lists people search, use <x-form.combobox>.'],
            ['tag' => 'x-form.combobox', 'view' => 'form/combobox', 'group' => self::GROUP_FORMS, 'purpose' => 'A searchable choice of one for long lists: type to narrow, arrows to move, Enter to choose.', 'usage' => 'Options as value => label; albums in the media library. Falls back to a native select without JavaScript.'],
            ['tag' => 'x-form.checkbox', 'view' => 'form/checkbox', 'group' => self::GROUP_FORMS, 'purpose' => 'A 20px square on the strong rule that fills with the ink and an ivory tick.', 'usage' => 'The label is the slot. Agreements pass required ("Tick this box to continue."). For a setting that switches on or off, use <x-form.toggle>.'],
            ['tag' => 'x-form.radio-group', 'view' => 'form/radio-group', 'group' => self::GROUP_FORMS, 'purpose' => 'A choice of one as a fieldset: a list of radios, or variant="segmented" pills.', 'usage' => 'Options as value => label (or [label, hint]). Status uses the segmented pills through <x-admin.status-field>; catalogue filters use the list.'],
            ['tag' => 'x-form.toggle', 'view' => 'form/toggle', 'group' => self::GROUP_FORMS, 'purpose' => 'An on/off switch (role="switch") with the word On or Off beside the track.', 'usage' => 'For one setting that takes effect as a whole, such as Featured on the wall. Never for agreements.'],
            ['tag' => 'x-form.tags', 'view' => 'form/tags', 'group' => self::GROUP_FORMS, 'purpose' => 'Short entries typed into one field and kept as removable pills.', 'usage' => 'Expertise (an array) and commentary topics (separator=","). Enter or a comma adds; Backspace takes the last one back. Pass suggestions for known entries.'],
            ['tag' => 'x-form.date', 'view' => 'form/date', 'group' => self::GROUP_FORMS, 'purpose' => 'A calendar date with the browser\'s own picker, stored as Y-m-d.', 'usage' => 'Pass min and max to bound it. Takes every prop of <x-form.input>.'],
            ['tag' => 'x-form.year', 'view' => 'form/year', 'group' => self::GROUP_FORMS, 'purpose' => 'A four-digit year with a numeric keypad and a range check.', 'usage' => 'From 1900 to next year unless min and max say otherwise; the message names the range.'],
            ['tag' => 'x-form.password', 'view' => 'form/password', 'group' => self::GROUP_FORMS, 'purpose' => 'A password field with show/hide, a Caps Lock warning and, with strength, a length-only strength line.', 'usage' => 'autocomplete="current-password" to sign in, "new-password" with :strength="true" to choose one. Confirmation fields take no strength.'],
            ['tag' => 'x-form.otp', 'view' => 'form/otp', 'group' => self::GROUP_FORMS, 'purpose' => 'Six digit boxes for a one-time code: auto-advance, Backspace, arrows, paste and autofill of a whole code.', 'usage' => 'The two-factor challenge and confirming two-factor on the account page. Pass hidden bindings to disable it while a recovery code is used.'],
            ['tag' => 'x-form.upload', 'view' => 'form/upload', 'group' => self::GROUP_FORMS, 'purpose' => 'A drop zone with a progress hairline, then the file as a card with preview, Replace and Remove; images prompt for alt text.', 'usage' => 'Pass model (a Livewire upload), accept and the size hint; media, href and remove for the attached file; image and alt for photographs. Files go through MediaService.'],
            ['tag' => 'x-form.editor', 'view' => 'form/editor', 'group' => self::GROUP_FORMS, 'purpose' => 'The rich text editor: labelled toolbar with shortcut tooltips, a checked link dialog, a word count and one focus frame.', 'usage' => 'Biography and commentary text, bound to a property holding HTML. The server sanitises it again on save.'],
            ['tag' => 'x-form.error-summary', 'view' => 'form/error-summary', 'group' => self::GROUP_FORMS, 'purpose' => 'One calm note at the top of a long form that failed, each message linking to its field.', 'usage' => 'Once per long form, above the fields. In dashboard forms pass after-save; it appears when a save fails. Pass targets when a key links elsewhere (code to code-1), except for keys the page words itself.'],

            // Admin
            ['tag' => 'x-admin.sidebar', 'view' => 'admin/sidebar', 'group' => self::GROUP_ADMIN, 'purpose' => 'Dashboard navigation from App\\Constants\\AdminNavigation: collapsible groups, counts on queues, icons when collapsed.', 'usage' => 'Rendered by the admin layout from AdminShellService::sidebar(). The active item carries a 1px brass rule.'],
            ['tag' => 'x-admin.page-header', 'view' => 'admin/page-header', 'group' => self::GROUP_ADMIN, 'purpose' => 'Opens every admin screen: group eyebrow, heading, intro, actions.', 'usage' => 'Put the screen\'s primary action in the slot. One per screen.'],
            ['tag' => 'x-admin.stat-card', 'view' => 'admin/stat-card', 'group' => self::GROUP_ADMIN, 'purpose' => 'A dashboard figure in Cormorant lining figures with its label.', 'usage' => 'Pass count and either noun (pluralised) or caption (as written). Figures sit on a shared baseline across a row.'],
            ['tag' => 'x-admin.status-badge', 'view' => 'admin/status-badge', 'group' => self::GROUP_ADMIN, 'purpose' => 'A pill badge with icon and text for a status.', 'usage' => 'Pass a StatusTone; the icon means the state is never shown by colour alone.'],
            ['tag' => 'x-admin.flash', 'view' => 'admin/flash', 'group' => self::GROUP_ADMIN, 'purpose' => 'The outcome of the last action, kept hidden in the page; the toast stack announces it.', 'usage' => 'Under the page header of every screen. Livewire screens notify with the NotifiesEditors trait.'],
            ['tag' => 'x-admin.confirm-dialog', 'view' => 'admin/confirm-dialog', 'group' => self::GROUP_ADMIN, 'purpose' => 'A modal confirmation with focus trapped.', 'usage' => 'For destructive actions only. Name the Livewire methods for confirm and cancel.'],
            ['tag' => 'x-admin.image-grid', 'view' => 'admin/image-grid', 'group' => self::GROUP_ADMIN, 'purpose' => 'Thumbnails of an item\'s photographs with reorder and remove.', 'usage' => 'Used by forms with the ManagesImages trait.'],
            ['tag' => 'x-admin.image-details', 'view' => 'admin/image-details', 'group' => self::GROUP_ADMIN, 'purpose' => 'Alt text, caption, place, year and credit for one photograph.', 'usage' => 'Every photograph needs alt text and a caption with place and year before it is published.'],
            ['tag' => 'x-admin.pagination', 'view' => 'admin/pagination', 'group' => self::GROUP_ADMIN, 'purpose' => 'Livewire-aware pagination in the pill style.', 'usage' => '$items->links(\'components.admin.pagination\').'],
            ['tag' => 'x-admin.topbar', 'view' => 'admin/topbar', 'group' => self::GROUP_ADMIN, 'purpose' => 'The admin top bar: breadcrumbs, the command palette button, theme, View site, the user menu.', 'usage' => 'Rendered by the admin layout.'],
            ['tag' => 'x-admin.user-menu', 'view' => 'admin/user-menu', 'group' => self::GROUP_ADMIN, 'purpose' => 'The signed-in user\'s menu: account, keyboard shortcuts, sign out.', 'usage' => 'In the top bar only.'],
            ['tag' => 'x-admin.command-palette', 'view' => 'admin/command-palette', 'group' => self::GROUP_ADMIN, 'purpose' => 'Ctrl/Cmd+K: jump to any screen, start something new, find content by title.', 'usage' => 'Rendered once by the admin layout; entries from AdminShellService::paletteEntries().'],
            ['tag' => 'x-admin.toasts', 'view' => 'admin/toasts', 'group' => self::GROUP_ADMIN, 'purpose' => 'The toast stack: confirmations in a polite live region, errors in an assertive one, Undo after a move to the trash.', 'usage' => 'Rendered once by the admin layout; screens dispatch AdminUx::EVENT_TOAST through the NotifiesEditors trait.'],
            ['tag' => 'x-admin.shortcuts-sheet', 'view' => 'admin/shortcuts-sheet', 'group' => self::GROUP_ADMIN, 'purpose' => 'The keyboard shortcuts sheet opened with ?.', 'usage' => 'Rendered once by the admin layout from AdminUx::shortcuts().'],
            ['tag' => 'x-admin.leave-guard', 'view' => 'admin/leave-guard', 'group' => self::GROUP_ADMIN, 'purpose' => 'Asks "Leave without saving?" before following a link with unsaved changes.', 'usage' => 'Rendered once by the admin layout; edit screens report their state.'],
            ['tag' => 'x-admin.edit-layout', 'view' => 'admin/edit-layout', 'group' => self::GROUP_ADMIN, 'purpose' => 'An edit screen: named sections with a sticky section nav, the placeholder notice and the save bar.', 'usage' => 'Wrap every content form; pass editor (ContentForm::editorState()) and sections (id => label).'],
            ['tag' => 'x-admin.form-section', 'view' => 'admin/form-section', 'group' => self::GROUP_ADMIN, 'purpose' => 'One named part of a long form, with its heading and intro; its body is a small form that saves the record.', 'usage' => 'Inside <x-admin.edit-layout>; :form="false" for sections whose controls save themselves.'],
            ['tag' => 'x-admin.save-bar', 'view' => 'admin/save-bar', 'group' => self::GROUP_ADMIN, 'purpose' => 'The sticky save bar: status, save state, History, Preview, Save draft and Publish (or Unpublish and Update).', 'usage' => 'Rendered by <x-admin.edit-layout>.'],
            ['tag' => 'x-admin.revisions', 'view' => 'admin/revisions', 'group' => self::GROUP_ADMIN, 'purpose' => 'Version history of a record in a drawer, with Load this version.', 'usage' => 'Once per edit screen, after the layout.'],
            ['tag' => 'x-admin.drawer', 'view' => 'admin/drawer', 'group' => self::GROUP_ADMIN, 'purpose' => 'A side drawer dialog with a title and Close; focus trapped.', 'usage' => 'For details panels (image details, version history). Name the Livewire method that closes it.'],
            ['tag' => 'x-admin.row-menu', 'view' => 'admin/row-menu', 'group' => self::GROUP_ADMIN, 'purpose' => 'The actions of one table row in a small menu.', 'usage' => 'Last column of an admin table; destructive items last.'],
            ['tag' => 'x-admin.bulk-bar', 'view' => 'admin/bulk-bar', 'group' => self::GROUP_ADMIN, 'purpose' => 'The sticky bar of actions for ticked rows: Publish, Unpublish, Move to trash.', 'usage' => 'Under an admin table with checkboxes.'],
            ['tag' => 'x-admin.filter-chips', 'view' => 'admin/filter-chips', 'group' => self::GROUP_ADMIN, 'purpose' => 'One filter as a row of toggle chips.', 'usage' => 'For filters with a handful of options; longer lists use a select.'],
            ['tag' => 'x-admin.list-empty', 'view' => 'admin/list-empty', 'group' => self::GROUP_ADMIN, 'purpose' => 'The empty state of an admin list: what goes here and the button that adds the first one.', 'usage' => 'When a list is empty; with filters it offers to clear them.'],
            ['tag' => 'x-admin.attention-card', 'view' => 'admin/attention-card', 'group' => self::GROUP_ADMIN, 'purpose' => 'A "Needs your attention" card: count, title, why, records and the action.', 'usage' => 'On the dashboard home, from DashboardService::attentionCards().'],
            ['tag' => 'x-admin.launch-checklist', 'view' => 'admin/launch-checklist', 'group' => self::GROUP_ADMIN, 'purpose' => 'The "Before launch" checklist with progress and a fix link per check.', 'usage' => 'On the dashboard home, from LaunchReadinessService.'],
            ['tag' => 'x-site.preview-banner', 'view' => 'site/preview-banner', 'group' => self::GROUP_STRUCTURE, 'purpose' => 'The notice on a public page opened from a signed preview link.', 'usage' => 'Rendered by the public layout while a preview is shown.'],
        ];
    }

    /**
     * @return array<string, list<array{tag: string, view: string, group: string, purpose: string, usage: string}>>
     */
    public static function byGroup(): array
    {
        $grouped = array_fill_keys(array_keys(self::groups()), []);

        foreach (self::all() as $component) {
            $grouped[$component['group']][] = $component;
        }

        return $grouped;
    }

    /**
     * @return list<string>
     */
    public static function views(): array
    {
        return array_column(self::all(), 'view');
    }
}
