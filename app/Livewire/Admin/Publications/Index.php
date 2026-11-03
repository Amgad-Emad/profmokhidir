<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Publications;

use App\Constants\ContentTypes;
use App\Constants\ListToolbar;
use App\Constants\PublicationType;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\PublicationService;
use App\Support\DataTable\Column;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListView;
use Illuminate\Database\Eloquent\Model;

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

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('title', __('Title'), sortable: true),
            Column::text('authors', __('Authors'), width: '12rem', visible: false),
            Column::text('type', __('Type'), sortable: true, width: '10rem'),
            Column::number('year', __('Year'), sortable: true, width: '5.5rem'),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        return [$item->authors, $item->venue_publisher];
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
