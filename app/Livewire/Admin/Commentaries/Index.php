<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Commentaries;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\CommentaryService;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::COMMENTARY;
    }

    protected function service(): AbstractContentService
    {
        return app(CommentaryService::class);
    }

    protected function columns(): array
    {
        return ['title' => 'Title', 'date' => 'Date', 'byline' => 'Byline', 'status' => 'Status'];
    }
}
