<?php

declare(strict_types=1);

namespace App\Support\ListToolbar;

/**
 * One choice of the toolbar's Sort menu: a column, a direction and its
 * wording ("Newest first"). An empty column is the screen's own order.
 */
final readonly class ListSort
{
    public function __construct(
        public string $column,
        public string $direction,
        public string $label,
    ) {}

    public function key(): string
    {
        return $this->column === '' ? '' : $this->column.':'.$this->direction;
    }
}
