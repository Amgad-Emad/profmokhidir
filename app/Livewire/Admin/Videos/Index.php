<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Videos;

use App\Constants\ContentTypes;
use App\Constants\VideoProvider;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\VideoService;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::VIDEO;
    }

    protected function service(): AbstractContentService
    {
        return app(VideoService::class);
    }

    protected function columns(): array
    {
        return ['title' => 'Title', 'date' => 'Date', 'provider' => 'Source', 'event' => 'Event', 'status' => 'Status'];
    }

    protected function filterOptions(): array
    {
        return ['provider' => ['label' => 'Source', 'options' => VideoProvider::labels()]];
    }
}
