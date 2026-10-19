<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Human-readable file sizes for file links ("2.1 MB", "640 KB").
 */
final class FileSize
{
    /**
     * @var list<string>
     */
    private const array UNITS = ['B', 'KB', 'MB', 'GB'];

    public static function format(int $bytes): string
    {
        $bytes = max(0, $bytes);
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count(self::UNITS) - 1);
        $value = $bytes / (1024 ** $power);

        if ($power === 0) {
            return $bytes.' '.self::UNITS[0];
        }

        $text = number_format($value, $value < 10 ? 1 : 0, '.', '');

        if (str_contains($text, '.')) {
            $text = rtrim(rtrim($text, '0'), '.');
        }

        return $text.' '.self::UNITS[$power];
    }
}
