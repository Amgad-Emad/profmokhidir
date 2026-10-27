<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Media;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Constants\Uploads;
use App\Http\Requests\Admin\ImageDetailsRequest;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\Album;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Services\AlbumService;
use App\Services\MediaService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Every image on the site in one place: search and filter, upload new photos
 * into an album, edit the brand details (alt text, caption, place, year,
 * treatment), and delete after seeing where the image is used.
 */
class Library extends Component
{
    use AuthorizesRequests, ConfirmsActions, NotifiesEditors, WithFileUploads, WithPagination;

    public const string ACTION_DELETE = 'delete';

    public const int PER_PAGE = 24;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $collection = '';

    #[Url(except: '')]
    public string $treatment = '';

    #[Url(except: false)]
    public bool $incomplete = false;

    #[Url(as: 'missing_alt', except: false)]
    public bool $missingAlt = false;

    #[Url(except: false)]
    public bool $unused = false;

    #[Url(except: '')]
    public string $album = '';

    public const string SHOW_ALL = '';

    public const string SHOW_MISSING_ALT = 'missing-alt';

    public const string SHOW_INCOMPLETE = 'incomplete';

    public const string SHOW_UNUSED = 'unused';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $details = [];

    public ?int $uploadAlbumId = null;

    /** @var list<UploadedFile> */
    public $uploads = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Album::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCollection(): void
    {
        $this->resetPage();
    }

    public function updatedTreatment(): void
    {
        $this->resetPage();
    }

    public function updatedIncomplete(): void
    {
        $this->resetPage();
    }

    public function updatedAlbum(): void
    {
        $this->uploadAlbumId = $this->album === '' ? $this->uploadAlbumId : (int) $this->album;
        $this->resetPage();
    }

    /**
     * The "Show" chips: everything, images without alt text, images still
     * missing a detail, or images nothing on the site uses.
     */
    public function show(string $which = self::SHOW_ALL): void
    {
        $this->missingAlt = $which === self::SHOW_MISSING_ALT;
        $this->incomplete = $which === self::SHOW_INCOMPLETE;
        $this->unused = $which === self::SHOW_UNUSED;
        $this->resetPage();
    }

    public function showing(): string
    {
        return match (true) {
            $this->missingAlt => self::SHOW_MISSING_ALT,
            $this->incomplete => self::SHOW_INCOMPLETE,
            $this->unused => self::SHOW_UNUSED,
            default => self::SHOW_ALL,
        };
    }

    public function edit(int $id): void
    {
        $media = $this->media()->findOrFail($id);
        $this->authorizeMedia($media);

        $this->editingId = $id;
        $this->details = $this->media()->details($media);
        $this->details[MediaProperties::TREATMENT] ??= MediaTreatment::Archive->value;
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->details = [];
        $this->resetErrorBag();
    }

    public function saveDetails(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $media = $this->media()->findOrFail($this->editingId);
        $this->authorizeMedia($media);

        $required = $this->ownerIsPublished($media);
        $request = new ImageDetailsRequest;

        $this->validate(
            $this->prefix(ImageDetailsRequest::rulesFor($required), 'details.'),
            $this->prefix($request->messages(), 'details.'),
            $this->prefix($request->attributes(), 'details.'),
        );

        $this->media()->updateProperties($media, $this->details, auth()->user());
        $this->cancelEdit();
        $this->toast(__('Image details saved.'));
    }

    public function updatedUploads(): void
    {
        try {
            $this->validate(
                ['uploadAlbumId' => ['required', 'integer'], 'uploads' => ['array', 'max:20'], 'uploads.*' => Uploads::imageRules()],
                ['uploadAlbumId.required' => __('Choose the album the images belong to.')] + $this->remap((new ImageUploadRequest)->messages(), ImageUploadRequest::FIELD, 'uploads.*'),
                ['uploads.*' => __('image')],
            );
        } catch (ValidationException $exception) {
            // Livewire appends to array file properties; drop the rejected batch so the next choice starts clean.
            $this->uploads = [];

            throw $exception;
        }

        $album = app(AlbumService::class)->find((int) $this->uploadAlbumId);

        if ($album === null) {
            $this->addError('uploadAlbumId', __('That album no longer exists.'));

            return;
        }

        $this->authorize('manageMedia', $album);

        $files = array_values(array_filter(is_array($this->uploads) ? $this->uploads : [$this->uploads]));

        foreach ($files as $file) {
            app(AlbumService::class)->addPhoto($album, $file, [MediaProperties::TREATMENT => MediaTreatment::Archive->value]);
        }

        $this->uploads = [];
        $this->album = (string) $album->getKey();
        $this->show();
        $this->toast(trans_choice('{1} Image uploaded to :album. Open it to add alt text and a caption.|[2,*] :count images uploaded to :album. Open each to add alt text and a caption.', count($files), ['album' => $album->title]));
    }

    public function delete(): void
    {
        if (! $this->isConfirming(self::ACTION_DELETE)) {
            return;
        }

        $media = $this->media()->findOrFail((int) $this->confirmingId);
        $this->authorizeMedia($media);

        $this->media()->remove($media, auth()->user());
        $this->cancelConfirmation();

        if ($this->editingId === (int) $media->getKey()) {
            $this->cancelEdit();
        }

        $this->toast(__('Image deleted.'));
    }

    public function render(): View
    {
        $filters = [
            MediaRepositoryInterface::FILTER_COLLECTION => $this->collection,
            MediaRepositoryInterface::FILTER_TREATMENT => $this->treatment,
            MediaRepositoryInterface::FILTER_INCOMPLETE => $this->incomplete,
            MediaRepositoryInterface::FILTER_MISSING_ALT => $this->missingAlt,
            MediaRepositoryInterface::FILTER_UNUSED => $this->unused,
            MediaRepositoryInterface::FILTER_ALBUM => $this->album === '' ? null : (int) $this->album,
        ];

        $images = $this->media()->library($this->search, $filters, self::PER_PAGE);
        $usages = [];
        $missing = [];

        foreach ($images as $image) {
            $usages[(int) $image->getKey()] = $this->media()->usage($image);
            $missing[(int) $image->getKey()] = $this->media()->missingForPublishing($image);
        }

        $confirming = $this->isConfirming(self::ACTION_DELETE) ? $this->media()->find((int) $this->confirmingId) : null;

        return view('livewire.admin.media.library', [
            'images' => $images,
            'usages' => $usages,
            'missing' => $missing,
            'editing' => $this->editingId === null ? null : $this->media()->find($this->editingId),
            'editingRequired' => $this->editingId !== null && ($editing = $this->media()->find($this->editingId)) !== null && $this->ownerIsPublished($editing),
            'confirming' => $confirming,
            'confirmingUsage' => $confirming === null ? null : $this->media()->usage($confirming),
            'collections' => $this->collectionLabels(),
            'treatments' => MediaTreatment::labels(),
            'albums' => app(AlbumService::class)->search(null, [], 100)->getCollection()->pluck('title', 'id')->all(),
            'incompleteCount' => $this->media()->countIncompleteImages(),
            'missingAltCount' => $this->media()->countMissingAltText(),
            'showOptions' => [
                self::SHOW_ALL => __('All images'),
                self::SHOW_MISSING_ALT => __('Missing alt text'),
                self::SHOW_INCOMPLETE => __('Missing details'),
                self::SHOW_UNUSED => __('Not used on the site'),
            ],
            'filtered' => trim($this->search) !== '' || $this->collection !== '' || $this->treatment !== '' || $this->album !== '' || $this->showing() !== self::SHOW_ALL,
            'uploadAccept' => '.'.implode(',.', Uploads::IMAGE_EXTENSIONS),
            'uploadHint' => __('JPEG, PNG or WebP up to :size', ['size' => Uploads::imageMaxLabel()]),
        ])->layout('components.layouts.admin', ['title' => __(AdminNavigation::get(AdminNavigation::MEDIA_LIBRARY)['label'])]);
    }

    /**
     * @return array<string, string>
     */
    private function collectionLabels(): array
    {
        return [
            MediaCollections::PORTRAIT => __('Portrait'),
            MediaCollections::THUMBNAIL => __('Video posters'),
            MediaCollections::PHOTOS => __('Album photos'),
            MediaCollections::MEDIA => __('Other information'),
        ];
    }

    private function ownerIsPublished(Media $media): bool
    {
        $owner = $media->model;

        return $owner !== null && $owner->getAttribute('status') === ContentStatus::Published;
    }

    private function authorizeMedia(Media $media): void
    {
        $owner = $media->model;
        $type = ContentTypes::keyForModel((string) $media->model_type);

        if ($owner !== null) {
            $this->authorize('manageMedia', $owner);

            return;
        }

        $this->authorize('create', $type === null ? Album::class : ContentTypes::get($type)['model']);
    }

    private function media(): MediaService
    {
        return app(MediaService::class);
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, mixed>
     */
    private function prefix(array $items, string $prefix): array
    {
        $prefixed = [];

        foreach ($items as $key => $value) {
            $prefixed[str_starts_with($key, '*.') ? $key : $prefix.$key] = $value;
        }

        return $prefixed;
    }

    /**
     * @param  array<string, string>  $messages
     * @return array<string, string>
     */
    private function remap(array $messages, string $from, string $to): array
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
