<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Constants\Uploads;
use App\Http\Requests\Admin\DocumentUploadRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The single document (PDF, DOCX, PPTX, ZIP; 50 MB) of a publication or
 * teaching material. On an existing record the file is attached as soon as
 * it is chosen; on a new record it is attached right after the first save.
 */
trait ManagesDocument
{
    use WithFileUploads;

    /** @var UploadedFile|null */
    public $document = null;

    abstract protected function attachDocument(Model $record, UploadedFile $file): Media;

    abstract protected function detachDocument(Model $record): void;

    abstract protected function currentDocument(Model $record): ?Media;

    public function updatedDocument(): void
    {
        $this->validate([DocumentUploadRequest::FIELD => Uploads::documentRules()], (new DocumentUploadRequest)->messages(), (new DocumentUploadRequest)->attributes());

        $record = $this->record();

        if ($record === null || $this->document === null) {
            return;
        }

        $this->attachDocument($record, $this->document);
        $this->document = null;
        $this->notify('File attached.');
    }

    public function removeDocument(): void
    {
        $record = $this->record();

        if ($record === null) {
            return;
        }

        $this->authorize('manageMedia', $record);
        $this->detachDocument($record);
        $this->notify('File removed.');
    }

    protected function attachPendingDocument(Model $record): void
    {
        if ($this->document instanceof UploadedFile) {
            $this->attachDocument($record, $this->document);
            $this->document = null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentViewData(): array
    {
        $record = $this->record();

        return [
            'file' => $record === null ? null : $this->currentDocument($record),
            'fileAccept' => '.'.implode(',.', Uploads::DOCUMENT_EXTENSIONS),
            'fileHint' => __('PDF, Word, PowerPoint or ZIP up to :size', ['size' => Uploads::documentMaxLabel()]),
        ];
    }
}
