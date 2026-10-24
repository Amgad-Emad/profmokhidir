<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The country outlines of the analytics world map, read once from
 * resources/data/world-map.json (built by scripts/build-world-map.mjs from
 *
 * @svg-maps/world, CC BY 4.0). Drawn inline: no tiles, no external requests.
 */
final class WorldMap
{
    public const string SOURCE = 'data/world-map.json';

    /** Shading steps of the map, lightest (1) to darkest. */
    public const int TIERS = 5;

    /** @var array{viewBox: string, attribution: string, countries: array<string, array{name: string, d: string}>}|null */
    private static ?array $data = null;

    /**
     * @return array{viewBox: string, attribution: string, countries: array<string, array{name: string, d: string}>}
     */
    public static function data(): array
    {
        return self::$data ??= json_decode((string) file_get_contents(resource_path(self::SOURCE)), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * The shading tier (0 = none, 1–TIERS) of a count against the largest count, on a square-root scale
     * so a few countries with many visitors do not wash out the rest.
     */
    public static function tier(int $count, int $max): int
    {
        if ($count <= 0 || $max <= 0) {
            return 0;
        }

        return max(1, min(self::TIERS, (int) ceil(sqrt($count / $max) * self::TIERS)));
    }
}
