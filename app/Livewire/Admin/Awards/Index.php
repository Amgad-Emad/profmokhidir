<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Awards;

use App\Constants\ContentTypes;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\AwardService;

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

    protected function columns(): array
    {
        return ['name' => 'Name', 'organisation' => 'Organisation', 'year' => 'Year', 'status' => 'Status'];
    }

    protected function filterOptions(): array
    {
        return ['year' => ['label' => 'Year', 'options' => $this->years()]];
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
