<?php

declare(strict_types=1);

namespace App\Support\ListToolbar;

/**
 * A preset of an admin list, shown as a tab with its count ("Drafts 3"):
 * clicking it sets exactly these filters. `trash` marks the content screens'
 * Trash view, which lists soft-deleted records instead of filtering.
 */
final readonly class ListView
{
    /**
     * @param  array<string, string>  $filters
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $filters = [],
        public bool $trash = false,
    ) {}
}
