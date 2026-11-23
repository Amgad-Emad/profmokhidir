<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Constants\AdminUx;
use App\Constants\ContentStatus;
use App\Constants\ErrorPages;
use App\Constants\Locales;
use App\Constants\PageBlocks;
use App\Constants\PageBlockType;
use App\Constants\PageLinks;
use App\Constants\Pages;
use App\Constants\RevisionKind;
use App\Constants\Uploads;
use App\Http\Requests\Admin\PageRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\Page;
use App\Services\PageRecordsService;
use App\Services\PageService;
use App\Services\PreviewService;
use App\Services\RevisionService;
use App\Support\PageBlockDefinition;
use App\Support\PageBlockValue;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The edit screen of one page (Admin › Pages): its blocks laid out in the
 * order they appear on the site, each labelled in plain words, with the
 * shared save bar, autosave, version history and the real page previewed
 * beside the form with the draft shown. Editors change words and images
 * only: every field is a declared block with its own limits, and an emptied
 * required block shows (and keeps) the built-in wording.
 *
 * Drafts sit beside what visitors see, so autosave works on a published page
 * too; Publish (or Publish changes) puts them live.
 */
class Edit extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithFileUploads;

    public const string FIELD_PREFIX = 'form.';

    /** The Arabic tab's fields: arabic.<block key>... */
    public const string ARABIC_PREFIX = 'arabic.';

    /** Revisions keep the Arabic tab under this key beside the blocks. */
    public const string REVISION_TRANSLATIONS = 'translations';

    #[Locked]
    public string $pageKey;

    /** @var array<string, mixed> block key => value, as PageBlockValue shapes it */
    public array $form = [];

    /** @var array<string, mixed> block key => its Arabic words (PageBlockValue::arabicForm) */
    public array $arabic = [];

    /** @var array<string, UploadedFile|null> image block key => the file being uploaded */
    public array $uploads = [];

    /** @var array<string, array<int, list<string>>> block => item index => groups added with "Add milestone" (empty ones stay out of sight otherwise) */
    public array $addedGroups = [];

    public bool $showHistory = false;

    /** An earlier version was loaded; the next save is recorded as a restore. */
    public bool $restoredRevision = false;

    public function mount(string $page): void
    {
        abort_unless(Pages::isValid($page), 404);

        $record = $this->pages()->findByKey($page);

        if ($record === null) {
            $this->pages()->install();
            $record = $this->pages()->findByKey($page);
        }

        $this->authorize('update', $record);
        $this->pageKey = $page;
        $this->form = $this->pages()->formValues($record);
        $this->arabic = $this->pages()->arabicFormValues($record, $this->form);
    }

    public function record(): Page
    {
        return $this->pages()->findByKey($this->pageKey) ?? abort(404);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->request()->rules() as $field => $fieldRules) {
            $rules[self::FIELD_PREFIX.$field] = array_map(
                fn ($rule) => is_string($rule) && str_starts_with($rule, 'required_with:') ? 'required_with:'.self::FIELD_PREFIX.substr($rule, 14) : $rule,
                $fieldRules,
            );
        }

        foreach ($this->request()->arabicRules() as $field => $fieldRules) {
            $rules[self::ARABIC_PREFIX.$field] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->request()->messages() as $key => $message) {
            $messages[self::FIELD_PREFIX.$key] = $message;
        }

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->request()->attributes() as $field => $label) {
            $attributes[self::FIELD_PREFIX.$field] = $label;
        }

        foreach ($this->request()->arabicAttributes() as $field => $label) {
            $attributes[self::ARABIC_PREFIX.$field] = $label;
        }

        return $attributes;
    }

    /**
     * Inline validation as a field is left; an image chosen is uploaded at once.
     */
    public function updated(string $name): void
    {
        if (str_starts_with($name, self::FIELD_PREFIX) || str_starts_with($name, self::ARABIC_PREFIX)) {
            $this->validateOnly($name);
        }

        if (str_starts_with($name, 'uploads.')) {
            $this->upload(substr($name, strlen('uploads.')));
        }
    }

    /**
     * Keep the edits as a draft (Ctrl/Cmd+S). Visitors keep the published wording.
     */
    public function save(): void
    {
        $this->authorize('update', $this->record());
        $this->validateForm();

        $page = $this->pages()->saveDraft($this->record(), $this->form, $this->arabic);
        $this->afterSave($page, $this->restoredRevision ? RevisionKind::Restore : RevisionKind::Save);
        $this->toast($page->isPublished()
            ? __('Draft saved. Visitors still see the published wording until you publish the changes.')
            : __('Draft saved. Visitors see the original wording until the page is published.'));
    }

    /**
     * Put the edits live (Ctrl/Cmd+Enter).
     */
    public function publish(): void
    {
        $record = $this->record();
        $this->authorize('publish', $record);
        $this->validateForm();

        $wasPublished = $record->isPublished();
        $page = $this->pages()->publish($record, $this->form, auth()->user(), $this->arabic);
        $this->afterSave($page, RevisionKind::Publish);
        $this->toast($wasPublished ? __('Changes published. They are on the public site now.') : __('Page published. Visitors see these words now.'));
    }

    /**
     * Take one section (by its slug) off the public page, or put it back, at once. Its words stay as they are.
     */
    public function toggleSectionVisibility(string $slug): void
    {
        $record = $this->record();
        $this->authorize('publish', $record);

        $section = collect(PageBlocks::hideableSections($this->pageKey))->first(fn (string $title): bool => Str::slug($title) === $slug);

        if ($section === null) {
            return;
        }

        $hide = ! in_array($section, $record->hiddenSections(), true);
        $this->pages()->setSectionHidden($record, $section, $hide, auth()->user());
        $this->dispatch(AdminUx::EVENT_SAVED, at: now()->toIso8601String());
        $this->toast($hide
            ? __('“:section” is hidden. Visitors no longer see it; its words are kept.', ['section' => __($section)])
            : __('“:section” is back on the page.', ['section' => __($section)]));
    }

    /**
     * Show visitors the original wording again, keeping every edit for later.
     */
    public function unpublish(): void
    {
        $record = $this->record();
        $this->authorize('publish', $record);

        $this->pages()->unpublish($record, auth()->user());
        $this->dispatch(AdminUx::EVENT_SAVED, at: now()->toIso8601String());
        $this->toast(__('Page moved back to draft. Visitors see the original wording.'));
    }

    /**
     * Quietly keep a draft (every AdminUx::AUTOSAVE_SECONDS while there are
     * changes, and when a field is left). Never publishes. With invalid fields
     * nothing is saved and the save bar says why.
     */
    public function autosave(): void
    {
        $this->authorize('update', $this->record());
        $validator = Validator::make(['form' => $this->form, 'arabic' => $this->arabic], $this->rules(), $this->messages(), $this->validationAttributes());

        if ($validator->fails()) {
            $this->dispatch(AdminUx::EVENT_SAVED, blocked: __('Not saved yet: :reason', ['reason' => rtrim((string) $validator->errors()->first(), '.')]));

            return;
        }

        $page = $this->pages()->saveDraft($this->record(), $this->form, $this->arabic);
        $this->afterSave($page, RevisionKind::Autosave);
    }

    /**
     * Drop the unpublished changes: the form shows what visitors see again.
     */
    public function discardDraft(): void
    {
        $record = $this->record();
        $this->authorize('update', $record);

        $this->pages()->discardDraft($record);
        $this->form = $this->pages()->formValues($record);
        $this->arabic = $this->pages()->arabicFormValues($record, $this->form);
        $this->resetErrorBag();
        $this->dispatch(AdminUx::EVENT_SAVED, at: now()->toIso8601String());
        $this->toast(__('Unpublished changes discarded. The form shows the published wording.'), AdminUx::TOAST_INFO);
    }

    /**
     * Put one block back to its built-in wording (kept once saved).
     */
    public function restoreDefault(string $key): void
    {
        if (PageBlocks::has($this->pageKey, $key)) {
            $definition = PageBlocks::get($this->pageKey, $key);
            $this->form[$key] = PageBlockValue::normalise($definition, null);
            // The Arabic tab empties too, so Arabic pages show the original wording in Arabic.
            $this->arabic[$key] = PageBlockValue::arabicForm($definition, null, $this->form[$key]);
            $this->resetErrorBag(self::FIELD_PREFIX.$key);
            $this->resetErrorBag(self::ARABIC_PREFIX.$key);
            $this->dispatch(AdminUx::EVENT_CHANGED);
        }
    }

    public function addItem(string $key): void
    {
        $definition = $this->listDefinition($key);

        if ($definition === null || $definition->fixedItems || count($this->form[$key]) >= ($definition->maxItems ?? PHP_INT_MAX)) {
            return;
        }

        $item = $definition->blankItem();

        foreach ($definition->editableFields() as $field => $spec) {
            if ($spec['kind'] === PageBlockDefinition::FIELD_ROUTE) {
                $item[$field] = PageLinks::routes()[0];
            }
        }

        $this->form[$key][] = $item;
        $this->arabic[$key] = PageBlockValue::arabicForm($definition, $this->arabic[$key] ?? [], $this->form[$key]);
        $this->dispatch(AdminUx::EVENT_CHANGED);
    }

    public function removeItem(string $key, int $index): void
    {
        $definition = $this->listDefinition($key);

        // A list whose built-in default is empty (chapters, figures, cards) may be emptied completely.
        $minimum = (array) $definition?->default === [] ? 0 : 1;

        if ($definition === null || $definition->fixedItems || count($this->form[$key]) <= $minimum) {
            return;
        }

        unset($this->form[$key][$index], $this->arabic[$key][$index]);
        $this->form[$key] = array_values($this->form[$key]);
        $this->arabic[$key] = array_values((array) ($this->arabic[$key] ?? []));
        $this->resetErrorBag();
        $this->dispatch(AdminUx::EVENT_CHANGED);
    }

    /**
     * Move a list item up (-1) or down (+1).
     */
    public function moveItem(string $key, int $index, int $direction): void
    {
        $definition = $this->listDefinition($key);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($definition === null || ! $definition->sortable || ! isset($this->form[$key][$index], $this->form[$key][$target])) {
            return;
        }

        [$this->form[$key][$index], $this->form[$key][$target]] = [$this->form[$key][$target], $this->form[$key][$index]];

        if (isset($this->arabic[$key][$index], $this->arabic[$key][$target])) {
            [$this->arabic[$key][$index], $this->arabic[$key][$target]] = [$this->arabic[$key][$target], $this->arabic[$key][$index]];
        }
        $this->dispatch(AdminUx::EVENT_CHANGED);
    }

    /**
     * Show the next empty addable group of an item (a chapter's second, then third milestone).
     */
    public function addGroup(string $key, int $index, string $group): void
    {
        if ($this->listDefinition($key) === null || ! isset($this->form[$key][$index])) {
            return;
        }

        $this->addedGroups[$key][$index] = array_values(array_unique([...($this->addedGroups[$key][$index] ?? []), $group]));
    }

    /**
     * Take the image out of the block (the built-in image, or none, shows again once saved).
     */
    public function removeImage(string $key): void
    {
        if (! PageBlocks::has($this->pageKey, $key) || ! PageBlocks::get($this->pageKey, $key)->type->hasImage()) {
            return;
        }

        $this->authorize('manageMedia', $this->record());
        $mediaId = (int) ($this->form[$key]['media_id'] ?? 0);
        $this->form[$key]['media_id'] = null;
        $this->uploads[$key] = null;

        if ($mediaId > 0) {
            $this->pages()->removeImage($this->record(), $key, $mediaId, auth()->user());
        }

        $this->dispatch(AdminUx::EVENT_CHANGED);
    }

    /**
     * Load an earlier version into the form. Nothing is saved until the editor saves.
     */
    public function restoreRevision(int $revisionId): void
    {
        $record = $this->record();
        $this->authorize('update', $record);
        $data = app(RevisionService::class)->dataFor($record, $revisionId);

        if ($data === null) {
            return;
        }

        $arabic = $data[self::REVISION_TRANSLATIONS][Locales::AR] ?? [];

        foreach (PageBlocks::for($this->pageKey) as $key => $definition) {
            if (array_key_exists($key, $data)) {
                $this->form[$key] = PageBlockValue::normalise($definition, $data[$key]);
                $this->arabic[$key] = PageBlockValue::arabicForm($definition, $this->arabicStored($definition, $arabic[$key] ?? null), $this->form[$key]);
            }
        }

        $this->restoredRevision = true;
        $this->showHistory = false;
        $this->resetErrorBag();
        $this->dispatch(AdminUx::EVENT_CHANGED);
        $this->toast(__('Earlier version loaded into the form. Save to keep it, or leave without saving to discard it.'), AdminUx::TOAST_INFO);
    }

    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
    }

    public function render(): View
    {
        $record = $this->record();
        $definition = Pages::get($this->pageKey);

        return view('livewire.admin.pages.edit', [
            'page' => $record,
            'definition' => $definition,
            'blocks' => PageBlocks::for($this->pageKey),
            'sections' => PageBlocks::sections($this->pageKey),
            'images' => $this->images($record),
            'linkOptions' => PageLinks::options(),
            'hasDraft' => $this->pages()->hasDraft($record),
            'editor' => $this->editorState($record),
            'previewUrls' => $this->previewUrls($record),
            'records' => app(PageRecordsService::class)->for($this->pageKey),
            'hideableSections' => PageBlocks::hideableSections($this->pageKey),
            'hiddenSections' => $record->hiddenSections(),
            'imageHint' => __(':types, up to :size.', ['types' => implode(', ', array_map('strtoupper', Uploads::IMAGE_EXTENSIONS)), 'size' => Uploads::imageMaxLabel()]),
        ])->layout('components.layouts.admin', [
            'title' => __('Edit page: :page', ['page' => __($definition['title'])]),
            'crumb' => __($definition['title']),
        ]);
    }

    /**
     * The addresses the preview pane can show: the page, or each error page.
     *
     * @return array<string, string>
     */
    private function previewUrls(Page $page): array
    {
        $preview = app(PreviewService::class);

        if ($this->pageKey !== Pages::ERRORS) {
            return ['page' => $preview->pageUrl($page)];
        }

        $urls = [];

        foreach (ErrorPages::codes() as $code) {
            $urls[(string) $code] = $preview->pageUrl($page, $code);
        }

        return $urls;
    }

    /**
     * What the save bar and the version history need (the shape ContentForm::editorState() gives).
     *
     * @return array<string, mixed>
     */
    private function editorState(Page $page): array
    {
        $revisions = app(RevisionService::class);
        $route = Pages::get($this->pageKey)['route'];

        return [
            'status' => $page->status instanceof ContentStatus ? $page->status : ContentStatus::Draft,
            'savedAt' => $page->updated_at?->toIso8601String(),
            'previewUrl' => app(PreviewService::class)->pageUrl($page),
            'publicUrl' => $page->isPublished() && $route !== null && $this->pageKey !== Pages::PRIVACY ? route($route) : null,
            'revisions' => $this->showHistory ? $revisions->history($page) : collect(),
            'revisionCount' => $revisions->count($page),
            'placeholders' => [],
            'singular' => __('page'),
        ];
    }

    /**
     * The uploaded image each image block's form value names.
     *
     * @return array<string, Media|null>
     */
    private function images(Page $page): array
    {
        $images = [];

        foreach (PageBlocks::for($this->pageKey) as $key => $definition) {
            if ($definition->type->hasImage()) {
                $images[$key] = $this->pages()->imageFor($page, $key, $this->form[$key]['media_id'] ?? null);
            }

            // A list's item images, keyed "block.index".
            if ($definition->imageFields() !== []) {
                foreach ((array) ($this->form[$key] ?? []) as $index => $item) {
                    $images[$key.'.'.$index] = $this->pages()->listImage(is_array($item) ? ($item['media_id'] ?? null) : null);
                }
            }
        }

        return $images;
    }

    private function upload(string $key): void
    {
        if (str_contains($key, '.')) {
            $this->uploadListImage(...explode('.', $key, 2));

            return;
        }

        $file = $this->uploads[$key] ?? null;

        if (! $file instanceof UploadedFile || ! PageBlocks::has($this->pageKey, $key) || ! PageBlocks::get($this->pageKey, $key)->type->hasImage()) {
            return;
        }

        $this->authorize('manageMedia', $this->record());
        $this->validateOnly('uploads.'.$key, ['uploads.'.$key => PageRequest::uploadRules()], [], ['uploads.'.$key => 'image']);

        $media = $this->pages()->uploadImage($this->record(), $key, $file, (array) $this->form[$key], auth()->user());
        $this->form[$key]['media_id'] = (int) $media->getKey();
        $this->uploads[$key] = null;
        $this->dispatch(AdminUx::EVENT_CHANGED);
    }

    /**
     * An image chosen for one list item (uploads.{block}.{index}): stored on the block and named by the item.
     */
    private function uploadListImage(string $key, string $index): void
    {
        $definition = $this->listDefinition($key);
        $file = $this->uploads[$key][$index] ?? null;

        if ($definition === null || $definition->imageFields() === [] || ! $file instanceof UploadedFile || ! isset($this->form[$key][(int) $index])) {
            return;
        }

        $field = 'uploads.'.$key.'.'.$index;
        $this->authorize('manageMedia', $this->record());
        $this->validateOnly($field, [$field => PageRequest::uploadRules()], [], [$field => 'image']);

        $item = (array) $this->form[$key][(int) $index];
        $media = $this->pages()->uploadImage($this->record(), $key, $file, $item, auth()->user());
        $this->form[$key][(int) $index]['media_id'] = (int) $media->getKey();
        $this->uploads[$key][$index] = null;
        $this->dispatch(AdminUx::EVENT_CHANGED);
    }

    /**
     * Take the image out of one list item.
     */
    public function removeListImage(string $key, int $index): void
    {
        $definition = $this->listDefinition($key);

        if ($definition === null || $definition->imageFields() === [] || ! isset($this->form[$key][$index])) {
            return;
        }

        $this->authorize('manageMedia', $this->record());
        $mediaId = (int) ($this->form[$key][$index]['media_id'] ?? 0);
        $this->form[$key][$index]['media_id'] = null;

        if ($mediaId > 0) {
            $this->pages()->removeImage($this->record(), $key, $mediaId, auth()->user());
        }

        $this->dispatch(AdminUx::EVENT_CHANGED);
    }

    /**
     * Validate the whole form, telling the editor where to look when it fails.
     */
    private function validateForm(): void
    {
        try {
            $this->validate();
        } catch (ValidationException $exception) {
            $this->toast(__('Not saved: some fields need attention. They are listed at the top of the form.'), AdminUx::TOAST_ERROR);

            throw $exception;
        }
    }

    private function afterSave(Page $page, RevisionKind $kind): void
    {
        $this->form = $this->pages()->formValues($page);
        $this->arabic = $this->pages()->arabicFormValues($page, $this->form);
        app(RevisionService::class)->record($page, [...$this->form, self::REVISION_TRANSLATIONS => [Locales::AR => $this->arabic]], $kind, auth()->user());
        $this->restoredRevision = false;
        $this->dispatch(AdminUx::EVENT_SAVED, at: $page->updated_at?->toIso8601String(), autosave: true);
    }

    /**
     * A revision keeps the Arabic tab as it was typed; lists are read back by position.
     */
    private function arabicStored(PageBlockDefinition $definition, mixed $form): mixed
    {
        if ($definition->type !== PageBlockType::List || ! is_array($form) || ! $definition->fixedItems) {
            return $form;
        }

        // Fixed lists are matched by key: give each typed item its English item's key.
        return array_map(
            fn (mixed $item, int $index): array => [...(is_array($item) ? $item : []), 'key' => $this->form[$definition->key][$index]['key'] ?? null],
            array_values($form),
            array_keys(array_values($form)),
        );
    }

    private function listDefinition(string $key): ?PageBlockDefinition
    {
        if (! PageBlocks::has($this->pageKey, $key)) {
            return null;
        }

        $definition = PageBlocks::get($this->pageKey, $key);

        return $definition->type === PageBlockType::List && is_array($this->form[$key] ?? null) ? $definition : null;
    }

    private function request(): PageRequest
    {
        return (new PageRequest)->forPage($this->pageKey);
    }

    private function pages(): PageService
    {
        return app(PageService::class);
    }
}
