<?php

declare(strict_types=1);

namespace App\Livewire\Admin\OtherItems;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\OtherItemService;
use App\Support\DataTable\Column;
use App\Support\ListToolbar\ListFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::OTHER_ITEM;
    }

    protected function service(): AbstractContentService
    {
        return app(OtherItemService::class);
    }

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('title', __('Title'), sortable: true),
            Column::text('category', __('Category'), sortable: true, width: '10rem', visible: false),
            Column::text('item_type', __('Type'), width: '9rem', visible: false),
            Column::date('date', __('Date')),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        return [$item->category, $item->item_type instanceof \BackedEnum && method_exists($item->item_type, 'label') ? $item->item_type->label() : (string) $item->item_type];
    }

    protected function contentFilters(): array
    {
        return [
            ListFilter::multi('category', __('Category'), $this->options('category')),
            ListFilter::multi('item_type', __('Type'), $this->options('item_type')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function options(string $column): array
    {
        $options = [];

        foreach ($this->service()->distinct($column) as $value) {
            $options[(string) $value] = Str::headline((string) $value);
        }

        ksort($options);

        return $options;
    }
}
