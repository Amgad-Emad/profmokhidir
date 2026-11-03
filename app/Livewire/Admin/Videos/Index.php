<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Videos;

use App\Constants\ContentTypes;
use App\Constants\ListToolbar;
use App\Constants\MediaCollections;
use App\Constants\VideoProvider;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\MediaService;
use App\Services\VideoService;
use App\Support\DataTable\Column;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListView;
use Illuminate\Database\Eloquent\Model;

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

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('title', __('Title'), sortable: true),
            Column::text('provider', __('Source'), sortable: true, width: '8rem', visible: false),
            Column::text('event', __('Event'), width: '10rem', visible: false),
            Column::date('date', __('Date')),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        return [$item->event, $item->location, $item->provider instanceof \BackedEnum && method_exists($item->provider, 'label') ? $item->provider->label() : null];
    }

    protected function thumbnail(Model $item): ?string
    {
        return app(MediaService::class)->thumbnailUrl($item, MediaCollections::THUMBNAIL);
    }

    protected function contentFilters(): array
    {
        return [
            ListFilter::multi('provider', __('Source'), VideoProvider::labels()),
            ListFilter::flag('is_featured', __('Featured'), __('Featured on the site'), __('Featured')),
        ];
    }

    protected function contentViews(): array
    {
        return [new ListView('featured', __('Featured'), ['is_featured' => ListToolbar::FLAG_ON])];
    }
}
