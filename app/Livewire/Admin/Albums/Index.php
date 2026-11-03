<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Albums;

use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\AlbumService;
use App\Services\MediaService;
use App\Support\DataTable\Column;
use Illuminate\Database\Eloquent\Model;

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

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('title', __('Title'), sortable: true),
            Column::date('published_at', __('Published')),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        $count = app(MediaService::class)->inCollection($item, MediaCollections::PHOTOS)->count();

        return [trans_choice('{0} No photos|{1} :count photo|[2,*] :count photos', $count, ['count' => $count])];
    }

    protected function thumbnail(Model $item): ?string
    {
        return app(MediaService::class)->thumbnailUrl($item, MediaCollections::PHOTOS);
    }
}
