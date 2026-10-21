<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HomeQuotes;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Http\Requests\Admin\ContentRequest;
use App\Http\Requests\Admin\HomeQuoteRequest;
use App\Livewire\Admin\ContentForm;
use App\Models\HomeQuote;
use App\Services\AbstractContentService;
use App\Services\HomeQuoteService;

class Form extends ContentForm
{
    public function mount(?HomeQuote $homeQuote = null): void
    {
        $this->initialise($homeQuote);
    }

    protected function type(): string
    {
        return ContentTypes::HOME_QUOTE;
    }

    protected function service(): AbstractContentService
    {
        return app(HomeQuoteService::class);
    }

    protected function request(): ContentRequest
    {
        return new HomeQuoteRequest;
    }

    protected function defaults(): array
    {
        return [
            'quote' => '',
            'attribution' => null,
            'source' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.home-quotes.form';
    }
}
