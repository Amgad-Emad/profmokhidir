<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The contrast guard of Admin › Appearance: every text/background pair the
 * design actually draws, checked against WCAG 2.2 AA in each mode (4.5:1 for
 * text, 3:1 for large text and for user-interface boundaries such as focus
 * rings, control outlines and chart lines). A foreground "token@92" is that
 * token at 92% opacity (the translucent inks tokens.css derives on bands).
 * A background is a stack of tokens, the first at the bottom (a translucent
 * input over the page). Critical pairs (body text, links and buttons) block
 * publishing while they fail.
 */
final class ThemeContrast
{
    public const string TEXT = 'text';

    public const string LARGE = 'large';

    public const string UI = 'ui';

    /**
     * @return array<string, float>
     */
    public static function minimums(): array
    {
        return [self::TEXT => 4.5, self::LARGE => 3.0, self::UI => 3.0];
    }

    public static function minimum(string $kind): float
    {
        return self::minimums()[$kind];
    }

    /**
     * Pair id => [foreground, background layers, kind, label, critical].
     *
     * @return array<string, array{fg: string, bg: list<string>, kind: string, label: string, critical: bool}>
     */
    public static function pairs(): array
    {
        $pairs = [
            // The page.
            'body' => ['ink-body', ['surface'], self::TEXT, 'Body text on the background', true],
            'ink' => ['ink', ['surface'], self::TEXT, 'Text on the background', true],
            'heading' => ['ink-strong', ['surface'], self::TEXT, 'Headings on the background', false],
            'muted' => ['ink-muted', ['surface'], self::TEXT, 'Muted text on the background', false],
            'quiet' => ['ink-quiet', ['surface'], self::TEXT, 'Quiet text on the background', false],
            'label' => ['accent-text', ['surface'], self::TEXT, 'Labels on the background', false],
            'label-sunken' => ['accent-text', ['surface-sunken'], self::TEXT, 'Labels on the sunken surface', false],
            'link' => ['link', ['surface'], self::TEXT, 'Links on the background', true],
            'link-hover' => ['link-hover', ['surface'], self::TEXT, 'Links under the pointer', true],
            'placeholder' => ['placeholder', ['surface', 'surface-field'], self::TEXT, 'Placeholder text in a field', false],
            'field-text' => ['ink', ['surface', 'surface-field'], self::TEXT, 'Typed text in a field', true],
            'field-border' => ['field-border', ['surface'], self::UI, 'Field border on the background', false],
            'field-focus' => ['field-focus-line', ['surface', 'surface-field'], self::UI, 'Field focus line', false],
            'focus' => ['focus', ['surface'], self::UI, 'Focus ring on the background', false],
            'rule-strong' => ['rule-strong', ['surface'], self::UI, 'Strong rule on the background', false],
            'accent-line' => ['accent-line', ['surface'], self::UI, 'Accent rule on the background', false],
            'selection' => ['selection-ink', ['selection'], self::TEXT, 'Selected text', false],
            'danger' => ['danger', ['surface'], self::TEXT, 'Danger text on the background', true],
            'danger-raised' => ['danger', ['surface-raised'], self::TEXT, 'Danger text on raised surfaces', false],
            'accent-ink' => ['accent-ink', ['accent-line'], self::TEXT, 'Text on an accent fill', false],

            // Raised surfaces, cards and panels.
            'raised-ink' => ['ink', ['surface-raised'], self::TEXT, 'Text on raised surfaces', true],
            'raised-heading' => ['ink-strong', ['surface-raised'], self::TEXT, 'Headings on raised surfaces', false],
            'raised-muted' => ['ink-muted', ['surface-raised'], self::TEXT, 'Muted text on raised surfaces', false],
            'raised-quiet' => ['ink-quiet', ['surface-raised'], self::TEXT, 'Quiet text on raised surfaces', false],
            'raised-label' => ['accent-text-on-raised', ['surface-raised'], self::TEXT, 'Labels on raised surfaces', false],
            'raised-focus' => ['focus-on-raised', ['surface-raised'], self::UI, 'Focus ring on raised surfaces', false],
            'raised-rule' => ['rule-strong-on-raised', ['surface-raised'], self::UI, 'Field borders on raised surfaces', false],
            'panel-ink' => ['ink', ['surface-panel'], self::TEXT, 'Text on panels and dialogs', true],
            'panel-label' => ['accent-text-on-raised', ['surface-panel'], self::TEXT, 'Labels on panels', false],
            'card-ink' => ['ink', ['card-surface'], self::TEXT, 'Text on cards', true],
            'card-muted' => ['ink-muted', ['card-surface'], self::TEXT, 'Muted text on cards', false],
            'card-label' => ['accent-text-on-raised', ['card-surface'], self::TEXT, 'Labels on cards', false],
            'raised-field' => ['ink', ['surface-raised', 'surface-field-on-raised'], self::TEXT, 'Typed text in a field on a card', true],

            // Buttons.
            'button' => ['fill-ink', ['fill'], self::TEXT, 'Solid button text', true],
            'button-hover' => ['fill-ink', ['fill-deep'], self::TEXT, 'Solid button text under the pointer', true],
            'button-pressed' => ['fill-ink', ['fill-pressed'], self::TEXT, 'Solid button text while pressed', true],
            'button-tool' => ['fill-ink', ['fill-hover'], self::TEXT, 'Pressed tool under the pointer', false],
            'button-edge' => ['fill', ['surface'], self::UI, 'Solid button against the background', false],
            'danger-button' => ['danger-ink', ['danger-surface'], self::TEXT, 'Danger button text', true],
            'danger-button-hover' => ['danger-ink', ['danger-surface-hover'], self::TEXT, 'Danger button text under the pointer', true],
            'danger-button-pressed' => ['danger-ink', ['danger-surface-pressed'], self::TEXT, 'Danger button text while pressed', true],

            // Status, chat and charts.
            'pending' => ['pending-tone', ['surface'], self::TEXT, 'Pending badge', false],
            'approved' => ['success-tone', ['surface'], self::TEXT, 'Approved badge', false],
            'rejected' => ['neutral-tone', ['surface'], self::TEXT, 'Rejected badge', false],
            'pending-panel' => ['accent-text-on-raised', ['surface-panel'], self::TEXT, 'Pending badge on a panel', false],
            'approved-panel' => ['success-tone', ['surface-panel'], self::TEXT, 'Approved badge on a panel', false],
            'rejected-panel' => ['neutral-tone', ['surface-panel'], self::TEXT, 'Rejected badge on a panel', false],
            'chat-own' => ['ink', ['chat-own'], self::TEXT, 'Your own chat messages', true],
            'chat-own-label' => ['accent-text-on-raised', ['chat-own'], self::TEXT, 'Labels in your own messages', false],
            'chat-unread' => ['chat-unread', ['surface'], self::UI, 'Unread line', false],
            'chart-line' => ['chart-line', ['surface-panel'], self::UI, 'Chart series', false],
            'chart-compare' => ['chart-compare', ['surface-panel'], self::UI, 'Comparison series', false],
            'chart-label' => ['chart-label', ['surface-panel'], self::TEXT, 'Chart labels', false],

            // Header, footer and dark bands.
            'band-ink' => ['ink-on-band', ['band-surface'], self::TEXT, 'Navigation and text on the header and sidebar', true],
            'band-heading' => ['ink-on-band-strong', ['band-surface'], self::TEXT, 'Headings on bands', false],
            'band-muted' => ['ink-on-band@84', ['band-surface'], self::TEXT, 'Quiet text on bands', false],
            'band-glass' => ['ink-on-band', ['surface', 'band-glass'], self::TEXT, 'Header text on the glass', true],
            'footer-ink' => ['ink-on-band', ['footer-surface'], self::TEXT, 'Footer text and links', true],
            'footer-quiet' => ['ink-on-band@84', ['footer-surface'], self::TEXT, 'Quiet footer text', false],
            'band-accent' => ['band-accent', ['band-surface'], self::TEXT, 'Labels and focus on bands', false],
            'band-rule' => ['band-rule-strong', ['band-surface'], self::UI, 'Field borders on bands', false],
            'band-raised-ink' => ['ink-on-band', ['band-surface-raised'], self::TEXT, 'Text in menus opened from a band', true],
            'band-raised-label' => ['band-accent-on-raised', ['band-surface-raised'], self::TEXT, 'Labels in those menus', false],
            'band-raised-rule' => ['band-rule-strong-on-raised', ['band-surface-raised'], self::UI, 'Field borders in those menus', false],
            'band-field' => ['ink-on-band', ['band-surface', 'band-field'], self::TEXT, 'Typed text in a band field', true],
            'band-button' => ['band-fill-ink', ['band-fill'], self::TEXT, 'Solid button text on bands', true],
            'band-button-hover' => ['band-fill-ink', ['band-fill-deep'], self::TEXT, 'Its text under the pointer', true],
            'band-button-pressed' => ['band-fill-ink', ['band-fill-pressed'], self::TEXT, 'Its text while pressed', true],
            'band-selection' => ['band-selection-ink', ['band-selection'], self::TEXT, 'Selected text on bands', false],
            'band-danger' => ['band-danger', ['band-surface'], self::TEXT, 'Danger text on bands', false],
            'band-danger-raised' => ['band-danger', ['band-surface-raised'], self::TEXT, 'Danger text in band menus', false],
            'band-danger-button' => ['band-danger-ink', ['band-danger'], self::TEXT, 'Danger button text on bands', true],
            'band-danger-button-hover' => ['band-danger-ink', ['band-danger-hover'], self::TEXT, 'Its text under the pointer', true],
            'band-danger-button-pressed' => ['band-danger-ink', ['band-danger-pressed'], self::TEXT, 'Its text while pressed', true],

            // The home page opening.
            'hero-ink' => ['ink-on-hero', ['primary'], self::TEXT, 'Text on the home page opening', true],
            'hero-heading' => ['ink-on-band-strong', ['primary'], self::TEXT, 'Headings on the opening', false],
            'hero-accent' => ['hero-accent', ['primary'], self::TEXT, 'Labels on the opening', false],
            'hero-accent-line' => ['accent-line', ['primary'], self::UI, 'Accent rules on the opening', false],
            'hero-raised' => ['hero-accent-on-raised', ['hero-surface-raised'], self::TEXT, 'Labels on cards on the opening', false],
            'hero-raised-ink' => ['ink-on-band', ['hero-surface-raised'], self::TEXT, 'Text on cards on the opening', false],
            'hero-danger' => ['band-danger', ['primary'], self::TEXT, 'Danger text on the opening', false],
        ];

        return array_map(static fn (array $pair): array => [
            'fg' => $pair[0], 'bg' => $pair[1], 'kind' => $pair[2], 'label' => $pair[3], 'critical' => $pair[4],
        ], $pairs);
    }

    /**
     * The token a "token@92" foreground names.
     */
    public static function tokenOf(string $foreground): string
    {
        return explode('@', $foreground, 2)[0];
    }

    public static function opacityOf(string $foreground): ?float
    {
        $parts = explode('@', $foreground, 2);

        return isset($parts[1]) ? (float) $parts[1] : null;
    }
}
