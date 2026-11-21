<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Awards;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\MediaTreatment;
use App\Http\Requests\Admin\AwardRequest;
use App\Http\Requests\Admin\ContentRequest;
use App\Livewire\Admin\Concerns\ManagesImages;
use App\Livewire\Admin\ContentForm;
use App\Models\Award;
use App\Services\AbstractContentService;
use App\Services\AwardService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Form extends ContentForm
{
    use ManagesImages {
        saveImage as private saveImageDetails;
        removeImage as private removeConfirmedImage;
    }

    public function mount(?Award $award = null): void
    {
        $this->initialise($award);
        $this->loadImages();
    }

    /**
     * Image details show on the cached roll of honour, so saving them clears it.
     */
    public function saveImage(int $id): void
    {
        $this->saveImageDetails($id);
        app(AwardService::class)->flushCache();
    }

    public function removeImage(): void
    {
        $this->removeConfirmedImage();
        app(AwardService::class)->flushCache();
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

    protected function viewData(): array
    {
        return $this->imagesViewData();
    }

    protected function imageCollection(): string
    {
        return MediaCollections::AWARD_IMAGES;
    }

    protected function defaultTreatment(): MediaTreatment
    {
        return MediaTreatment::Archive;
    }

    protected function attachImage(Model $record, UploadedFile $file, array $details): Media
    {
        return app(AwardService::class)->addImage($record, $file, $details);
    }
}
