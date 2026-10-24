<?php

namespace Tests\Unit\Design\Concerns;

/**
 * Reads resources/css/tokens.css and resolves its custom properties the way the
 * browser does: light-dark() by theme, var() through the region blocks in force
 * (:root, .on-dark, .glow-brass, the raised surfaces and the .btn table),
 * color-mix() in sRGB with premultiplied alpha, translucent colours composited
 * over their surface. Used by ThemeContrastTest and ButtonContrastTest.
 */
trait ResolvesDesignTokens
{
    /** @var array<string, array<string, string>> selector => declarations */
    protected array $blocks;

    protected function loadTokens(): void
    {
        $this->blocks = $this->parseBlocks(file_get_contents(resource_path('css/tokens.css')));
    }

    /**
     * Every context the tokens are used in: theme, region layers and whether the surface is raised.
     *
     * @return array<string, array{theme: string, layers: list<string>, raised: bool}>
     */
    public static function contexts(): array
    {
        $contexts = [];

        foreach (['light', 'dark'] as $theme) {
            foreach (['page' => [], 'band' => ['.on-dark'], 'hero' => ['.on-dark', '.glow-brass']] as $region => $layers) {
                foreach ([false, true] as $raised) {
                    $contexts["{$theme} {$region}".($raised ? ' raised' : '')] = ['theme' => $theme, 'layers' => $layers, 'raised' => $raised];
                }
            }
        }

        return $contexts;
    }


    /**
     * The surfaces an ink can sit on in a context, each as an opaque colour.
     *
     * @return array<string, array{float, float, float, float}>
     */
    protected function surfacesFor(array $context): array
    {
        $names = $context['raised'] ? ['surface-raised', 'surface-panel'] : ['surface', 'surface-sunken'];
        $surfaces = [];

        foreach ($names as $name) {
            $surfaces[$name] = $this->resolve($name, $context);
            $surfaces["surface-field on {$name}"] = $this->over($this->resolve('surface-field', $context), $surfaces[$name]);
        }

        return $surfaces;
    }

    /**
     * @return list<string>
     */
    protected function tokenNames(): array
    {
        $names = [];

        foreach ($this->blocks as $declarations) {
            foreach (array_keys($declarations) as $property) {
                if (str_starts_with($property, '--') && ! str_starts_with($property, '--brand-')) {
                    $names[] = substr($property, 2);
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The declarations in force for a context, later layers overriding earlier ones.
     *
     * @return array<string, string>
     */
    protected function declarations(array $context): array
    {
        $declarations = $this->blocks[':root'];

        foreach ($context['layers'] as $layer) {
            $declarations = [...$declarations, ...$this->blocks[$layer]];
        }

        if ($context['raised']) {
            $declarations = [...$declarations, ...$this->blocks['raised']];
        }

        // The button table is declared on .btn itself, so it resolves inside whatever region holds the button.
        return [...$declarations, ...($this->blocks['.btn'] ?? [])];
    }

    /**
     * A token as an sRGB colour with alpha, in the given context.
     *
     * @return array{float, float, float, float}
     */
    protected function resolve(string $token, array $context): array
    {
        $declarations = $this->declarations($context);

        $this->assertArrayHasKey("--{$token}", $declarations, "--{$token} is not declared");

        return $this->evaluate($declarations["--{$token}"], $declarations, $context['theme']);
    }

    /**
     * @param  array<string, string>  $declarations
     * @return array{float, float, float, float}
     */
    protected function evaluate(string $value, array $declarations, string $theme): array
    {
        $value = trim($value);

        if ($value === 'transparent') {
            return [0, 0, 0, 0];
        }

        if (preg_match('/^#([0-9a-f]{6})$/i', $value, $m)) {
            return [hexdec(substr($m[1], 0, 2)) / 255, hexdec(substr($m[1], 2, 2)) / 255, hexdec(substr($m[1], 4, 2)) / 255, 1];
        }

        if (preg_match('/^var\((--[a-z0-9-]+)\)$/i', $value, $m)) {
            $this->assertArrayHasKey($m[1], $declarations, "{$m[1]} is not declared");

            return $this->evaluate($declarations[$m[1]], $declarations, $theme);
        }

        if (preg_match('/^light-dark\((.*)\)$/is', $value, $m)) {
            $arguments = $this->splitArguments($m[1]);
            $this->assertCount(2, $arguments, "light-dark() takes two values: {$value}");

            return $this->evaluate($arguments[$theme === 'dark' ? 1 : 0], $declarations, $theme);
        }

        if (preg_match('/^color-mix\(\s*in\s+srgb\s*,(.*)\)$/is', $value, $m)) {
            $arguments = $this->splitArguments($m[1]);
            $this->assertCount(2, $arguments, "color-mix() takes two colours: {$value}");

            [$first, $firstShare] = $this->colourAndShare($arguments[0]);
            [$second, $secondShare] = $this->colourAndShare($arguments[1]);
            $firstShare ??= $secondShare === null ? 0.5 : 1 - $secondShare;
            $secondShare ??= 1 - $firstShare;

            return $this->mix($this->evaluate($first, $declarations, $theme), $firstShare, $this->evaluate($second, $declarations, $theme), $secondShare);
        }

        $this->fail("Cannot evaluate token value: {$value}");
    }

    /**
     * @return array{string, ?float}
     */
    protected function colourAndShare(string $argument): array
    {
        if (preg_match('/^(.*?)\s+(\d+(?:\.\d+)?)%$/s', trim($argument), $m)) {
            return [trim($m[1]), (float) $m[2] / 100];
        }

        return [trim($argument), null];
    }

    /**
     * Split function arguments at top-level commas.
     *
     * @return list<string>
     */
    protected function splitArguments(string $list): array
    {
        $arguments = [];
        $depth = 0;
        $current = '';

        foreach (str_split($list) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
                $arguments[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $arguments[] = trim($current);

        return $arguments;
    }

    /**
     * color-mix() in sRGB with premultiplied alpha, as the specification defines it.
     *
     * @param  array{float, float, float, float}  $a
     * @param  array{float, float, float, float}  $b
     * @return array{float, float, float, float}
     */
    protected function mix(array $a, float $shareA, array $b, float $shareB): array
    {
        $alpha = $a[3] * $shareA + $b[3] * $shareB;

        if ($alpha <= 0) {
            return [0, 0, 0, 0];
        }

        $channel = fn (int $i): float => ($a[$i] * $a[3] * $shareA + $b[$i] * $b[3] * $shareB) / $alpha;

        return [$channel(0), $channel(1), $channel(2), $alpha];
    }

    /**
     * Composite a colour over an opaque background.
     *
     * @param  array{float, float, float, float}  $foreground
     * @param  array{float, float, float, float}  $background
     * @return array{float, float, float, float}
     */
    protected function over(array $foreground, array $background): array
    {
        $a = $foreground[3];

        return [
            $foreground[0] * $a + $background[0] * (1 - $a),
            $foreground[1] * $a + $background[1] * (1 - $a),
            $foreground[2] * $a + $background[2] * (1 - $a),
            1,
        ];
    }

    /**
     * WCAG contrast ratio of an ink (composited if translucent) over an opaque surface.
     *
     * @param  array{float, float, float, float}  $ink
     * @param  array{float, float, float, float}  $surface
     */
    protected function ratio(array $ink, array $surface): float
    {
        $surface = $this->over($surface, [1, 1, 1, 1]);
        $ink = $this->over($ink, $surface);
        $l1 = $this->luminance($ink);
        $l2 = $this->luminance($surface);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    /**
     * @param  array{float, float, float, float}  $colour
     */
    protected function luminance(array $colour): float
    {
        $linear = static fn (float $c): float => $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $linear($colour[0]) + 0.7152 * $linear($colour[1]) + 0.0722 * $linear($colour[2]);
    }

    /**
     * @param  array{float, float, float, float}  $colour
     */
    protected function hex(array $colour): string
    {
        return sprintf('#%02x%02x%02x', (int) round($colour[0] * 255), (int) round($colour[1] * 255), (int) round($colour[2] * 255));
    }

    /**
     * The brand hex values a token is built from in a context.
     *
     * @return list<string>
     */
    protected function sources(string $token, array $context): array
    {
        $declarations = $this->declarations($context);
        $seen = [];
        $walk = function (string $value) use (&$walk, &$seen, $declarations, $context): void {
            if (preg_match('/^light-dark\((.*)\)$/is', trim($value), $m)) {
                $arguments = $this->splitArguments($m[1]);
                $walk($arguments[$context['theme'] === 'dark' ? 1 : 0]);

                return;
            }

            preg_match_all('/#[0-9a-f]{6}\b/i', $value, $hex);
            array_push($seen, ...array_map('strtolower', $hex[0]));

            preg_match_all('/var\((--[a-z0-9-]+)\)/i', $value, $vars);

            foreach ($vars[1] as $name) {
                $walk($declarations[$name] ?? '');
            }
        };

        $walk($declarations["--{$token}"]);

        return array_values(array_unique($seen));
    }

    /**
     * The custom-property declarations of each rule block, keyed by a short name.
     *
     * @return array<string, array<string, string>>
     */
    protected function parseBlocks(string $css): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $blocks = [];

        preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);

        foreach ($rules as [, $selector, $body]) {
            $selector = trim(preg_replace('/\s+/', ' ', $selector));
            $key = match (true) {
                $selector === ':root' => ':root',
                $selector === '.on-dark' => '.on-dark',
                $selector === '.glow-brass' => '.glow-brass',
                str_starts_with($selector, '.card') => 'raised',
                $selector === '.btn' => '.btn',
                default => null,
            };

            if ($key === null) {
                continue;
            }

            $declarations = [];

            foreach ($this->splitDeclarations($body) as $declaration) {
                [$property, $value] = array_map('trim', explode(':', $declaration, 2)) + [1 => ''];

                if ($property !== '') {
                    $declarations[$property] = preg_replace('/\s+/', ' ', $value);
                }
            }

            $blocks[$key] = [...($blocks[$key] ?? []), ...$declarations];
        }

        foreach ([':root', '.on-dark', '.glow-brass', 'raised'] as $required) {
            $this->assertArrayHasKey($required, $blocks, "tokens.css has no {$required} block");
        }

        return $blocks;
    }

    /**
     * Split a rule body at semicolons outside parentheses.
     *
     * @return list<string>
     */
    protected function splitDeclarations(string $body): array
    {
        return array_filter(array_map('trim', $this->splitOn($body, ';')), static fn (string $d): bool => $d !== '');
    }

    /**
     * @return list<string>
     */
    protected function splitOn(string $text, string $separator): array
    {
        $parts = [];
        $depth = 0;
        $current = '';

        foreach (str_split($text) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($char === $separator && $depth === 0) {
                $parts[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $parts[] = $current;

        return $parts;
    }

    /**
     * The raw value of a token in a context, following plain var() aliases.
     */
    protected function rawValue(string $token, array $context): string
    {
        $declarations = $this->declarations($context);
        $value = $declarations["--{$token}"] ?? '';
        $seen = 0;

        while (preg_match('/^var\((--[a-z0-9-]+)\)$/i', trim($value), $m) && $seen++ < 10) {
            $value = $declarations[$m[1]] ?? '';
        }

        return $value;
    }
}
