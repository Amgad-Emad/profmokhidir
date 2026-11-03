<?php

declare(strict_types=1);

namespace App\Support\DataTable;

use App\Constants\StatusTone;

/**
 * What one cell shows: its text, a tooltip (the exact time of a relative date,
 * the whole of a truncated value), a machine-readable datetime, a status tone
 * (status cells and badges) and, for a badges cell, its badges. A status cell
 * may carry a quick toggle (`toggle`: a wire call such as "togglePublished(4)").
 */
final readonly class Cell
{
    /**
     * @param  list<array{label: string, tone: StatusTone}>  $badges
     * @param  array{call: string, on: bool, label: string}|null  $toggle
     */
    public function __construct(
        public string $text = '',
        public ?string $tooltip = null,
        public ?string $datetime = null,
        public ?StatusTone $tone = null,
        public array $badges = [],
        public ?array $toggle = null,
        public bool $muted = false,
    ) {}

    public static function text(?string $text, bool $muted = false): self
    {
        return new self(text: (string) $text, muted: $muted);
    }

    public static function status(string $label, StatusTone $tone, ?array $toggle = null): self
    {
        return new self(text: $label, tone: $tone, toggle: $toggle);
    }

    /**
     * @param  list<array{label: string, tone: StatusTone}>  $badges
     */
    public static function badges(array $badges, string $none = ''): self
    {
        return new self(text: $none, badges: $badges, muted: true);
    }

    public function isEmpty(): bool
    {
        return $this->text === '' && $this->badges === [];
    }
}
