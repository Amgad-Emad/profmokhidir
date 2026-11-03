<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HomeQuotes;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\HomeQuoteService;
use App\Support\DataTable\Column;
use Illuminate\Database\Eloquent\Model;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::HOME_QUOTE;
    }

    protected function service(): AbstractContentService
    {
        return app(HomeQuoteService::class);
    }

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('quote', __('Quote')),
            Column::text('attribution', __('Attribution'), sortable: true, width: '12rem', visible: false),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        return [$item->attribution, $item->source];
    }
}
