<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Awards;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Http\Requests\Admin\AwardRequest;
use App\Http\Requests\Admin\ContentRequest;
use App\Livewire\Admin\ContentForm;
use App\Models\Award;
use App\Services\AbstractContentService;
use App\Services\AwardService;

class Form extends ContentForm
{
    public function mount(?Award $award = null): void
    {
        $this->initialise($award);
    }

    protected function type(): string
    {
        return ContentTypes::AWARD;
    }

    protected function service(): AbstractContentService
    {
        return app(AwardService::class);
    }

    protected function request(): ContentRequest
    {
        return new AwardRequest;
    }

    protected function defaults(): array
    {
        return [
            'name' => '',
            'organisation' => null,
            'year' => null,
            'description' => null,
            'source_url' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.awards.form';
    }
}
