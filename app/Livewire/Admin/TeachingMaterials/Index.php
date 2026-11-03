<?php

declare(strict_types=1);

namespace App\Livewire\Admin\TeachingMaterials;

use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\MediaService;
use App\Services\TeachingMaterialService;
use App\Support\DataTable\Column;
use App\Support\FileSize;
use App\Support\ListToolbar\ListFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::TEACHING_MATERIAL;
    }

    protected function service(): AbstractContentService
    {
        return app(TeachingMaterialService::class);
    }

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('title', __('Title'), sortable: true),
            Column::text('course', __('Course'), sortable: true, width: '10rem', visible: false),
            Column::text('type', __('Type'), sortable: true, width: '9rem', visible: false),
            Column::text('access', __('Access'), sortable: true, width: '7.5rem'),
            Column::date('material_date', __('Date')),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        $file = app(MediaService::class)->first($item, MediaCollections::FILE);

        return [$item->course, $item->type?->label(), $file ? FileSize::format((int) $file->size) : null];
    }

    protected function contentFilters(): array
    {
        return [
            ListFilter::multi('type', __('Type'), TeachingMaterialType::labels()),
            ListFilter::single('access', __('Access'), TeachingAccess::labels()),
            ListFilter::multi('course', __('Course'), $this->options('course')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function options(string $column): array
    {
        $options = [];

        foreach ($this->service()->distinct($column) as $value) {
            $options[(string) $value] = Str::headline((string) $value);
        }

        ksort($options);

        return $options;
    }
}
