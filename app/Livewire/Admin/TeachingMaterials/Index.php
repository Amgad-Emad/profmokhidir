<?php

declare(strict_types=1);

namespace App\Livewire\Admin\TeachingMaterials;

use App\Constants\ContentTypes;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\TeachingMaterialService;
use App\Support\ListToolbar\ListFilter;
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

    protected function columns(): array
    {
        return ['title' => 'Title', 'course' => 'Course', 'type' => 'Type', 'access' => 'Access', 'material_date' => 'Date', 'status' => 'Status'];
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
