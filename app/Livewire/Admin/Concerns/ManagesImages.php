<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Constants\ContentStatus;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Constants\Uploads;
use App\Http\Requests\Admin\ImageDetailsRequest;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Images of a record (portrait, thumbnail, album photos, other-item media):
 * upload with validation, edit the brand details per image, remove. Details
 * are required as soon as the owning record is published.
 */
trait ManagesImages
{
    use WithFileUploads;

    /** @var list<UploadedFile>|UploadedFile|null */
    public $newImages = [];

    /** @var array<int, array<string, mixed>> media id => details */
    public array $images = [];

    public ?int $confirmingImageId = null;

    abstract protected function imageCollection(): string;

    abstract protected function attachImage(Model $record, UploadedFile $file, array $details): Media;

    abstract protected function defaultTreatment(): MediaTreatment;

    protected function loadImages(): void
    {
        $record = $this->record();
        $this->images = [];

        if (! $record instanceof HasMedia) {
            return;
        }

        foreach ($this->mediaService()->inCollection($record, $this->imageCollection()) as $image) {
            $this->images[(int) $image->getKey()] = $this->detailsOf($image);
        }
    }

    public function updatedNewImages(): void
    {
        $files = is_array($this->newImages) ? $this->newImages : [$this->newImages];
        $files = array_values(array_filter($files));

        try {
            $this->validate(
                ['newImages' => ['array', 'max:20'], 'newImages.*' => Uploads::imageRules()],
                $this->prefixMessages((new ImageUploadRequest)->messages(), ImageUploadRequest::FIELD, 'newImages.*'),
                ['newImages.*' => 'image'],
            );
        } catch (ValidationException $exception) {
            // Livewire appends to array file properties; drop the rejected batch so the next choice starts clean.
            $this->newImages = [];

            throw $exception;
        }

        $record = $this->record();

        if (! $record instanceof HasMedia) {
            return;
        }

        $this->authorize('manageMedia', $record);

        foreach ($files as $file) {
            $this->attachImage($record, $file, [MediaProperties::TREATMENT => $this->defaultTreatment()->value]);
        }

        $this->newImages = [];
        $this->loadImages();
        $this->notify(count($files) === 1 ? 'Image uploaded. Add its details below.' : count($files).' images uploaded. Add their details below.');
    }

    public function saveImage(int $id): void
    {
        $record = $this->record();
        $image = $this->mediaService()->find($id);

        if ($record === null || $image === null || ! array_key_exists($id, $this->images)) {
            return;
        }

        $this->authorize('manageMedia', $record);

        $required = $record->getAttribute('status') === ContentStatus::Published;
        $request = new ImageDetailsRequest;

        $this->validate(
            $this->prefixRules(ImageDetailsRequest::rulesFor($required), "images.$id."),
            $this->prefixMessagesAll($request->messages(), "images.$id."),
            $this->prefixMessagesAll($request->attributes(), "images.$id."),
        );

        $this->mediaService()->updateProperties($image, $this->images[$id], auth()->user());
        $this->loadImages();
        $this->notify('Image details saved.');
    }

    public function confirmRemoveImage(int $id): void
    {
        $this->confirmingImageId = $id;
    }

    public function cancelRemoveImage(): void
    {
        $this->confirmingImageId = null;
    }

    public function removeImage(): void
    {
        $record = $this->record();
        $image = $this->confirmingImageId === null ? null : $this->mediaService()->find($this->confirmingImageId);
        $this->confirmingImageId = null;

        if ($record === null || $image === null) {
            return;
        }

        $this->authorize('manageMedia', $record);
        $this->mediaService()->remove($image, auth()->user());
        $this->loadImages();
        $this->notify('Image removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function detailsOf(Media $image): array
    {
        $details = [];

        foreach ([MediaProperties::ALT_TEXT, MediaProperties::CAPTION, MediaProperties::PLACE, MediaProperties::YEAR, MediaProperties::TREATMENT, MediaProperties::CREDIT, MediaProperties::PERMISSIONS_NOTE] as $key) {
            $details[$key] = $image->getCustomProperty($key);
        }

        $details[MediaProperties::TREATMENT] ??= $this->defaultTreatment()->value;

        return $details;
    }

    /**
     * @return array<string, mixed>
     */
    protected function imagesViewData(): array
    {
        $record = $this->record();
        $media = $record instanceof HasMedia ? $this->mediaService()->inCollection($record, $this->imageCollection()) : collect();
        $missing = [];

        foreach ($media as $image) {
            $missing[(int) $image->getKey()] = $this->mediaService()->missingForPublishing($image);
        }

        return [
            'media' => $media->keyBy(fn (Media $image): int => (int) $image->getKey()),
            'missing' => $missing,
            'imageAccept' => '.'.implode(',.', Uploads::IMAGE_EXTENSIONS),
            'imageHint' => 'JPEG, PNG or WebP up to '.Uploads::imageMaxLabel(),
            'detailsRequired' => $record?->getAttribute('status') === ContentStatus::Published,
        ];
    }

    protected function mediaService(): MediaService
    {
        return app(MediaService::class);
    }

    /**
     * @param  array<string, list<mixed>>  $rules
     * @return array<string, list<mixed>>
     */
    private function prefixRules(array $rules, string $prefix): array
    {
        $prefixed = [];

        foreach ($rules as $field => $fieldRules) {
            $prefixed[$prefix.$field] = $fieldRules;
        }

        return $prefixed;
    }

    /**
     * @param  array<string, string>  $messages
     * @return array<string, string>
     */
    private function prefixMessagesAll(array $messages, string $prefix): array
    {
        $prefixed = [];

        foreach ($messages as $key => $message) {
            $prefixed[str_starts_with($key, '*.') ? $key : $prefix.$key] = $message;
        }

        return $prefixed;
    }

    /**
     * @param  array<string, string>  $messages
     * @return array<string, string>
     */
    private function prefixMessages(array $messages, string $from, string $to): array
    {
        $mapped = [];

        foreach ($messages as $key => $message) {
            if (str_starts_with($key, $from.'.')) {
                $mapped[$to.substr($key, strlen($from))] = $message;
            }
        }

        return $mapped;
    }
}
