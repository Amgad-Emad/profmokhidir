<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Videos;

use App\Constants\Archive;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\MediaTreatment;
use App\Constants\Uploads;
use App\Constants\VideoProvider;
use App\Http\Requests\Admin\ContentRequest;
use App\Http\Requests\Admin\VideoRequest;
use App\Livewire\Admin\Concerns\ManagesImages;
use App\Livewire\Admin\ContentForm;
use App\Models\Video;
use App\Services\AbstractContentService;
use App\Services\MediaService;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Form extends ContentForm
{
    use ManagesImages;

    /** @var UploadedFile|null */
    public $videoFile = null;

    public function mount(?Video $video = null): void
    {
        $this->initialise($video);
        $this->loadImages();
    }

    protected function type(): string
    {
        return ContentTypes::VIDEO;
    }

    protected function service(): AbstractContentService
    {
        return app(VideoService::class);
    }

    protected function request(): ContentRequest
    {
        return new VideoRequest($this->recordId);
    }

    protected function defaults(): array
    {
        return [
            'title' => '',
            'slug' => null,
            'date' => null,
            'decade' => null,
            'is_featured' => false,
            'event' => null,
            'location' => null,
            'description' => null,
            'provider' => VideoProvider::YouTube->value,
            'media_url' => null,
            'captions_url' => null,
            'status' => ContentStatus::Draft->value,
        ];
    }

    protected function view(): string
    {
        return 'livewire.admin.videos.form';
    }

    protected function viewData(): array
    {
        $record = $this->record();
        $videoFile = $record === null ? null : app(MediaService::class)->first($record, MediaCollections::VIDEO);

        return $this->imagesViewData() + [
            'providers' => VideoProvider::labels(),
            'decades' => Archive::decadeOptions(),
            'videoFile' => $videoFile,
            'videoFileUrl' => $videoFile?->getUrl(),
            'videoAccept' => '.'.implode(',.', Uploads::VIDEO_EXTENSIONS),
            'videoHint' => __('MP4 or WebM up to :size', ['size' => Uploads::documentMaxLabel()]),
        ];
    }

    public function updatedVideoFile(): void
    {
        $this->validate(['videoFile' => Uploads::videoRules()], ['videoFile.mimes' => __('Use an MP4 or WebM video.'), 'videoFile.max' => __('Videos can be at most :size.', ['size' => Uploads::documentMaxLabel()])], ['videoFile' => __('video file')]);

        $record = $this->record();

        if ($record === null || $this->videoFile === null) {
            return;
        }

        $this->authorize('manageMedia', $record);
        app(VideoService::class)->setVideoFile($record, $this->videoFile);
        $this->videoFile = null;
        $this->notify('Video file uploaded.');
    }

    public function removeVideoFile(): void
    {
        $record = $this->record();

        if ($record === null) {
            return;
        }

        $this->authorize('manageMedia', $record);
        app(VideoService::class)->removeMedia($record, MediaCollections::VIDEO, auth()->user());
        $this->notify('Video file removed.');
    }

    protected function afterCreate(Model $record): void
    {
        if ($this->videoFile instanceof UploadedFile) {
            app(VideoService::class)->setVideoFile($record, $this->videoFile);
            $this->videoFile = null;
        }
    }

    protected function imageCollection(): string
    {
        return MediaCollections::THUMBNAIL;
    }

    protected function defaultTreatment(): MediaTreatment
    {
        return MediaTreatment::Archive;
    }

    protected function attachImage(Model $record, UploadedFile $file, array $details): Media
    {
        return app(VideoService::class)->setThumbnail($record, $file, $details);
    }
}
