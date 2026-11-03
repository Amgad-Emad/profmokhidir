<?php

declare(strict_types=1);

namespace App\Livewire\Admin\CvEntries;

use App\Constants\ContentTypes;
use App\Constants\CvSection;
use App\Constants\DateFormats;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\CvEntryService;
use App\Support\DataTable\Column;
use App\Support\ListToolbar\ListFilter;
use Illuminate\Database\Eloquent\Model;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::CV_ENTRY;
    }

    protected function service(): AbstractContentService
    {
        return app(CvEntryService::class);
    }

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('title', __('Title'), sortable: true),
            Column::text('section', __('Section'), sortable: true, width: '10rem'),
            Column::text('organisation', __('Organisation'), width: '12rem', visible: false),
            Column::number('start_year', __('From'), sortable: true, width: '5.5rem'),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        return [$item->organisation, $item->end_year ? $item->start_year.DateFormats::RANGE_SEPARATOR.$item->end_year : null];
    }

    protected function contentFilters(): array
    {
        return [ListFilter::multi('section', __('Section'), CvSection::labels())];
    }
}
