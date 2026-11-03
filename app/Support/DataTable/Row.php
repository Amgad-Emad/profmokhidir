<?php

declare(strict_types=1);

namespace App\Support\DataTable;

use App\Constants\StatusTone;
use Illuminate\Contracts\Support\Htmlable;

/**
 * One record as a data table row. The primary cell combines a thumbnail, the
 * title (a real link to `url` when the user may edit it) with small badges
 * ("Needs Arabic") and a muted supporting line; `cells` hold the other columns
 * by key; `actions` the row's actions (inline ones as icons, all in the menu).
 * `detail` is optional extra content (a tribute's words) shown under the row
 * from a disclosure button; `open` shows it from the start (an inline edit
 * form). `selectable` false leaves the row without a checkbox.
 */
final readonly class Row
{
    /**
     * @param  list<string>  $supporting
     * @param  list<array{label: string, tone: StatusTone, hook?: string, title?: string, lang?: string, data?: array<string, string>}>  $badges
     * @param  array<string, Cell>  $cells
     * @param  list<RowAction>  $actions
     * @param  array<string, string|int>  $attributes  extra data attributes of the row (data-tribute => 4)
     */
    public function __construct(
        public int|string $id,
        public string $title,
        public ?string $url = null,
        public array $supporting = [],
        public ?string $thumbnail = null,
        public array $badges = [],
        public array $cells = [],
        public array $actions = [],
        public ?Htmlable $detail = null,
        public bool $open = false,
        public ?string $detailLabel = null,
        public bool $selectable = true,
        public bool $titleMono = false,
        public array $attributes = [],
    ) {}

    public function cell(string $key): Cell
    {
        return $this->cells[$key] ?? new Cell;
    }

    public function supportingLine(string $separator = ' · '): string
    {
        return implode($separator, array_values(array_filter($this->supporting, fn (?string $part): bool => $part !== null && $part !== '')));
    }

    /**
     * @return list<RowAction>
     */
    public function inlineActions(): array
    {
        return array_values(array_filter($this->actions, fn (RowAction $action): bool => $action->inline));
    }
}
