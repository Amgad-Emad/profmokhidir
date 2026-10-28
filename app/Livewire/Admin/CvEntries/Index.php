<?php

declare(strict_types=1);

namespace App\Livewire\Admin\CvEntries;

use App\Constants\ContentTypes;
use App\Constants\CvSection;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\CvEntryService;
use App\Support\ListToolbar\ListFilter;

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

    protected function columns(): array
    {
        return ['title' => 'Title', 'section' => 'Section', 'organisation' => 'Organisation', 'start_year' => 'From', 'status' => 'Status'];
    }

    protected function contentFilters(): array
    {
        return [ListFilter::multi('section', __('Section'), CvSection::labels())];
    }
}
