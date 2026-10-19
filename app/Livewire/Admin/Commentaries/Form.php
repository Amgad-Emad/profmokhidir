<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Commentaries;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Http\Requests\Admin\CommentaryRequest;
use App\Http\Requests\Admin\ContentRequest;
use App\Livewire\Admin\ContentForm;
use App\Models\Commentary;
use App\Services\AbstractContentService;
use App\Services\CommentaryService;
use Illuminate\Database\Eloquent\Model;

class Form extends ContentForm
{
    public function mount(?Commentary $commentary = null): void
    {
        $this->initialise($commentary);
    }

    protected function type(): string
    {
        return ContentTypes::COMMENTARY;
    }

    protected function service(): AbstractContentService
    {
        return app(CommentaryService::class);
    }

    protected function request(): ContentRequest
    {
        return new CommentaryRequest($this->recordId);
    }

    protected function defaults(): array
    {
        return [
            'title' => '',
            'slug' => null,
            'date' => null,
            'summary' => null,
            'body' => null,
            'external_url' => null,
            'tags' => null,
            'byline' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.commentaries.form';
    }

    protected function fromRecord(Model $record): array
    {
        $form = parent::fromRecord($record);
        $form['tags'] = is_array($record->tags) ? implode(', ', $record->tags) : null;

        return $form;
    }
}
