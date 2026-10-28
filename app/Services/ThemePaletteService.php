<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Brand;
use App\Constants\Theme;
use App\Constants\ThemeContrast;
use App\Constants\ThemeTokens;
use App\Support\Colour;
use LogicException;

/**
 * The colour arithmetic of a theme: which tokens it sets, what every other
 * token derives to (hover and pressed shades, tints, translucent rules, inks
 * that keep their contrast), the CSS the active theme prints in <head>, and
 * the contrast guard over every pair in App\Constants\ThemeContrast.
 *
 * A theme stores only the colours someone chose, per mode (token => hex). A
 * token it leaves unset is derived from the others; a Basic colour it leaves
 * unset falls back to the brand. Derivations may read the other mode (the
 * dark theme's headings take the light background, a light band takes the
 * dark mode's raised surface), so both modes resolve together.
 */
class ThemePaletteService
{
    /** @var array<string, array<string, string>> mode => token => hex, while resolving */
    private array $resolved = [];

    /** @var array<string, array<string, string>> */
    private array $explicit = [];

    /** @var array<string, true> */
    private array $resolving = [];

    /**
     * Keep only known tokens with valid colours, normalised to lower-case hex.
     *
     * @param  array<mixed>  $values
     * @return array<string, string>
     */
    public function sanitise(array $values): array
    {
        $clean = [];

        foreach ($values as $token => $value) {
            if (ThemeTokens::isToken($token) && Colour::isValid($value)) {
                $clean[$token] = Colour::normalise($value);
            }
        }

        return $clean;
    }

    /**
     * Every token of both modes, from the colours a theme sets.
     *
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public function resolve(array $light, array $dark): array
    {
        $this->explicit = [Theme::Light->value => $this->sanitise($light), Theme::Dark->value => $this->sanitise($dark)];
        $this->resolved = [Theme::Light->value => [], Theme::Dark->value => []];
        $this->resolving = [];

        $result = [];

        foreach (Theme::cases() as $mode) {
            foreach (ThemeTokens::keys() as $token) {
                $result[$mode->value][$token] = $this->get($token, $mode);
            }
        }

        $this->explicit = $this->resolved = [];

        return $result;
    }

    /**
     * What each unset token of a theme derives to (the "reset" value of an advanced token).
     *
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public function derived(array $light, array $dark): array
    {
        $basic = array_flip(ThemeTokens::basic());

        return $this->resolve(array_intersect_key($light, $basic), array_intersect_key($dark, $basic));
    }

    /**
     * Drop chosen advanced colours that equal what they would derive to, so they keep following the Basic colours.
     *
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public function compact(array $light, array $dark): array
    {
        $light = $this->sanitise($light);
        $dark = $this->sanitise($dark);
        $derived = $this->derived($light, $dark);
        $basic = ThemeTokens::basic();
        $keep = static fn (array $values, array $derivedValues): array => array_filter(
            $values,
            static fn (string $value, string $token): bool => in_array($token, $basic, true) || $value !== $derivedValues[$token],
            ARRAY_FILTER_USE_BOTH,
        );

        return ['light' => $keep($light, $derived['light']), 'dark' => $keep($dark, $derived['dark'])];
    }

    /**
     * The custom properties of a resolved theme, for a <style> block in <head>.
     * Declared on :root:root so they outrank tokens.css whatever the order; each
     * token is a light-dark() pair so a visitor who has not chosen a mode still
     * follows the device.
     *
     * @param  array{light: array<string, string>, dark: array<string, string>}  $resolved
     */
    public function css(array $resolved): string
    {
        $declarations = [];

        foreach (ThemeTokens::keys() as $token) {
            $light = $resolved['light'][$token];
            $dark = $resolved['dark'][$token];
            $declarations[] = sprintf('--%s:%s', $token, $light === $dark ? $light : "light-dark({$light},{$dark})");
        }

        return ':root:root{'.implode(';', $declarations).'}';
    }

    /**
     * Every contrast pair in both modes, failures first.
     *
     * @param  array{light: array<string, string>, dark: array<string, string>}  $resolved
     * @return list<array{id: string, mode: string, label: string, kind: string, critical: bool, token: string, foreground: string, background: string, ratio: float, minimum: float, passes: bool, suggestion: ?string}>
     */
    public function check(array $resolved): array
    {
        $results = [];

        foreach (Theme::cases() as $mode) {
            foreach (ThemeContrast::pairs() as $id => $pair) {
                $results[] = $this->checkPair($resolved[$mode->value], $mode, $id, $pair);
            }
        }

        usort($results, static fn (array $a, array $b): int => [$a['passes'], ! $a['critical']] <=> [$b['passes'], ! $b['critical']]);

        return $results;
    }

    /**
     * @param  array{light: array<string, string>, dark: array<string, string>}  $resolved
     * @return list<array<string, mixed>>
     */
    public function failures(array $resolved, bool $criticalOnly = false): array
    {
        return array_values(array_filter(
            $this->check($resolved),
            static fn (array $result): bool => ! $result['passes'] && (! $criticalOnly || $result['critical']),
        ));
    }

    /**
     * The tones the MK mark's ring may take in a mode: the brand brass and the
     * accent tones of the palette (never the text, surface or danger colours).
     *
     * @param  array<string, string>  $resolved  one mode
     * @return list<string>
     */
    public function markRingOptions(array $resolved): array
    {
        $tones = [Colour::normalise(Brand::BRASS)];

        foreach (['accent-line', 'accent-text', 'focus', 'band-accent', 'hero-accent', 'pending-tone'] as $token) {
            if (isset($resolved[$token]) && ! Colour::isTranslucent($resolved[$token])) {
                $tones[] = $resolved[$token];
            }
        }

        return array_values(array_unique($tones));
    }

    /**
     * The opaque colours of a resolved mode, Basic first, for the picker's quick picks.
     *
     * @param  array<string, string>  $resolved  one mode
     * @return list<string>
     */
    public function quickPicks(array $resolved, int $limit = 12): array
    {
        $order = [...ThemeTokens::basic(), 'ink-strong', 'ink-muted', 'accent-text', 'band-surface', 'band-surface-raised', 'fill', 'surface-panel', 'ink-on-band'];
        $picks = [];

        foreach ($order as $token) {
            $value = $resolved[$token] ?? null;

            if ($value !== null && ! Colour::isTranslucent($value) && ! in_array($value, $picks, true)) {
                $picks[] = $value;
            }
        }

        return array_slice($picks, 0, $limit);
    }

    /**
     * @param  array<string, string>  $tokens  one resolved mode
     * @param  array{fg: string, bg: list<string>, kind: string, label: string, critical: bool}  $pair
     * @return array{id: string, mode: string, label: string, kind: string, critical: bool, token: string, foreground: string, background: string, ratio: float, minimum: float, passes: bool, suggestion: ?string}
     */
    private function checkPair(array $tokens, Theme $mode, string $id, array $pair): array
    {
        $token = ThemeContrast::tokenOf($pair['fg']);
        $opacity = ThemeContrast::opacityOf($pair['fg']);
        $foreground = $opacity === null ? $tokens[$token] : Colour::alpha($tokens[$token], $opacity);
        $background = Colour::flatten(array_map(static fn (string $layer): string => $tokens[$layer], $pair['bg']));
        $minimum = ThemeContrast::minimum($pair['kind']);
        $ratio = Colour::contrast($foreground, $background);
        $passes = round($ratio, 2) >= $minimum;
        $suggestion = null;

        if (! $passes) {
            $shade = Colour::ensure($foreground, $background, $minimum + 0.05);
            // The token itself is stored without the pair's opacity.
            $suggestion = $opacity === null ? $shade : Colour::opaque($shade);
        }

        return [
            'id' => $id, 'mode' => $mode->value, 'label' => $pair['label'], 'kind' => $pair['kind'], 'critical' => $pair['critical'],
            'token' => $token, 'foreground' => $foreground, 'background' => $background,
            'ratio' => round($ratio, 2), 'minimum' => $minimum, 'passes' => $passes, 'suggestion' => $suggestion,
        ];
    }

    private function get(string $token, Theme $mode): string
    {
        if (isset($this->resolved[$mode->value][$token])) {
            return $this->resolved[$mode->value][$token];
        }

        $key = $mode->value.':'.$token;

        if (isset($this->resolving[$key])) {
            throw new LogicException("Theme token {$key} derives from itself.");
        }

        $this->resolving[$key] = true;
        $explicit = $this->explicit[$mode->value][$token] ?? null;

        if ($token === ThemeTokens::MARK_RING) {
            $value = $this->markRing($explicit, $mode);
        } elseif ($explicit !== null) {
            $value = $explicit;
        } elseif (in_array($token, ThemeTokens::basic(), true)) {
            $value = ThemeTokens::defaults($mode)[$token];
        } else {
            $value = Colour::normalise($this->derive($token, $mode));
        }

        unset($this->resolving[$key]);

        return $this->resolved[$mode->value][$token] = $value;
    }

    private function markRing(?string $explicit, Theme $mode): string
    {
        $options = $this->markRingOptions([
            'accent-line' => $this->get('accent-line', $mode),
            'accent-text' => $this->get('accent-text', $mode),
            'focus' => $this->get('focus', $mode),
            'band-accent' => $this->get('band-accent', $mode),
            'hero-accent' => $this->get('hero-accent', $mode),
            'pending-tone' => $this->get('pending-tone', $mode),
        ]);

        return $explicit !== null && in_array($explicit, $options, true) ? $explicit : ThemeTokens::defaults($mode)[ThemeTokens::MARK_RING];
    }

    /**
     * How a token follows the others when a theme leaves it unset. Each rule
     * reproduces the brand's relationship (the brand values are what these
     * rules give for the brand's Basic colours, or the brand overrides them).
     */
    private function derive(string $token, Theme $mode): string
    {
        $light = $mode === Theme::Light;
        $g = fn (string $name): string => $this->get($name, $mode);
        $l = fn (string $name): string => $this->get($name, Theme::Light);
        $d = fn (string $name): string => $this->get($name, Theme::Dark);
        $ensure = fn (string $ink, array $backgrounds, float $ratio): string => $this->ensureAll($ink, array_map($g, $backgrounds), $ratio);
        $either = fn (string $preferred, string $background, float $ratio, string $fallback): string => Colour::contrast($g($preferred), $g($background)) >= $ratio ? $g($preferred) : $fallback;

        return match ($token) {
            'ink-strong' => $ensure($light ? $g('ink') : $l('surface'), ['surface', 'surface-raised'], 4.5),
            'ink-body', 'link', 'chart-line' => $g('ink'),
            'ink-muted' => $ensure(Colour::mix($g('ink'), 78, $g('surface')), ['surface', 'surface-raised'], 7.0),
            'ink-quiet' => $ensure(Colour::mix($g('ink'), 62, $g('surface')), ['surface', 'surface-raised', 'surface-panel'], 4.5),
            'accent-text' => $ensure($g('accent-line'), ['surface', 'surface-sunken', 'surface-panel'], 4.5),
            'link-hover', 'field-focus-line' => $g('ink-strong'),
            'placeholder', 'chart-label' => $g('ink-quiet'),
            'ink-on-band' => $this->ensureAll($d('ink'), [$g('band-surface'), $g('footer-surface'), $g('band-surface-raised')], 4.5),
            'ink-on-band-strong' => $this->ensureAll($d('ink-strong'), [$g('band-surface'), $g('primary')], 4.5),
            'accent-ink' => $ensure($g('primary'), ['accent-line'], 4.5),
            'danger-ink' => $ensure($g('surface'), ['danger-surface', 'danger-surface-hover', 'danger-surface-pressed'], 4.5),
            'selection', 'card-surface', 'chat-own' => $g('surface-raised'),
            'selection-ink' => $ensure($g('ink-strong'), ['selection'], 4.5),
            'surface-panel' => $light ? $g('surface') : $g('surface-raised'),
            'surface-sunken' => $light ? $g('surface-raised') : $g('surface'),
            'surface-overlay' => Colour::alpha($g('primary'), $light ? 60 : 78),
            'surface-scrim' => Colour::alpha($g('primary'), 70),
            'rule' => $light ? Colour::mix($g('surface-raised'), 88, $g('ink')) : Colour::alpha($g('accent-line'), 35),
            'card-border' => $g('rule'),
            'rule-strong' => $light
                ? $ensure(Colour::mix($g('ink'), 72, $g('surface')), ['surface', 'surface-sunken', 'surface-field'], 3.0)
                : $ensure($g('accent-line'), ['surface', 'surface-sunken'], 3.0),
            'field-border' => $g('rule-strong'),
            'focus', 'pending-tone' => $ensure($g('accent-text'), ['surface', 'surface-sunken'], 3.0),
            'accent-text-on-raised' => $either('accent-text', 'surface-raised', 4.5, $this->ensureAll($g('ink'), [$g('surface-raised'), $g('surface-panel'), $g('chat-own'), $g('card-surface')], 4.5)),
            'focus-on-raised' => $either('focus', 'surface-raised', 3.0, $g('accent-text-on-raised')),
            'rule-strong-on-raised' => $either('rule-strong', 'surface-raised', 3.0, $this->ensureAll(Colour::alpha($g('ink'), 66), [$g('surface-raised')], 3.0)),
            'surface-field' => $light ? $g('surface') : Colour::alpha($g('ink-strong'), 6),
            'surface-field-on-raised' => $g('surface'),
            'fill' => $light ? $g('primary') : $g('ink-strong'),
            'fill-ink' => $ensure($g('surface'), ['fill', 'fill-deep', 'fill-pressed'], 4.5),
            'fill-deep' => Colour::isDark($g('fill')) ? Colour::mix($g('fill'), 78, Colour::BLACK) : Colour::mix($g('fill'), 45, $l('surface-raised')),
            'fill-pressed' => Colour::isDark($g('fill')) ? Colour::mix($g('fill'), 62, Colour::BLACK) : $l('surface-raised'),
            'fill-hover' => $light ? $d('surface-raised') : $l('surface-raised'),
            'danger-surface' => $g('danger'),
            'danger-surface-hover' => $light ? Colour::mix($g('danger'), 84, $g('primary')) : Colour::mix($g('danger'), 78, $l('surface')),
            'danger-surface-pressed' => $light ? Colour::mix($g('danger'), 70, $g('primary')) : Colour::mix($g('danger'), 62, $l('surface')),
            'band-surface', 'footer-surface' => $token === 'footer-surface' ? $g('band-surface') : ($light ? $g('primary') : $g('surface-raised')),
            'band-glass' => Colour::alpha($g('band-surface'), $light ? 88 : 80),
            'band-surface-raised' => $light ? $d('surface-raised') : $g('primary'),
            'band-accent' => $either('accent-line', 'band-surface', 4.5, $g('ink-on-band')),
            'band-accent-on-raised' => $either('accent-line', 'band-surface-raised', 4.5, $g('ink-on-band')),
            'band-rule' => Colour::alpha($g('accent-line'), 35),
            'band-rule-strong' => $either('accent-line', 'band-surface', 3.0, Colour::alpha($g('ink-on-band'), 66)),
            'band-rule-strong-on-raised' => $either('accent-line', 'band-surface-raised', 3.0, Colour::alpha($g('ink-on-band'), 66)),
            'band-field' => $light ? Colour::alpha($g('ink-on-band-strong'), 6) : $g('primary'),
            'band-field-on-raised' => $light ? $g('primary') : Colour::alpha($g('ink-on-band-strong'), 6),
            'band-fill' => $g('ink-on-band-strong'),
            'band-fill-ink' => $ensure($g('primary'), ['band-fill', 'band-fill-deep', 'band-fill-pressed'], 4.5),
            'band-fill-deep' => Colour::isDark($g('band-fill')) ? Colour::mix($g('band-fill'), 78, Colour::BLACK) : Colour::mix($g('band-fill'), 45, $l('surface-raised')),
            'band-fill-pressed', 'band-fill-hover' => Colour::isDark($g('band-fill')) ? Colour::mix($g('band-fill'), 62, Colour::BLACK) : $l('surface-raised'),
            'band-selection' => $g('ink-on-band'),
            'band-selection-ink' => $ensure($g('primary'), ['band-selection'], 4.5),
            'band-danger' => $this->ensureAll($d('danger'), [$g('band-surface'), $g('band-surface-raised'), $g('primary')], 4.5),
            'band-danger-ink' => $ensure($d('surface'), ['band-danger', 'band-danger-hover', 'band-danger-pressed'], 4.5),
            'band-danger-hover' => Colour::mix($g('band-danger'), 78, $l('surface')),
            'band-danger-pressed' => Colour::mix($g('band-danger'), 62, $l('surface')),
            'ink-on-hero' => $this->ensureAll($l('ink-on-band'), [$g('primary'), $g('hero-surface-raised')], 4.5),
            'hero-accent' => $this->ensureAll($l('band-accent'), [$g('primary')], 4.5),
            'hero-surface-raised' => $l('band-surface-raised'),
            'hero-accent-on-raised' => $this->ensureAll($l('band-accent-on-raised'), [$g('hero-surface-raised')], 4.5),
            'chat-unread' => $g('accent-line'),
            'chart-compare' => $ensure($g('accent-line'), ['surface-panel', 'surface'], 3.05),
            'success-tone' => $light ? $ensure($d('surface-raised'), ['surface', 'surface-raised', 'surface-panel'], 4.5) : $g('ink'),
            'neutral-tone' => $light ? $g('ink-quiet') : $this->ensureAll(Colour::alpha($g('ink-muted'), 80), [$g('surface'), $g('surface-raised'), $g('surface-panel')], 4.5),
            'chart-grid' => $light ? $g('rule') : Colour::alpha($g('accent-line'), 25),
            'map-land' => $light ? $g('surface-raised') : Colour::alpha($g('ink-strong'), 7),
            'map-edge' => $light ? $g('surface') : $g('surface-raised'),
            'accent-glow' => Colour::alpha($g('accent-line'), 22),
            'duotone-shadow' => $l('primary'),
            'duotone-highlight' => $l('surface'),
            default => throw new LogicException("Theme token {$token} has no derivation."),
        };
    }

    /**
     * The ink itself, or its nearest shade that reaches the ratio on every background.
     *
     * @param  list<string>  $backgrounds
     */
    private function ensureAll(string $ink, array $backgrounds, float $ratio): string
    {
        for ($pass = 0; $pass < 4; $pass++) {
            $worst = null;

            foreach ($backgrounds as $background) {
                if ($worst === null || Colour::contrast($ink, $background) < Colour::contrast($ink, $worst)) {
                    $worst = $background;
                }
            }

            if ($worst === null || Colour::contrast($ink, $worst) >= $ratio) {
                break;
            }

            $ink = Colour::ensure($ink, $worst, $ratio + 0.02);
        }

        return $ink;
    }
}
