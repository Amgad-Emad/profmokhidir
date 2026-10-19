<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Albums;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\AlbumService;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::ALBUM;
    }

    protected function service(): AbstractContentService
    {
        return app(AlbumService::class);
    }

    protected function columns(): array
    {
        return ['title' => 'Title', 'published_at' => 'Published', 'status' => 'Status'];
    }
}
