<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Exceptions\ImagesNotReadyException;
use App\Http\Requests\Admin\ContentRequest;
use App\Services\AbstractContentService;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * The create/edit form every content type shares. Field values live in
 * $form; validation rules, messages and attribute names come from the type's
 * Form Request so they are defined once. Fields validate as they are left
 * (inline messages), and the whole form on save.
 */
abstract class ContentForm extends Component
{
    use AuthorizesRequests;

    public const string FIELD_PREFIX = 'form.';

    public ?int $recordId = null;

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
            $this->form = $this->defaults();

            return;
        }

        $this->authorize('update', $record);
        $this->recordId = (int) $record->getKey();
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
        }

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $attributes = [];

        foreach (array_keys($this->request()->rules()) as $field) {
            $attributes[self::FIELD_PREFIX.$field] = Str::of($field)->replace(['.*.', '.*', '_'], [' ', '', ' '])->lower()->toString();
        }

        foreach ($this->request()->attributes() as $field => $label) {
            $attributes[self::FIELD_PREFIX.$field] = $label;
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

    public function save(): void
    {
        $validated = $this->validate()['form'] ?? [];
        $data = $this->transform($validated);
        $record = $this->record();

        try {
            if ($record === null) {
                $record = $this->service()->create($data, auth()->user());
                $this->recordId = (int) $record->getKey();
                $this->afterCreate($record);
                session()->flash('admin.status', sprintf('%s created.', $this->singular()));

                $this->redirectRoute(ContentTypes::editRoute($this->type()), [ContentTypes::routeParameter($this->type()) => $record->getKey()]);

                return;
            }

            $record = $this->service()->update($record, $data, auth()->user());
            $this->form = $this->fromRecord($record);
            $this->afterUpdate($record);
            $this->notify(sprintf('%s saved.', $this->singular()));
        } catch (ImagesNotReadyException $exception) {
            $this->addError(self::FIELD_PREFIX.'status', $exception->getMessage());
        }
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
        ])->layout('components.layouts.admin', ['title' => ($record === null ? 'New ' : 'Edit ').strtolower($type['singular'])]);
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

        foreach (array_keys($this->defaults()) as $field) {
            $value = $record->getAttribute($field);

            $form[$field] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof CarbonInterface => $value->format('Y-m-d'),
                default => $value,
            };
        }

        return $form;
    }

    protected function afterCreate(Model $record): void {}

    protected function afterUpdate(Model $record): void {}

    protected function singular(): string
    {
        return ContentTypes::get($this->type())['singular'];
    }

    protected function notify(string $message): void
    {
        session()->flash('admin.status', $message);
    }
}
