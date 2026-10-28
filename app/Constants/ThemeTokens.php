<?php

declare(strict_types=1);

namespace App\Constants;

use App\Support\Colour;

/**
 * Every colour token a theme can set (Admin › Appearance), declared with the
 * same name in resources/css/tokens.css (--{key}): its group, its label in
 * plain words, where it is used, and its default in the light and the dark
 * mode, which are the brand values (the "Evergreen & Ivory" theme). The Basic
 * group is shown to everyone; the others sit under "Advanced colours". A token
 * a theme leaves unset is derived from the others by
 * App\Services\ThemePaletteService, so editing the few Basic colours keeps the
 * whole system consistent.
 *
 * aliases() lists the tokens tokens.css derives in CSS from these (translucent
 * inks of a band); fixed() lists the brand-only tokens no theme can change
 * (the MK mark's disc and letters).
 */
final class ThemeTokens
{
    public const string GROUP_BASIC = 'basic';

    public const string GROUP_TEXT = 'text';

    public const string GROUP_SURFACES = 'surfaces';

    public const string GROUP_RULES = 'rules';

    public const string GROUP_CARDS = 'cards';

    public const string GROUP_BUTTONS = 'buttons';

    public const string GROUP_BANDS = 'bands';

    public const string GROUP_HERO = 'hero';

    public const string GROUP_CHAT = 'chat';

    public const string GROUP_STATUS = 'status';

    public const string GROUP_CHARTS = 'charts';

    public const string GROUP_IMAGERY = 'imagery';

    /** The one token restricted to brass-like tones of the active palette. */
    public const string MARK_RING = 'mark-ring';

    /**
     * Group key => [label, intro], in display order.
     *
     * @return array<string, array{label: string, intro: string}>
     */
    public static function groups(): array
    {
        return [
            self::GROUP_BASIC => ['label' => 'Basic colours', 'intro' => 'The six colours the rest of the palette is built from.'],
            self::GROUP_TEXT => ['label' => 'Font colours', 'intro' => 'Headings, running text, labels, links, placeholders and text on coloured fills.'],
            self::GROUP_SURFACES => ['label' => 'Surfaces', 'intro' => 'Panels, sunken areas and the dimmed backdrop behind dialogs.'],
            self::GROUP_RULES => ['label' => 'Borders, rules and focus', 'intro' => 'Hairlines, strong rules and the keyboard focus ring.'],
            self::GROUP_CARDS => ['label' => 'Cards and inputs', 'intro' => 'Cards, form fields and what changes on a raised surface.'],
            self::GROUP_BUTTONS => ['label' => 'Buttons', 'intro' => 'The solid button and the danger button in each state. Outline, quiet and icon buttons follow the text colour.'],
            self::GROUP_BANDS => ['label' => 'Header, footer and dark bands', 'intro' => 'The header glass, the footer, the dashboard sidebar and the sign-in panel.'],
            self::GROUP_HERO => ['label' => 'Home page opening', 'intro' => 'The hero with the soft glow. Its background is the Primary colour.'],
            self::GROUP_CHAT => ['label' => 'Community chat', 'intro' => 'Your own messages and the unread line.'],
            self::GROUP_STATUS => ['label' => 'Status tones', 'intro' => 'Pending, approved and rejected badges. Each also carries an icon and a word.'],
            self::GROUP_CHARTS => ['label' => 'Charts', 'intro' => 'The analytics report: series, grid, labels and the map.'],
            self::GROUP_IMAGERY => ['label' => 'Glow, photographs and the mark', 'intro' => 'The hero glow, the archive photo duotone and the MK mark ring.'],
        ];
    }

    /**
     * Token => definition, in display order within each group.
     *
     * @return array<string, array{group: string, label: string, description: string, light: string, dark: string}>
     */
    public static function all(): array
    {
        return self::$all ??= self::build();
    }

    /** @var array<string, array{group: string, label: string, description: string, light: string, dark: string}>|null */
    private static ?array $all = null;

    /**
     * @return array<string, array{group: string, label: string, description: string, light: string, dark: string}>
     */
    private static function build(): array
    {
        $e = Brand::EVERGREEN;
        $i = Brand::IVORY;
        $c = Brand::CYPRESS;
        $b = Brand::BRASS;
        $ch = Brand::CHAMPAGNE;
        $l = Brand::LINEN;
        $s = Brand::STONE;
        $a = Brand::ASH;
        $bz = Brand::BRONZE;
        $dl = Brand::DANGER_LIGHT;
        $dd = Brand::DANGER_DARK;

        return array_map(static fn (array $token): array => [...$token, 'light' => Colour::normalise($token['light']), 'dark' => Colour::normalise($token['dark'])], [
            // Basic.
            'surface' => self::token(self::GROUP_BASIC, 'Background', 'The page itself, behind all content.', $i, $e),
            'surface-raised' => self::token(self::GROUP_BASIC, 'Raised surface', 'Cards, panels and highlighted rows that sit above the page.', $l, $c),
            'primary' => self::token(self::GROUP_BASIC, 'Primary colour', 'The home page opening, the header and footer in the light mode, and the solid button.', $e, $e),
            'accent-line' => self::token(self::GROUP_BASIC, 'Accent', 'Thin rules, the active-section line, progress bars and the glow. Labels use a darker shade where needed.', $b, $b),
            'ink' => self::token(self::GROUP_BASIC, 'Text', 'Interface text, navigation and outline buttons; the other font colours start from it.', $e, $ch),
            'danger' => self::token(self::GROUP_BASIC, 'Danger', 'Destructive actions and error messages only, never decoration.', $dl, $dd),

            // Font colours.
            'ink-strong' => self::token(self::GROUP_TEXT, 'Heading text', 'Headings and emphasised interface text.', $e, $i),
            'ink-body' => self::token(self::GROUP_TEXT, 'Body text', 'Running text in paragraphs.', $e, $ch),
            'ink-muted' => self::token(self::GROUP_TEXT, 'Muted text', 'Descriptions, hints and metadata (dates, venues).', Colour::mix($a, 70, $e), $s),
            'ink-quiet' => self::token(self::GROUP_TEXT, 'Quiet text', 'Marginal notes, counters and the quietest metadata.', $a, Colour::alpha($s, 88)),
            'accent-text' => self::token(self::GROUP_TEXT, 'Label text', 'Small uppercase labels, eyebrows and caption details.', $bz, $b),
            'link' => self::token(self::GROUP_TEXT, 'Link text', 'Links inside running text.', $e, $ch),
            'link-hover' => self::token(self::GROUP_TEXT, 'Link hover', 'A link in running text under the pointer.', $e, $i),
            'placeholder' => self::token(self::GROUP_TEXT, 'Placeholder text', 'The sample text inside an empty form field.', $a, Colour::alpha($s, 88)),
            'ink-on-band' => self::token(self::GROUP_TEXT, 'Text on primary', 'Text on the header, footer, sidebar and the home page opening.', $ch, $ch),
            'ink-on-band-strong' => self::token(self::GROUP_TEXT, 'Headings on primary', 'Headings and the strongest text on those primary bands.', $i, $i),
            'accent-ink' => self::token(self::GROUP_TEXT, 'Text on accent', 'Text set on an accent fill. The brand never fills with the accent, so this is reserved and checked for contrast.', $e, $e),
            'danger-ink' => self::token(self::GROUP_TEXT, 'Text on danger', 'The label of the solid danger button in a confirmation dialog.', $i, $e),
            'selection' => self::token(self::GROUP_TEXT, 'Selection background', 'Behind text the reader has selected.', $l, $c),
            'selection-ink' => self::token(self::GROUP_TEXT, 'Selection text', 'Selected text.', $e, $i),

            // Surfaces.
            'surface-panel' => self::token(self::GROUP_SURFACES, 'Panel surface', 'Dialogs, drawers and dashboard panels.', $i, $c),
            'surface-sunken' => self::token(self::GROUP_SURFACES, 'Sunken surface', 'Wells and the dashboard canvas behind panels.', $l, $e),
            'surface-overlay' => self::token(self::GROUP_SURFACES, 'Dialog backdrop', 'The translucent layer behind a dialog or the lightbox.', Colour::alpha($e, 60), Colour::alpha($e, 78)),
            'surface-scrim' => self::token(self::GROUP_SURFACES, 'Image scrim', 'The shade under captions set over photographs.', Colour::alpha($e, 70), Colour::alpha($e, 70)),

            // Borders, rules and focus.
            'rule' => self::token(self::GROUP_RULES, 'Hairline rule', 'Dividers and the borders of lists and tables.', $s, Colour::alpha($b, 35)),
            'rule-strong' => self::token(self::GROUP_RULES, 'Strong rule', 'Control outlines and rules that must be seen (3:1).', $a, $b),
            'focus' => self::token(self::GROUP_RULES, 'Focus ring', 'The keyboard focus ring around buttons and links.', $bz, $b),

            // Cards and inputs.
            'card-surface' => self::token(self::GROUP_CARDS, 'Card background', 'Cards across the site and the dashboard.', $l, $c),
            'card-border' => self::token(self::GROUP_CARDS, 'Card border', 'The hairline around a card.', $s, Colour::alpha($b, 35)),
            'accent-text-on-raised' => self::token(self::GROUP_CARDS, 'Label text on cards', 'Labels on raised surfaces, where the accent may be too faint.', $bz, $ch),
            'focus-on-raised' => self::token(self::GROUP_CARDS, 'Focus ring on cards', 'The focus ring on raised surfaces.', $bz, $ch),
            'rule-strong-on-raised' => self::token(self::GROUP_CARDS, 'Strong rule on cards', 'Control outlines and field borders on raised surfaces.', $a, Colour::alpha($ch, 66)),
            'surface-field' => self::token(self::GROUP_CARDS, 'Input background', 'Text fields, selects and text areas on the page.', $i, Colour::alpha($i, 6)),
            'surface-field-on-raised' => self::token(self::GROUP_CARDS, 'Input background on cards', 'Form fields on raised surfaces and panels.', $i, $e),
            'field-border' => self::token(self::GROUP_CARDS, 'Input border', 'The frame of a form field on the page.', $a, $b),
            'field-focus-line' => self::token(self::GROUP_CARDS, 'Input focus line', 'The line along the foot of a field while typing.', $e, $i),

            // Buttons.
            'fill' => self::token(self::GROUP_BUTTONS, 'Solid button', 'The one solid button of a form, the current filter and pressed tools.', $e, $i),
            'fill-ink' => self::token(self::GROUP_BUTTONS, 'Solid button text', 'Text and icons on the solid button.', $i, $e),
            'fill-deep' => self::token(self::GROUP_BUTTONS, 'Solid button hover', 'The solid button under the pointer or focused.', Colour::mix($e, 78, Colour::BLACK), Colour::mix($i, 45, $l)),
            'fill-pressed' => self::token(self::GROUP_BUTTONS, 'Solid button pressed', 'The solid button while pressed.', Colour::mix($e, 62, Colour::BLACK), $l),
            'fill-hover' => self::token(self::GROUP_BUTTONS, 'Tool hover', 'A pressed toolbar tool under the pointer.', $c, $l),
            'danger-surface' => self::token(self::GROUP_BUTTONS, 'Danger button', 'The solid danger button inside a confirmation dialog.', $dl, $dd),
            'danger-surface-hover' => self::token(self::GROUP_BUTTONS, 'Danger button hover', 'That button under the pointer or focused.', Colour::mix($dl, 84, $e), Colour::mix($dd, 78, $i)),
            'danger-surface-pressed' => self::token(self::GROUP_BUTTONS, 'Danger button pressed', 'That button while pressed.', Colour::mix($dl, 70, $e), Colour::mix($dd, 62, $i)),

            // Header, footer and dark bands.
            'band-surface' => self::token(self::GROUP_BANDS, 'Band background', 'The header, the dashboard sidebar and the sign-in panel.', $e, $c),
            'band-glass' => self::token(self::GROUP_BANDS, 'Header glass', 'The translucent header over the page as it scrolls.', Colour::alpha($e, 88), Colour::alpha($c, 80)),
            'footer-surface' => self::token(self::GROUP_BANDS, 'Footer background', 'The site footer.', $e, $c),
            'band-surface-raised' => self::token(self::GROUP_BANDS, 'Raised surface in bands', 'Menus and panels opened from the header or sidebar.', $c, $e),
            'band-accent' => self::token(self::GROUP_BANDS, 'Labels and focus in bands', 'Labels, the focus ring and pending badges on a band.', $b, $ch),
            'band-accent-on-raised' => self::token(self::GROUP_BANDS, 'Labels on raised band surfaces', 'Labels and focus on menus opened from a band.', $ch, $b),
            'band-rule' => self::token(self::GROUP_BANDS, 'Rules in bands', 'Hairlines on a band.', Colour::alpha($b, 35), Colour::alpha($b, 35)),
            'band-rule-strong' => self::token(self::GROUP_BANDS, 'Strong rules in bands', 'Control outlines on a band.', $b, Colour::alpha($ch, 66)),
            'band-rule-strong-on-raised' => self::token(self::GROUP_BANDS, 'Strong rules on raised band surfaces', 'Control outlines on menus opened from a band.', Colour::alpha($ch, 66), $b),
            'band-field' => self::token(self::GROUP_BANDS, 'Inputs in bands', 'The search field and other inputs on a band.', Colour::alpha($i, 6), $e),
            'band-field-on-raised' => self::token(self::GROUP_BANDS, 'Inputs on raised band surfaces', 'Fields inside menus opened from a band.', $e, Colour::alpha($i, 6)),
            'band-fill' => self::token(self::GROUP_BANDS, 'Solid button in bands', 'The solid button on a band.', $i, $i),
            'band-fill-ink' => self::token(self::GROUP_BANDS, 'Solid button text in bands', 'Its text and icons.', $e, $e),
            'band-fill-deep' => self::token(self::GROUP_BANDS, 'Solid button hover in bands', 'It under the pointer or focused.', Colour::mix($i, 45, $l), Colour::mix($i, 45, $l)),
            'band-fill-pressed' => self::token(self::GROUP_BANDS, 'Solid button pressed in bands', 'It while pressed.', $l, $l),
            'band-fill-hover' => self::token(self::GROUP_BANDS, 'Tool hover in bands', 'A pressed tool on a band under the pointer.', $l, $l),
            'band-selection' => self::token(self::GROUP_BANDS, 'Selection in bands', 'Behind selected text on a band.', $ch, $ch),
            'band-selection-ink' => self::token(self::GROUP_BANDS, 'Selected text in bands', 'Selected text on a band.', $e, $e),
            'band-danger' => self::token(self::GROUP_BANDS, 'Danger in bands', 'Errors and destructive actions on a band.', $dd, $dd),
            'band-danger-ink' => self::token(self::GROUP_BANDS, 'Text on danger in bands', 'The label of a solid danger button on a band.', $e, $e),
            'band-danger-hover' => self::token(self::GROUP_BANDS, 'Danger button hover in bands', 'It under the pointer.', Colour::mix($dd, 78, $i), Colour::mix($dd, 78, $i)),
            'band-danger-pressed' => self::token(self::GROUP_BANDS, 'Danger button pressed in bands', 'It while pressed.', Colour::mix($dd, 62, $i), Colour::mix($dd, 62, $i)),

            // Home page opening.
            'ink-on-hero' => self::token(self::GROUP_HERO, 'Text on the opening', 'The tagline and text of the home page opening.', $ch, $ch),
            'hero-accent' => self::token(self::GROUP_HERO, 'Labels and focus on the opening', 'Labels, rules and the focus ring there.', $b, $b),
            'hero-surface-raised' => self::token(self::GROUP_HERO, 'Raised surface on the opening', 'Cards set on the opening.', $c, $c),
            'hero-accent-on-raised' => self::token(self::GROUP_HERO, 'Labels on raised opening surfaces', 'Labels and focus on those cards.', $ch, $ch),

            // Community chat.
            'chat-own' => self::token(self::GROUP_CHAT, 'Own message surface', 'Behind the messages you sent.', $l, $c),
            'chat-unread' => self::token(self::GROUP_CHAT, 'Unread line', 'The line above the first unread message.', $b, $b),

            // Status tones.
            'pending-tone' => self::token(self::GROUP_STATUS, 'Pending', 'Pending tributes and applications.', $bz, $b),
            'success-tone' => self::token(self::GROUP_STATUS, 'Approved', 'Approved, published and successful states.', $c, $ch),
            'neutral-tone' => self::token(self::GROUP_STATUS, 'Rejected', 'Rejected, suspended and draft states.', $a, Colour::alpha($s, 80)),

            // Charts.
            'chart-line' => self::token(self::GROUP_CHARTS, 'Chart series', 'The main line of a chart.', $e, $ch),
            'chart-compare' => self::token(self::GROUP_CHARTS, 'Comparison series', 'The previous period on a chart.', $b, Colour::mix($b, 70, $ch)),
            'chart-grid' => self::token(self::GROUP_CHARTS, 'Chart grid', 'Grid lines.', $s, Colour::alpha($b, 25)),
            'chart-label' => self::token(self::GROUP_CHARTS, 'Chart labels', 'Axis labels.', $a, Colour::alpha($s, 88)),
            'map-land' => self::token(self::GROUP_CHARTS, 'Map land', 'Countries without visitors.', $l, Colour::alpha($i, 7)),
            'map-edge' => self::token(self::GROUP_CHARTS, 'Map borders', 'The lines between countries.', $i, $c),

            // Glow, photographs and the mark.
            'accent-glow' => self::token(self::GROUP_IMAGERY, 'Glow', 'The soft radial glow on the home page opening.', Colour::alpha($b, 22), Colour::alpha($b, 22)),
            'duotone-shadow' => self::token(self::GROUP_IMAGERY, 'Duotone shadows', 'The dark end of the archive photo duotone.', $e, $e),
            'duotone-highlight' => self::token(self::GROUP_IMAGERY, 'Duotone highlights', 'The light end of the archive photo duotone.', $i, $i),
            self::MARK_RING => self::token(self::GROUP_IMAGERY, 'Mark ring', 'The hairline ring of the MK mark. Only brass-like tones of the active palette are allowed.', $b, $b),
        ]);
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isToken(mixed $key): bool
    {
        return is_string($key) && array_key_exists($key, self::all());
    }

    /**
     * @return list<string>
     */
    public static function basic(): array
    {
        return self::inGroup(self::GROUP_BASIC);
    }

    /**
     * @return list<string>
     */
    public static function inGroup(string $group): array
    {
        return array_keys(array_filter(self::all(), static fn (array $token): bool => $token['group'] === $group));
    }

    /**
     * The brand values of one mode, token => hex.
     *
     * @return array<string, string>
     */
    public static function defaults(Theme $mode): array
    {
        return array_map(static fn (array $token): string => $token[$mode->value], self::all());
    }

    /**
     * Tokens tokens.css derives in CSS from the theme tokens (each is a
     * color-mix() of theme tokens only): token => what it is made from.
     *
     * @return array<string, string>
     */
    public static function aliases(): array
    {
        return [
            'surface-band' => 'band-surface',
            'surface-hero' => 'primary',
            'surface-glass' => 'band-glass',
            'danger-wash' => 'danger',
        ];
    }

    /**
     * Brand-only tokens no theme changes: the MK mark keeps its brand colours
     * (only its ring follows the theme, through MARK_RING).
     *
     * @return list<string>
     */
    public static function fixed(): array
    {
        return ['mark-disc', 'mark-disc-ink', 'mark-ink', 'mark-ink-on-band'];
    }

    /**
     * @return array{group: string, label: string, description: string, light: string, dark: string}
     */
    private static function token(string $group, string $label, string $description, string $light, string $dark): array
    {
        return ['group' => $group, 'label' => $label, 'description' => $description, 'light' => $light, 'dark' => $dark];
    }
}
