<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * How the Pictures index is shown; the choice lives in the URL (?view=decades).
 */
enum PictureView: string
{
    use HasValues;

    case Albums = 'albums';
    case Decades = 'decades';

    public function label(): string
    {
        return match ($this) {
            self::Albums => 'Albums',
            self::Decades => 'Through the decades',
        };
    }

    /**
     * The view named in the query string; anything unknown is the album index.
     */
    public static function fromQuery(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::Albums) : self::Albums;
    }

    /**
     * Query parameters that select this view (none for the default).
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        return $this === self::Albums ? [] : [Archive::QUERY_VIEW => $this->value];
    }
}
