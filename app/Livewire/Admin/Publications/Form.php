<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Publications;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\PublicationType;
use App\Http\Requests\Admin\ContentRequest;
use App\Http\Requests\Admin\PublicationRequest;
use App\Livewire\Admin\Concerns\ManagesDocument;
use App\Livewire\Admin\ContentForm;
use App\Models\Publication;
use App\Services\AbstractContentService;
use App\Services\MediaService;
use App\Services\PublicationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Form extends ContentForm
{
    use ManagesDocument;

    public function mount(?Publication $publication = null): void
    {
        $this->initialise($publication);
    }

    protected function type(): string
    {
        return ContentTypes::PUBLICATION;
    }

    protected function service(): AbstractContentService
    {
        return app(PublicationService::class);
    }

    protected function request(): ContentRequest
    {
        return new PublicationRequest($this->recordId);
    }

    protected function defaults(): array
    {
        return [
            'title' => '',
            'slug' => null,
            'type' => PublicationType::JournalArticle->value,
            'authors' => null,
            'year' => null,
            'venue_publisher' => null,
            'doi' => null,
            'citation' => null,
            'abstract' => null,
            'url' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.publications.form';
    }

    protected function viewData(): array
    {
        $data = $this->documentViewData();
        $data['fileUrl'] = $data['file']?->getUrl();
        $data['types'] = PublicationType::labels();

        return $data;
    }

    protected function afterCreate(Model $record): void
    {
        $this->attachPendingDocument($record);
    }

    protected function attachDocument(Model $record, UploadedFile $file): Media
    {
        $this->authorize('manageMedia', $record);

        return app(PublicationService::class)->attachFile($record, $file);
    }

    protected function detachDocument(Model $record): void
    {
        app(PublicationService::class)->removeFile($record, auth()->user());
    }

    protected function currentDocument(Model $record): ?Media
    {
        return app(MediaService::class)->first($record, MediaCollections::FILE);
    }
}
