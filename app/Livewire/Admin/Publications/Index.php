<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Publications;

use App\Constants\ContentTypes;
use App\Constants\PublicationType;
use App\Livewire\Admin\ContentIndex;
use App\Services\AbstractContentService;
use App\Services\PublicationService;

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

    protected function filterOptions(): array
    {
        return ['type' => ['label' => 'Type', 'options' => PublicationType::labels()], 'year' => ['label' => 'Year', 'options' => $this->years()]];
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
