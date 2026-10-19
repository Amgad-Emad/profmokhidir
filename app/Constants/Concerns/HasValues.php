<?php

declare(strict_types=1);

namespace App\Constants\Concerns;

/**
 * Helpers shared by the string-backed enums in app/Constants.
 * Every enum using this trait must implement label().
 */
trait HasValues
{
    abstract public function label(): string;

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * @return array<string, string> value => label
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
