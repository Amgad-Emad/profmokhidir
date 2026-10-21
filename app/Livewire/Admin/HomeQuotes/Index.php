<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HomeQuotes;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\HomeQuoteService;

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

    protected function columns(): array
    {
        return ['quote' => 'Quote', 'attribution' => 'Attribution', 'status' => 'Status'];
    }
}
