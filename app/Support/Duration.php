<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Short engaged-time figures for the analytics report: "45s", "2m 05s", "3h 12m".
 */
final class Duration
{
    public static function short(int|float $seconds): string
    {
        $seconds = (int) round(max(0, $seconds));

        if ($seconds < 60) {
            return __(':secondss', ['seconds' => $seconds]);
        }

        if ($seconds < 3600) {
            return __(':minutesm :secondss', ['minutes' => intdiv($seconds, 60), 'seconds' => str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT)]);
        }

        return __(':hoursh :minutesm', ['hours' => intdiv($seconds, 3600), 'minutes' => str_pad((string) intdiv($seconds % 3600, 60), 2, '0', STR_PAD_LEFT)]);
    }

    /**
     * The machine-readable form for <time datetime>, e.g. "PT2M5S".
     */
    public static function iso(int|float $seconds): string
    {
        $seconds = (int) round(max(0, $seconds));

        return 'PT'.(intdiv($seconds, 3600) > 0 ? intdiv($seconds, 3600).'H' : '').(intdiv($seconds % 3600, 60) > 0 ? intdiv($seconds % 3600, 60).'M' : '').($seconds % 60).'S';
    }
}
