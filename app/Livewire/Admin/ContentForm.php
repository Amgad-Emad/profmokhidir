<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Constants\AdminUx;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\Locales;
use App\Constants\Placeholders;
use App\Constants\RevisionKind;
use App\Exceptions\ImagesNotReadyException;
use App\Http\Requests\Admin\ContentRequest;
use App\Livewire\Admin\Concerns\EditsTranslations;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\Revision;
use App\Services\AbstractContentService;
use App\Services\PreviewService;
use App\Services\RevisionService;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * The create/edit form every content type shares. Field values live in
 * $form; validation rules, messages and attribute names come from the type's
 * Form Request so they are defined once. Fields validate as they are left
 * (inline messages), and the whole form on save.
 * Translatable columns are edited in English and Arabic (EditsTranslations).
 */
abstract class ContentForm extends Component
{
    use AuthorizesRequests, EditsTranslations, NotifiesEditors;

    public const string FIELD_PREFIX = 'form.';

    /** Key of $form holding the other languages' text: $form['translations']['ar']['title']. */
    public const string TRANSLATIONS = 'translations';

    public ?int $recordId = null;

    /** Whether the record existed when the screen opened (an autosave of a new one swaps the address once). */
    public bool $wasEditing = false;

    /** An earlier version was loaded; the next save is recorded as a restore. */
    public bool $restoredRevision = false;

    /** The version history drawer is open. */
    public bool $showHistory = false;

    /** @var array<string, mixed> */
    public array $form = [];

    /** ContentTypes key of the type this form edits. */
    abstract protected function type(): string;

    /**
     * @return AbstractContentService<Model>
     */
    abstract protected function service(): AbstractContentService;

    abstract protected function request(): ContentRequest;

    /**
     * Field name => default value for a new record.
     *
     * @return array<string, mixed>
     */
    abstract protected function defaults(): array;

    protected function initialise(?Model $record): void
    {
        $modelClass = ContentTypes::get($this->type())['model'];

        if ($record === null) {
            $this->authorize('create', $modelClass);
            $this->form = $this->withTranslationDefaults($this->defaults());

            return;
        }

        $this->authorize('update', $record);
        $this->recordId = (int) $record->getKey();
        $this->wasEditing = true;
        // Re-read so database defaults (status, sort order) are present on the model.
        $this->form = $this->fromRecord($this->service()->findOrFail($this->recordId));
    }

    public function record(): ?Model
    {
        return $this->recordId === null ? null : $this->service()->find($this->recordId);
    }

    /**
     * Validation rules, prefixed for the $form array.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->request()->rules() as $field => $fieldRules) {
            $rules[self::FIELD_PREFIX.$field] = array_map(fn ($rule) => $this->prefixRuleReference($rule), $fieldRules);

            if ($this->isTranslatableKey($field)) {
                foreach (Locales::prefixed() as $locale) {
                    $rules[$this->translationKey($locale, $field)] = $this->translationRules($fieldRules);
                }
            }
        }

        return $rules;
    }

    /**
     * Rules that name another field (gte:start_year, required_unless:provider,file)
     * must point at the prefixed property.
     */
    private function prefixRuleReference(mixed $rule): mixed
    {
        if (! is_string($rule) || ! str_contains($rule, ':')) {
            return $rule;
        }

        [$name, $parameters] = explode(':', $rule, 2);

        if (! in_array($name, ['gt', 'gte', 'lt', 'lte', 'same', 'different', 'required_if', 'required_unless', 'required_with', 'required_without', 'required_with_all', 'required_without_all', 'prohibited_if', 'prohibited_unless', 'exclude_if', 'exclude_unless', 'before', 'after', 'before_or_equal', 'after_or_equal'], true)) {
            return $rule;
        }

        $parts = explode(',', $parameters);
        $parts[0] = self::FIELD_PREFIX.$parts[0];

        return $name.':'.implode(',', $parts);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->request()->messages() as $key => $message) {
            $messages[str_starts_with($key, '*.') ? $key : self::FIELD_PREFIX.$key] = $message;

            if (! str_starts_with($key, '*.') && $this->isTranslatableKey($key)) {
                foreach (Locales::prefixed() as $locale) {
                    $messages[$this->translationKey($locale, $key)] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $attributes = [];

        $labels = [];

        foreach (array_keys($this->request()->rules()) as $field) {
            $labels[$field] = Str::of($field)->replace(['.*.', '.*', '_'], [' ', '', ' '])->lower()->toString();
        }

        $labels = array_replace($labels, $this->request()->attributes());

        foreach ($labels as $field => $label) {
            $attributes[self::FIELD_PREFIX.$field] = __($label);

            if ($this->isTranslatableKey($field)) {
                foreach (Locales::prefixed() as $locale) {
                    $attributes[$this->translationKey($locale, $field)] = __(':field (Arabic)', ['field' => __($label)]);
                }
            }
        }

        return $attributes;
    }

    /**
     * Inline validation: check a field as soon as it changes.
     */
    public function updated(string $name): void
    {
        if (str_starts_with($name, self::FIELD_PREFIX)) {
            $this->validateOnly($name);
        }
    }

    /**
     * Save with the status as it is (Ctrl/Cmd+S): a draft stays a draft, a
     * published record is updated in place.
     */
    public function save(): void
    {
        $this->persist(null);
    }

    /**
     * Publish (or, when already published, update) — Ctrl/Cmd+Enter.
     */
    public function publish(): void
    {
        $this->persist(ContentStatus::Published);
    }

    /**
     * Take a published record off the public site, keeping the edits.
     */
    public function unpublish(): void
    {
        $this->persist(ContentStatus::Draft);
    }

    /**
     * Quietly save a draft (every AdminUx::AUTOSAVE_SECONDS while there are
     * changes, and when a field is left). Never publishes and never touches a
     * published record; with invalid fields nothing is saved and the save bar
     * says why, without marking the fields while the editor is still typing.
     */
    public function autosave(): void
    {
        $record = $this->record();

        if ($record !== null && $record->getAttribute('status') === ContentStatus::Published) {
            $this->dispatch(AdminUx::EVENT_SAVED, autosave: false, blocked: __('Published: press Update to put changes live'));

            return;
        }

        $validator = Validator::make(['form' => $this->form], $this->rules(), $this->messages(), $this->validationAttributes());

        if ($validator->fails()) {
            $this->dispatch(AdminUx::EVENT_SAVED, blocked: __('Not saved yet: :reason', ['reason' => rtrim((string) $validator->errors()->first(), '.')]));

            return;
        }

        $data = $this->dataFrom($validator->validated()['form'] ?? []);
        $data['status'] = ContentStatus::Draft->value;

        try {
            $record = $this->store($data);
        } catch (ImagesNotReadyException) {
            return;
        }

        $this->recordRevision($record, RevisionKind::Autosave);

        if (! $this->wasEditing) {
            $this->wasEditing = true;
            $this->dispatch(AdminUx::EVENT_ADDRESS, url: $this->editUrl($record), crumb: $this->recordLabel($record));
        }

        $this->dispatch(AdminUx::EVENT_SAVED, at: $record->updated_at?->toIso8601String());
    }

    /**
     * Load an earlier version into the form. Nothing is saved until the
     * editor saves; the save bar shows "Unsaved changes" meanwhile.
     */
    public function restoreRevision(int $revisionId): void
    {
        $record = $this->record();

        if ($record === null) {
            return;
        }

        $this->authorize('update', $record);
        $data = app(RevisionService::class)->dataFor($record, $revisionId);

        if ($data === null) {
            return;
        }

        $this->form = array_replace($this->form, array_intersect_key($data, $this->defaults()));

        // Versions saved before the record had translations leave the current ones as they are.
        foreach (Locales::prefixed() as $locale) {
            $translated = $data[self::TRANSLATIONS][$locale] ?? null;

            if (is_array($translated)) {
                $this->form[self::TRANSLATIONS][$locale] = array_replace(
                    $this->form[self::TRANSLATIONS][$locale] ?? [],
                    array_intersect_key($translated, array_flip($this->translatableFields())),
                );
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

    /**
     * Validate, store and report a manual save, publish or unpublish.
     */
    protected function persist(?ContentStatus $status): void
    {
        $previousStatus = $this->form['status'] ?? ContentStatus::Draft->value;

        if ($status !== null) {
            $this->form['status'] = $status->value;
        }

        try {
            $validated = $this->validate()['form'] ?? [];
        } catch (ValidationException $exception) {
            $this->form['status'] = $previousStatus;
            $this->toast(__('Not saved: some fields need attention. They are listed at the top of the form.'), AdminUx::TOAST_ERROR);

            throw $exception;
        }

        $isNew = $this->record() === null;
        $wasPublished = $this->record()?->getAttribute('status') === ContentStatus::Published;

        $this->beforeStore();

        try {
            $record = $this->store($this->dataFrom($validated));
        } catch (ImagesNotReadyException $exception) {
            $this->form['status'] = $previousStatus;
            $this->addError(self::FIELD_PREFIX.'status', $exception->getMessage());
            $this->toast($exception->getMessage(), AdminUx::TOAST_ERROR);

            return;
        }

        $isPublished = $record->getAttribute('status') === ContentStatus::Published;
        $this->recordRevision($record, match (true) {
            $isPublished && ! $wasPublished => RevisionKind::Publish,
            $this->restoredRevision => RevisionKind::Restore,
            default => RevisionKind::Save,
        });
        $this->restoredRevision = false;

        $message = match (true) {
            $isPublished && ! $wasPublished => __(':type published. It is on the public site now.', ['type' => __($this->singular())]),
            ! $isPublished && $wasPublished => __(':type moved back to draft. Visitors no longer see it.', ['type' => __($this->singular())]),
            $isNew => $this->createdMessage(),
            default => __(':type saved.', ['type' => __($this->singular())]),
        };

        if ($isNew && $this->redirectsAfterCreate()) {
            session()->flash('admin.status', $message);
            $this->redirect($this->editUrl($record));

            return;
        }

        $this->toast($message);
        $this->dispatch(AdminUx::EVENT_SAVED, at: $record->updated_at?->toIso8601String(), autosave: ! $isPublished);
    }

    /**
     * Create or update the record from validated, transformed data and keep
     * the form in step with what was stored.
     *
     * @param  array<string, mixed>  $data
     */
    protected function store(array $data): Model
    {
        $record = $this->record();

        if ($record === null) {
            $record = $this->service()->create($data, auth()->user());
            $this->recordId = (int) $record->getKey();
            $this->afterCreate($record);
        } else {
            $record = $this->service()->update($record, $data, auth()->user());
            $this->afterUpdate($record);
        }

        $this->form = $this->fromRecord($record);

        return $record;
    }

    protected function createdMessage(): string
    {
        return __(':type created.', ['type' => __($this->singular())]);
    }

    /**
     * New records open their edit screen after the first manual save.
     */
    protected function redirectsAfterCreate(): bool
    {
        return true;
    }

    protected function recordRevision(Model $record, RevisionKind $kind): void
    {
        app(RevisionService::class)->record($record, $this->form, $kind, auth()->user());
    }

    protected function editUrl(Model $record): string
    {
        return $this->type() === ContentTypes::PROFILE
            ? route(ContentTypes::editRoute($this->type()))
            : route(ContentTypes::editRoute($this->type()), [ContentTypes::routeParameter($this->type()) => $record->getKey()]);
    }

    /**
     * Signed link to the record's public page with this draft shown (see PreviewService).
     */
    public function previewUrl(): ?string
    {
        $record = $this->record();

        return $record === null ? null : app(PreviewService::class)->url($this->type(), $record);
    }

    /**
     * Form fields whose value still holds the seeders' placeholder copy.
     *
     * @return list<string>
     */
    public function placeholderFields(): array
    {
        return array_keys(array_filter(
            array_diff_key($this->form, [self::TRANSLATIONS => true]),
            fn ($value): bool => Placeholders::contains($value),
        ));
    }

    public function publicUrl(): ?string
    {
        $record = $this->record();

        if ($record === null || $record->getAttribute('status') !== ContentStatus::Published) {
            return null;
        }

        $type = ContentTypes::get($this->type());

        return $type['public_param'] === null
            ? route($type['public_route'])
            : route($type['public_route'], [$record->getAttribute($type['public_param'])]);
    }

    public function indexUrl(): string
    {
        return route(ContentTypes::indexRoute($this->type()));
    }

    public function render(): View
    {
        $type = ContentTypes::get($this->type());
        $record = $this->record();

        return view($this->view(), $this->viewData() + [
            'type' => $type,
            'record' => $record,
            'isNew' => $record === null,
            'publicUrl' => $this->publicUrl(),
            'indexUrl' => $this->indexUrl(),
            'statusOptions' => ContentStatus::labels(),
            'editor' => $this->editorState($record),
        ])->layout('components.layouts.admin', [
            'title' => __($record === null ? 'New :type' : 'Edit :type', ['type' => mb_strtolower(__($type['singular']))]),
            'crumb' => $record === null ? __('New :type', ['type' => mb_strtolower(__($type['singular']))]) : $this->recordLabel($record),
        ]);
    }

    /**
     * What the save bar, the version history and the placeholder summary need.
     *
     * @return array{status: ContentStatus, savedAt: ?string, previewUrl: ?string, publicUrl: ?string, revisions: Collection<int, Revision>, revisionCount: int, placeholders: list<string>, singular: string}
     */
    protected function editorState(?Model $record): array
    {
        $status = $record?->getAttribute('status');

        return [
            'status' => $status instanceof ContentStatus ? $status : ContentStatus::Draft,
            'savedAt' => $record?->getAttribute('updated_at')?->toIso8601String(),
            'previewUrl' => $this->previewUrl(),
            'publicUrl' => $this->publicUrl(),
            'revisions' => $record !== null && $this->showHistory ? app(RevisionService::class)->history($record) : collect(),
            'revisionCount' => $record === null ? 0 : app(RevisionService::class)->count($record),
            'placeholders' => $this->placeholderFields(),
            'singular' => mb_strtolower(__(ContentTypes::get($this->type())['singular'])),
        ];
    }

    protected function recordLabel(Model $record): string
    {
        $label = $record->getAttribute('title') ?? $record->getAttribute('name') ?? $record->getAttribute('quote');

        return $label === null ? __(ContentTypes::get($this->type())['singular']) : Str::limit(strip_tags((string) $label), 60);
    }

    /** Blade view of the concrete form. */
    abstract protected function view(): string;

    /**
     * Extra data for the view.
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [];
    }

    /**
     * Turn validated values into what the service expects (enum values stay
     * strings; the services cast them). Override to reshape fields.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function transform(array $validated): array
    {
        return $this->nullifyBlank($validated);
    }

    /**
     * Empty form inputs arrive as "" and must be stored as NULL.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function nullifyBlank(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                $values[$key] = null;
            }
        }

        return $values;
    }

    /**
     * Form values of an existing record: enums as values, dates as Y-m-d.
     *
     * @return array<string, mixed>
     */
    protected function fromRecord(Model $record): array
    {
        $form = [];

        $translatable = $this->translatableFields();

        foreach ($this->defaults() as $field => $default) {
            if (in_array($field, $translatable, true)) {
                // Explicitly English: reading the attribute would follow the editor's language.
                $form[$field] = $this->translationOf($record, $field, Locales::DEFAULT, $default);

                continue;
            }

            $value = $record->getAttribute($field);

            $form[$field] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof CarbonInterface => $value->format('Y-m-d'),
                default => $value,
            };
        }

        foreach (Locales::prefixed() as $locale) {
            foreach ($translatable as $field) {
                $form[self::TRANSLATIONS][$locale][$field] = $this->translationOf($record, $field, $locale, $this->defaults()[$field]);
            }
        }

        return $form;
    }

    /**
     * Runs on a manual save, publish or unpublish, after the form is valid and
     * before the record is stored (ManagesImages saves edited image details here).
     */
    protected function beforeStore(): void {}

    protected function afterCreate(Model $record): void {}

    protected function afterUpdate(Model $record): void {}

    protected function singular(): string
    {
        return ContentTypes::get($this->type())['singular'];
    }

    /**
     * A success toast; plain English messages are translated here.
     */
    protected function notify(string $message): void
    {
        $this->toast(__($message));
    }
}
