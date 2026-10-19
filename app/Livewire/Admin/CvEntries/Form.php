<?php

declare(strict_types=1);

namespace App\Livewire\Admin\CvEntries;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\CvSection;
use App\Http\Requests\Admin\ContentRequest;
use App\Http\Requests\Admin\CvEntryRequest;
use App\Livewire\Admin\ContentForm;
use App\Models\CvEntry;
use App\Services\AbstractContentService;
use App\Services\CvEntryService;

class Form extends ContentForm
{
    public function mount(?CvEntry $cvEntry = null): void
    {
        $this->initialise($cvEntry);
    }

    protected function type(): string
    {
        return ContentTypes::CV_ENTRY;
    }

    protected function service(): AbstractContentService
    {
        return app(CvEntryService::class);
    }

    protected function request(): ContentRequest
    {
        return new CvEntryRequest;
    }

    protected function defaults(): array
    {
        return [
            'section' => CvSection::Appointment->value,
            'title' => '',
            'organisation' => null,
            'role_description' => null,
            'start_year' => null,
            'end_year' => null,
            'last_updated_at' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.cv-entries.form';
    }

    protected function viewData(): array
    {
        return ['sections' => CvSection::labels()];
    }
}
