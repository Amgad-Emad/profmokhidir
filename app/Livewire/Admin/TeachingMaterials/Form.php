<?php

declare(strict_types=1);

namespace App\Livewire\Admin\TeachingMaterials;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Http\Requests\Admin\ContentRequest;
use App\Http\Requests\Admin\TeachingMaterialRequest;
use App\Livewire\Admin\Concerns\ManagesDocument;
use App\Livewire\Admin\ContentForm;
use App\Models\TeachingMaterial;
use App\Services\AbstractContentService;
use App\Services\MediaService;
use App\Services\TeachingMaterialService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Form extends ContentForm
{
    use ManagesDocument;

    public function mount(?TeachingMaterial $teachingMaterial = null): void
    {
        $this->initialise($teachingMaterial);
    }

    protected function type(): string
    {
        return ContentTypes::TEACHING_MATERIAL;
    }

    protected function service(): AbstractContentService
    {
        return app(TeachingMaterialService::class);
    }

    protected function request(): ContentRequest
    {
        return new TeachingMaterialRequest;
    }

    protected function defaults(): array
    {
        return [
            'title' => '',
            'course' => null,
            'topic' => null,
            'type' => TeachingMaterialType::Slides->value,
            'description' => null,
            'version' => null,
            'material_date' => null,
            'external_url' => null,
            'access' => TeachingAccess::Public->value,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.teaching-materials.form';
    }

    protected function viewData(): array
    {
        $record = $this->record();
        $data = $this->documentViewData();
        $data['fileUrl'] = $record === null ? null : app(TeachingMaterialService::class)->downloadUrl($record);
        $data['types'] = TeachingMaterialType::labels();
        $data['accessOptions'] = TeachingAccess::labels();

        return $data;
    }

    protected function afterCreate(Model $record): void
    {
        $this->attachPendingDocument($record);
    }

    protected function attachDocument(Model $record, UploadedFile $file): Media
    {
        $this->authorize('manageMedia', $record);

        return app(TeachingMaterialService::class)->attachFile($record, $file);
    }

    protected function detachDocument(Model $record): void
    {
        app(TeachingMaterialService::class)->removeFile($record, auth()->user());
    }

    protected function currentDocument(Model $record): ?Media
    {
        return app(MediaService::class)->first($record, MediaCollections::FILE);
    }
}
