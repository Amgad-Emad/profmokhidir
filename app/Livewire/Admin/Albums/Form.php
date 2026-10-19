<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Albums;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Http\Requests\Admin\AlbumRequest;
use App\Http\Requests\Admin\ContentRequest;
use App\Livewire\Admin\Concerns\ManagesImages;
use App\Livewire\Admin\ContentForm;
use App\Models\Album;
use App\Services\AbstractContentService;
use App\Services\AlbumService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Form extends ContentForm
{
    use ManagesImages;

    public function mount(?Album $album = null): void
    {
        $this->initialise($album);
        $this->loadImages();
    }

    protected function type(): string
    {
        return ContentTypes::ALBUM;
    }

    protected function service(): AbstractContentService
    {
        return app(AlbumService::class);
    }

    protected function request(): ContentRequest
    {
        return new AlbumRequest($this->recordId);
    }

    protected function defaults(): array
    {
        return [
            'title' => '',
            'slug' => null,
            'description' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.albums.form';
    }

    protected function viewData(): array
    {
        $data = $this->imagesViewData();
        $data['coverId'] = $data['media']->first(fn (Media $photo): bool => (bool) $photo->getCustomProperty(MediaProperties::IS_COVER, false))?->getKey();

        return $data;
    }

    public function setCover(int $id): void
    {
        $record = $this->record();
        $photo = $this->mediaService()->find($id);

        if ($record === null || $photo === null) {
            return;
        }

        $this->authorize('manageMedia', $record);
        app(AlbumService::class)->setCover($record, $photo);
        $this->notify('Cover photo set.');
    }

    public function movePhotoUp(int $id): void
    {
        $this->movePhoto($id, -1);
    }

    public function movePhotoDown(int $id): void
    {
        $this->movePhoto($id, 1);
    }

    /**
     * @param  list<int|string>  $orderedIds
     */
    public function reorderPhotos(array $orderedIds): void
    {
        $record = $this->record();

        if ($record === null) {
            return;
        }

        $this->authorize('manageMedia', $record);
        app(AlbumService::class)->reorderPhotos(array_map('intval', $orderedIds));
        $this->loadImages();
        $this->notify('Photo order saved.');
    }

    private function movePhoto(int $id, int $offset): void
    {
        $ids = array_keys($this->images);
        $from = array_search($id, $ids, true);

        if ($from === false) {
            return;
        }

        $to = max(0, min(count($ids) - 1, $from + $offset));

        if ($to === $from) {
            return;
        }

        [$moved] = array_splice($ids, $from, 1);
        array_splice($ids, $to, 0, [$moved]);

        $this->reorderPhotos($ids);
    }

    protected function imageCollection(): string
    {
        return MediaCollections::PHOTOS;
    }

    protected function defaultTreatment(): MediaTreatment
    {
        return MediaTreatment::Archive;
    }

    protected function attachImage(Model $record, UploadedFile $file, array $details): Media
    {
        return app(AlbumService::class)->addPhoto($record, $file, $details);
    }
}
