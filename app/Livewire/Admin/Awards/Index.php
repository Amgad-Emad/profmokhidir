<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Awards;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\AwardService;
use App\Support\DataTable\Column;
use App\Support\ListToolbar\ListFilter;
use Illuminate\Database\Eloquent\Model;

class Index extends ContentIndex
{
    protected function type(): string
    {
        return ContentTypes::AWARD;
    }

    protected function service(): AbstractContentService
    {
        return app(AwardService::class);
    }

    protected function dataTableColumns(): array
    {
        return [
            Column::primary('name', __('Name'), sortable: true),
            Column::text('organisation', __('Organisation'), sortable: true, width: '12rem', visible: false),
            Column::number('year', __('Year'), sortable: true, width: '5.5rem'),
            ...$this->closingColumns(),
        ];
    }

    protected function supporting(Model $item): array
    {
        return [$item->organisation];
    }

    protected function contentFilters(): array
    {
        return [ListFilter::multi('year', __('Year'), $this->years())];
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
