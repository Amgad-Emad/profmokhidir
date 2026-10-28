<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Publications;

use App\Constants\ContentTypes;
use App\Constants\ListToolbar;
use App\Constants\PublicationType;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\PublicationService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListView;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::PUBLICATION;
    }

    protected function service(): AbstractContentService
    {
        return app(PublicationService::class);
    }

    protected function columns(): array
    {
        return ['title' => 'Title', 'type' => 'Type', 'year' => 'Year', 'authors' => 'Authors', 'status' => 'Status'];
    }

    protected function contentFilters(): array
    {
        return [
            ListFilter::multi('type', __('Type'), PublicationType::labels()),
            ListFilter::multi('year', __('Year'), $this->years()),
            ListFilter::flag('is_featured', __('Featured'), __('Featured on the site'), __('Featured')),
        ];
    }

    protected function contentViews(): array
    {
        return [new ListView('featured', __('Featured'), ['is_featured' => ListToolbar::FLAG_ON])];
    }

    /**
     * @return array<int, string>
     */
    private function years(): array
    {
        $years = [];

        foreach ($this->service()->distinct('year') as $year) {
            $years[(int) $year] = (string) $year;
        }

        return $years;
    }
}
