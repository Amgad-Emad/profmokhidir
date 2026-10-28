<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Videos;

use App\Constants\ContentTypes;
use App\Constants\ListToolbar;
use App\Constants\VideoProvider;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\VideoService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListView;

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
