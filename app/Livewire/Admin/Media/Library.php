<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Media;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\DataTable;
use App\Constants\ListToolbar;
use App\Constants\Locales;
use App\Constants\MediaCollections;
use App\Constants\MediaConversions;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Constants\StatusTone;
use App\Constants\Uploads;
use App\Http\Requests\Admin\ImageDetailsRequest;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\Album;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Services\AlbumService;
use App\Services\DataTableService;
use App\Services\MediaService;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use App\Support\DataTable\RowAction;
use App\Support\FileSize;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
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
 * Every image on the site in one place under the list toolbar (views for what
 * still needs work; Album, Used as, Treatment, Alt text and Usage filters), upload new photos
 * into an album, edit the brand details (alt text, caption, place, year,
 * treatment), replace the file (keeping those details and where it is used),
 * and delete after seeing where the image is used.
 */
class Library extends Component
{
    use AuthorizesRequests, ConfirmsActions, NotifiesEditors, WithDataTable, WithFileUploads, WithListToolbar, WithPagination;

    public const string ACTION_DELETE = 'delete';

    public const int PER_PAGE = DataTable::DEFAULT_PAGE_SIZE;

    /** Tiles (the default) or the table. */
    #[Url(except: DataTable::LAYOUT_GRID)]
    public string $layout = DataTable::LAYOUT_GRID;

    public const string SHOW_ALL = ListToolbar::VIEW_ALL;

    public const string SHOW_MISSING_ALT = 'missing-alt';

    public const string SHOW_INCOMPLETE = 'incomplete';

    public const string SHOW_UNUSED = 'unused';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $details = [];

    public ?int $uploadAlbumId = null;

    /** @var list<UploadedFile> */
    public $uploads = [];

    /** @var UploadedFile|null the file chosen to replace the image being edited */
    public $replacement = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Album::class);
    }

    protected function listScreen(): string
    {
        return AdminNavigation::MEDIA_LIBRARY;
    }

    protected function listFilters(): array
    {
        return [
            ListFilter::single(MediaRepositoryInterface::FILTER_ALBUM, __('Album'), $this->albums()),
            ListFilter::multi(MediaRepositoryInterface::FILTER_COLLECTION, __('Used as'), $this->collectionLabels()),
            ListFilter::single(MediaRepositoryInterface::FILTER_TREATMENT, __('Treatment'), MediaTreatment::labels()),
            ListFilter::flag(MediaRepositoryInterface::FILTER_MISSING_ALT, __('Alt text'), __('Missing alt text'), __('Missing alt text')),
            ListFilter::flag(MediaRepositoryInterface::FILTER_INCOMPLETE, __('Details'), __('Missing details'), __('Missing details'), toolbar: false),
            ListFilter::flag(MediaRepositoryInterface::FILTER_UNUSED, __('Usage'), __('Not used on the site'), __('Unused')),
        ];
    }

    /**
     * Every image, then the ones that still need work or that nothing shows.
     */
    protected function listViews(): array
    {
        return [
            new ListView(self::SHOW_ALL, __('All images')),
            new ListView(self::SHOW_MISSING_ALT, __('Missing alt text'), [MediaRepositoryInterface::FILTER_MISSING_ALT => ListToolbar::FLAG_ON]),
            new ListView(self::SHOW_INCOMPLETE, __('Missing details'), [MediaRepositoryInterface::FILTER_INCOMPLETE => ListToolbar::FLAG_ON]),
            new ListView(self::SHOW_UNUSED, __('Unused'), [MediaRepositoryInterface::FILTER_UNUSED => ListToolbar::FLAG_ON]),
        ];
    }

    protected function listSorts(): array
    {
        return [
            new ListSort('', ListToolbar::SORT_DESC, __('Newest first')),
            new ListSort('created_at', ListToolbar::SORT_ASC, __('Oldest first')),
            new ListSort('file_name', ListToolbar::SORT_ASC, __('File name, A to Z')),
        ];
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->media()->countLibrary($search, $criteria);
    }

    /**
     * The "Show" views, kept under their old name.
     */
    public function show(string $which = self::SHOW_ALL): void
    {
        $this->applyView($which === '' ? self::SHOW_ALL : $which);
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
        $this->replacement = null;
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

    /**
     * A new file was chosen in the details drawer: put it in place of the
     * image being edited, keeping its details and everywhere it is used.
     */
    public function updatedReplacement(): void
    {
        if ($this->editingId === null) {
            $this->replacement = null;

            return;
        }

        $media = $this->media()->findOrFail($this->editingId);
        $this->authorizeMedia($media);

        try {
            $this->validate(
                ['replacement' => $this->media()->replacementRules($media)],
                $this->remap((new ImageUploadRequest)->messages(), ImageUploadRequest::FIELD, 'replacement'),
                ['replacement' => __('image')],
            );
        } catch (ValidationException $exception) {
            $this->replacement = null;

            throw $exception;
        }

        $this->media()->replace($media, $this->replacement, auth()->user());
        $this->replacement = null;
        $this->toast(__('Image replaced. Its details were kept.'));
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
        $this->applyFilterState([MediaRepositoryInterface::FILTER_ALBUM => (string) $album->getKey()]);
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

    public function setLayout(string $layout): void
    {
        $this->layout = $layout === DataTable::LAYOUT_LIST ? DataTable::LAYOUT_LIST : DataTable::LAYOUT_GRID;
    }

    /**
     * The table of the list layout: the image with its caption and where it is
     * used, whether its details are complete, its size and when it came in.
     */
    protected function dataTableColumns(): array
    {
        return [
            Column::primary('file_name', __('Image'), sortable: true),
            Column::badges('details', __('Details'), width: '10rem', priority: DataTable::PRIORITY_ESSENTIAL),
            Column::number('size', __('Size'), width: '6rem', priority: DataTable::PRIORITY_DESKTOP),
            Column::relative('created_at', __('Uploaded')),
        ];
    }

    /**
     * @param  array<string, mixed>  $usage
     * @param  list<string>  $missing
     */
    private function row(Media $image, array $usage, array $missing): Row
    {
        $id = (int) $image->getKey();
        $alt = $image->getTranslatedProperty(MediaProperties::ALT_TEXT, Locales::DEFAULT);
        $details = match (true) {
            blank($alt) => ['label' => __('Needs alt text'), 'tone' => StatusTone::Bronze],
            $missing !== [] => ['label' => __('Needs details'), 'tone' => StatusTone::Bronze],
            default => ['label' => __('Ready'), 'tone' => StatusTone::Cypress],
        };

        return new Row(
            id: $id,
            title: $image->getCustomProperty(MediaProperties::CAPTION) ?: $image->file_name,
            supporting: [$usage['type_label'].DataTable::SEPARATOR.$usage['label'].($usage['trashed'] ? ' '.__('(in trash)') : ''), $image->file_name],
            thumbnail: $image->hasGeneratedConversion(MediaConversions::SMALL) ? $image->getUrl(MediaConversions::SMALL) : $image->getUrl(),
            badges: $image->needsTranslation() ? [['label' => __('Needs Arabic'), 'tone' => StatusTone::Bronze, 'hook' => 'data-needs-arabic']] : [],
            cells: [
                'details' => Cell::badges([$details]),
                'size' => Cell::text(FileSize::format((int) $image->size)),
                'created_at' => app(DataTableService::class)->relative($image->created_at),
            ],
            actions: [
                RowAction::wire(__('Image details'), 'pencil', 'edit', [$id], inline: true, hook: 'data-edit-image'),
                RowAction::wire(__('Delete image'), 'trash', 'confirm', [self::ACTION_DELETE, $id], danger: true, hook: 'data-delete-image'),
            ],
            attributes: ['data-media' => $id],
        );
    }

    public function render(): View
    {
        if ($this->uploadAlbumId === null && isset($this->filters[MediaRepositoryInterface::FILTER_ALBUM])) {
            $this->uploadAlbumId = (int) $this->filters[MediaRepositoryInterface::FILTER_ALBUM];
        }

        $images = $this->media()->library($this->search, $this->listCriteria(), $this->perPage, $this->sort ?: null, $this->direction);
        $usages = [];
        $missing = [];

        foreach ($images as $image) {
            $usages[(int) $image->getKey()] = $this->media()->usage($image);
            $missing[(int) $image->getKey()] = $this->media()->missingDetails($image);
        }

        $confirming = $this->isConfirming(self::ACTION_DELETE) ? $this->media()->find((int) $this->confirmingId) : null;

        return view('livewire.admin.media.library', [
            'images' => $images,
            'listLayout' => $this->layout === DataTable::LAYOUT_LIST,
            'table' => $this->dataTable($images, $images->getCollection()->map(fn (Media $image): Row => $this->row($image, $usages[(int) $image->getKey()], $missing[(int) $image->getKey()]))->all(), [
                'caption' => __('Media library'),
                'id' => 'media-table',
            ]),
            'usages' => $usages,
            'missing' => $missing,
            'editing' => $this->editingId === null ? null : $this->media()->find($this->editingId),
            'editingRequired' => $this->editingId !== null && ($editing = $this->media()->find($this->editingId)) !== null && $this->ownerIsPublished($editing),
            'confirming' => $confirming,
            'confirmingUsage' => $confirming === null ? null : $this->media()->usage($confirming),
            'albums' => $this->albums(),
            'toolbar' => $this->listToolbar($images->total()),
            'filtered' => $this->listFiltered(),
            'uploadAccept' => '.'.implode(',.', Uploads::IMAGE_EXTENSIONS),
            'uploadHint' => __('JPEG, PNG or WebP up to :size', ['size' => Uploads::imageMaxLabel()]),
        ])->layout('components.layouts.admin', ['title' => __(AdminNavigation::get(AdminNavigation::MEDIA_LIBRARY)['label'])]);
    }

    /**
     * Album id => title, for the upload and the Album filter.
     *
     * @return array<int, string>
     */
    private function albums(): array
    {
        return app(AlbumService::class)->search(null, [], 100)->getCollection()->pluck('title', 'id')->all();
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
            MediaCollections::AWARD_IMAGES => __('Award certificates'),
            MediaCollections::MEDIA => __('Other information'),
            MediaCollections::PAGE_IMAGES => __('Page images'),
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
