<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Brand;
use App\Repositories\Contracts\BrandTokenRepositoryInterface;
use InvalidArgumentException;

/**
 * Assembles the design-system review page and checks WCAG contrast for the palette.
 */
class StyleguideService
{
    /** WCAG 2.2 AA minimum contrast for normal text. */
    public const float AA_NORMAL = 4.5;

    /** WCAG 2.2 AA minimum contrast for large text (24px, or 18.66px bold) and UI components. */
    public const float AA_LARGE = 3.0;

    public function __construct(private readonly BrandTokenRepositoryInterface $tokens) {}

    /**
     * Every colour with its role and its contrast against the two primary surfaces.
     *
     * @return list<array{name: string, hex: string, role: string, usage: string, onIvory: array{ratio: float, normal: bool, large: bool}, onEvergreen: array{ratio: float, normal: bool, large: bool}}>
     */
    public function colours(): array
    {
        $roles = $this->tokens->colourRoles();
        $rows = [];

        foreach ($this->tokens->colours() as $name => $hex) {
            $rows[] = [
                'name' => $name,
                'hex' => $hex,
                'role' => $roles[$name]['role'] ?? '',
                'usage' => $roles[$name]['usage'] ?? '',
                'onIvory' => $this->contrast($hex, Brand::IVORY),
                'onEvergreen' => $this->contrast($hex, Brand::EVERGREEN),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, array{family: string, weights: list<int>, italic: bool, use: string}>
     */
    public function fonts(): array
    {
        return $this->tokens->fonts();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function typeScale(): array
    {
        return $this->tokens->typeScale();
    }

    /**
     * @return array<string, array{attribute: string, summary: string}>
     */
    public function motion(): array
    {
        return $this->tokens->motion();
    }

    /**
     * Everything the style guide view needs.
     *
     * @return array{colours: list<array<string, mixed>>, fonts: array<string, mixed>, typeScale: array<string, mixed>, motion: array<string, mixed>}
     */
    public function sections(): array
    {
        return [
            'colours' => $this->colours(),
            'fonts' => $this->fonts(),
            'typeScale' => $this->typeScale(),
            'motion' => $this->motion(),
        ];
    }

    /**
     * Contrast ratio and AA verdicts for foreground on background.
     *
     * @return array{ratio: float, normal: bool, large: bool}
     */
    public function contrast(string $foreground, string $background): array
    {
        $ratio = $this->contrastRatio($foreground, $background);

        return [
            'ratio' => $ratio,
            'normal' => $ratio >= self::AA_NORMAL,
            'large' => $ratio >= self::AA_LARGE,
        ];
    }

    /**
     * WCAG 2.x contrast ratio between two hex colours, rounded to two decimals.
     */
    public function contrastRatio(string $a, string $b): float
    {
        $la = $this->relativeLuminance($a);
        $lb = $this->relativeLuminance($b);
        [$light, $dark] = $la >= $lb ? [$la, $lb] : [$lb, $la];

        return round(($light + 0.05) / ($dark + 0.05), 2);
    }

    public function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = array_map(
            fn (int $channel): float => $this->linearise($channel / 255),
            $this->rgb($hex),
        );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * @return array{int, int, int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a hex colour.', $hex));
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private function linearise(float $channel): float
    {
        return $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
