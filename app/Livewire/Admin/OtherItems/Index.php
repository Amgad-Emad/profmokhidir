<?php

declare(strict_types=1);

namespace App\Livewire\Admin\OtherItems;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\OtherItemService;
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

    protected function columns(): array
    {
        return ['title' => 'Title', 'category' => 'Category', 'item_type' => 'Type', 'date' => 'Date', 'status' => 'Status'];
    }

    protected function filterOptions(): array
    {
        return ['category' => ['label' => 'Category', 'options' => $this->options('category')], 'item_type' => ['label' => 'Type', 'options' => $this->options('item_type')]];
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
