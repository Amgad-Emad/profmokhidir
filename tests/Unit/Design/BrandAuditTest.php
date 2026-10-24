<?php

namespace Tests\Unit\Design;

use App\Constants\Brand;
use App\Constants\BrandAudit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Brand audit over the source stylesheets, scripts, Blade templates and the
 * built CSS: only the nine brand colours (plus white, black and transparent),
 * and only in the token file (every other file goes through the semantic
 * tokens), only the three brand fonts, no bounce or spin animations and no
 * gradient other than the brass glow.
 */
class BrandAuditTest extends TestCase
{
    /**
     * @return array<string, string> path => contents
     */
    private function files(array $globs): array
    {
        $files = [];

        foreach ($globs as $glob) {
            foreach (glob(base_path($glob), GLOB_BRACE) ?: [] as $path) {
                $relative = ltrim(str_replace(base_path(), '', $path), '/');

                if (array_key_exists($relative, BrandAudit::exemptFiles())) {
                    continue;
                }

                $files[$relative] = file_get_contents($path);
            }
        }

        return $files;
    }

    private function sources(): array
    {
        $files = $this->files(BrandAudit::sourceGlobs());

        $this->assertNotEmpty($files);

        return $files;
    }

    private function builds(): array
    {
        return $this->files(BrandAudit::buildGlobs());
    }

    /**
     * Strip the places where a hash is not a colour: HTML entities, anchors and ids.
     */
    private function withoutFalseHashes(string $contents): string
    {
        return preg_replace(['/&#x?[0-9a-f]+;/i', '/(href|id|aria-controls|aria-labelledby|aria-describedby|for|data-target)="#?[^"]*"/i', '/url\(#[^)]*\)/i'], '', $contents);
    }

    /**
     * #RRGGBBAA and #RGBA are a brand colour at an opacity (Tailwind's bg-evergreen/60 and so on).
     */
    private function withoutAlpha(string $hex): string
    {
        return match (strlen($hex)) {
            9 => substr($hex, 0, 7),
            5 => substr($hex, 0, 4),
            default => $hex,
        };
    }

    #[Test]
    public function the_only_colours_outside_the_palette_are_exactly_the_danger_red_and_white_and_black(): void
    {
        $outside = array_values(array_diff(
            BrandAudit::permittedHex(),
            array_map('strtoupper', Brand::colours()),
            ['#FFFFFF', '#000000', '#FFF', '#000', '#0000', '#00000000', '#FFF0', '#FFFFFF00'],
        ));

        $this->assertSame(['#9B2C2C', '#EFB0A5'], $outside);
        $this->assertSame(['danger-light' => '#9B2C2C', 'danger-dark' => '#EFB0A5'], Brand::functionalColours());
        $this->assertMatchesRegularExpression(BrandAudit::rawColourVariablePattern(), 'color: var(--functional-danger-light);', 'the raw danger red may only be read in the token file');
    }

    #[Test]
    public function only_brand_colours_appear_in_sources_and_built_css(): void
    {
        $offenders = [];
        $builds = $this->builds();

        foreach ([...$this->sources(), ...$builds] as $file => $contents) {
            preg_match_all('/#[0-9a-f]{3,8}(?![\w-])/i', $this->withoutFalseHashes($contents), $hex);

            // Hex colours live in the token file only; the build inherits them from there.
            $hexAllowed = $file === BrandAudit::TOKEN_FILE || array_key_exists($file, $builds);

            foreach (array_unique(array_map('strtoupper', $hex[0])) as $value) {
                if (! $hexAllowed) {
                    $offenders[] = "{$file}: {$value} (hex colours belong in ".BrandAudit::TOKEN_FILE.')';
                } elseif (! in_array($this->withoutAlpha($value), BrandAudit::permittedHex(), true)) {
                    $offenders[] = "{$file}: {$value}";
                }
            }

            // Literal rgb()/hsl() colours, except Tailwind's feature-detection probes (`red` in @supports).
            preg_match_all('/\b(?:rgba?|hsla?)\((?!\s*from\s+red\b)[^)]*\)/i', $contents, $functions);

            foreach (array_unique($functions[0]) as $value) {
                $offenders[] = "{$file}: {$value}";
            }

            // Tailwind's removed default palette and arbitrary colour utilities.
            preg_match_all('/\b(?:text|bg|border|ring|from|via|to|fill|stroke|outline|decoration|accent|caret|shadow)-(?:red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|neutral)-\d{2,3}\b/', $contents, $palette);

            foreach (array_unique($palette[0]) as $value) {
                $offenders[] = "{$file}: {$value}";
            }
        }

        $this->assertSame([], $offenders, "Off-brand colours:\n".implode("\n", $offenders));
    }

    #[Test]
    public function brand_colours_are_only_named_in_the_token_file(): void
    {
        $offenders = [];

        foreach ($this->sources() as $file => $contents) {
            if ($file === BrandAudit::TOKEN_FILE) {
                continue;
            }

            preg_match_all(BrandAudit::rawColourClassPattern(), $contents, $classes);
            preg_match_all(BrandAudit::rawColourVariablePattern(), $contents, $variables);

            foreach (array_unique([...$classes[0], ...$variables[0]]) as $value) {
                $offenders[] = "{$file}: {$value}";
            }
        }

        // The build must not carry raw colour utilities either (the palette is not a Tailwind namespace).
        foreach ($this->builds() as $file => $contents) {
            $pattern = '~\.(?:'.implode('|', array_map('preg_quote', BrandAudit::colourUtilityPrefixes())).')-(?:'.implode('|', array_keys(Brand::colours())).')(?:\\\\/\d{1,3})?[\s,{:]~';
            preg_match_all($pattern, $contents, $classes);

            foreach (array_unique($classes[0]) as $value) {
                $offenders[] = "{$file}: ".trim($value, " ,{:");
            }
        }

        $this->assertSame([], $offenders, "Raw brand colours outside ".BrandAudit::TOKEN_FILE." (use the semantic tokens):\n".implode("\n", $offenders));
    }

    #[Test]
    public function only_the_three_brand_fonts_are_used(): void
    {
        $offenders = [];
        $permitted = BrandAudit::permittedFontFamilies();

        foreach ([...$this->sources(), ...$this->builds()] as $file => $contents) {
            preg_match_all('/font-family\s*:\s*([^;}]+)/i', $contents, $declarations);

            foreach ($declarations[1] as $declaration) {
                // Values that resolve through a theme token are checked at the token.
                if (preg_match('/^\s*var\(--(?:font-(?:display|sans|mono)|default-(?:mono-)?font-family)\b/', $declaration)) {
                    continue;
                }

                foreach (array_map('trim', explode(',', $declaration)) as $family) {
                    $family = trim($family, "'\" ");

                    if ($family !== '' && ! in_array($family, $permitted, true)) {
                        $offenders[] = "{$file}: {$family}";
                    }
                }
            }

            preg_match_all('/\bfont-\[[^\]]+\]/', $contents, $arbitrary);

            foreach (array_unique($arbitrary[0]) as $value) {
                $offenders[] = "{$file}: {$value}";
            }
        }

        $this->assertSame([], array_values(array_unique($offenders)), "Off-brand fonts:\n".implode("\n", array_unique($offenders)));
    }

    #[Test]
    public function nothing_bounces_spins_or_flashes(): void
    {
        $offenders = [];
        $words = implode('|', array_map('preg_quote', BrandAudit::forbiddenMotionWords()));

        foreach ([...$this->sources(), ...$this->builds()] as $file => $contents) {
            preg_match_all('/@keyframes\s+([a-z0-9_-]+)/i', $contents, $keyframes);

            foreach (array_unique($keyframes[1]) as $name) {
                if (! in_array($name, BrandAudit::permittedKeyframes(), true)) {
                    $offenders[] = "{$file}: @keyframes {$name}";
                }
            }

            preg_match_all('/animation(?:-name)?\s*:\s*([^;}]+)/i', $contents, $animations);

            foreach ($animations[1] as $animation) {
                // The build may reorder the shorthand, so the name can sit anywhere among its tokens.
                $tokens = preg_split('/\s*,\s*|\s+/', trim($animation));

                if (array_intersect($tokens, [...BrandAudit::permittedKeyframes(), 'none']) === []) {
                    $offenders[] = "{$file}: animation {$animation}";
                }
            }

            preg_match_all('/\banimate-(?:'.$words.')\b|@keyframes\s+[a-z0-9_-]*(?:'.$words.')/i', $contents, $utilities);

            foreach (array_unique($utilities[0]) as $value) {
                $offenders[] = "{$file}: {$value}";
            }
        }

        $this->assertSame([], $offenders, "Forbidden motion:\n".implode("\n", $offenders));
    }

    #[Test]
    public function the_brass_glow_is_the_only_gradient(): void
    {
        $offenders = [];
        $seen = 0;

        foreach ([...$this->sources(), ...$this->builds()] as $file => $contents) {
            preg_match_all('/([a-z-]*gradient)\s*\(((?:[^()]|\([^()]*\))*)\)/i', $contents, $gradients, PREG_SET_ORDER);

            foreach ($gradients as [$whole, $function, $arguments]) {
                $seen++;

                $lower = strtolower($arguments);
                $isRadial = $function === BrandAudit::GRADIENT_FUNCTION;
                $isBrass = str_contains($lower, BrandAudit::GLOW_TOKEN) || str_contains($lower, '--brand-brass') || str_contains($lower, strtolower(Brand::BRASS));

                if (! $isRadial || ! $isBrass) {
                    $offenders[] = "{$file}: {$whole}";
                }
            }

            preg_match_all('/\bbg-(?:linear|radial|conic|gradient)-[a-z0-9-]+|\bbg-gradient-to-[a-z]+/', $contents, $utilities);

            foreach (array_unique($utilities[0]) as $value) {
                $offenders[] = "{$file}: {$value}";
            }
        }

        $this->assertGreaterThanOrEqual(1, $seen, 'The brass glow gradient exists');
        $this->assertSame([], $offenders, "Gradients other than the brass glow:\n".implode("\n", $offenders));
    }

    #[Test]
    public function exempt_files_are_listed_with_a_reason(): void
    {
        foreach (BrandAudit::exemptFiles() as $file => $reason) {
            $this->assertFileExists(base_path($file));
            $this->assertNotSame('', trim($reason));
        }
    }
}
