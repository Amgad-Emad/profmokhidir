<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Constants\AdminUx;
use App\Constants\ConfirmDialog;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\DateFormats;
use App\Constants\Locales;
use App\Constants\StatusTone;
use App\Exceptions\ImagesNotReadyException;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\Concerns\HasTranslatableContent;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Services\AbstractContentService;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The index table every content type shares: search, filters, sortable
 * columns, drag-and-drop reordering (with move up/down buttons as the keyboard
 * alternative), publish/unpublish (one or a ticked selection), trash with Undo
 * (no dialog: the trash is reversible) and a Trash view to
 * restore from or delete permanently (irreversible, so it asks first in the
 * danger confirmation dialog). Subclasses name the type, its service and its columns.
 */
abstract class ContentIndex extends Component
{
    use AuthorizesRequests, ConfirmsActions, NotifiesEditors, WithPagination;

    /** The confirmation that guards a permanent delete from the trash. */
    public const string ACTION_PURGE = 'purge';

    public const string VIEW_ACTIVE = 'active';

    public const string VIEW_TRASH = 'trash';

    public const int PER_PAGE = 20;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, string> */
    #[Url(except: [])]
    public array $filters = [];

    #[Url(except: '')]
    public string $sort = '';

    #[Url(except: 'asc')]
    public string $direction = ContentRepositoryInterface::SORT_ASC;

    #[Url(except: 'active')]
    public string $view = self::VIEW_ACTIVE;

    /** Ids ticked for a bulk action (on any page). @var list<int> */
    public array $selected = [];

    /** Columns whose first click sorts newest first. */
    public const array NEWEST_FIRST = ['updated_at', 'published_at'];

    /** Filters with more options than this are a select rather than chips. */
    public const int MAX_CHIPS = 8;

    /** ContentTypes key of the type this screen manages. */
    abstract protected function type(): string;

    /**
     * @return AbstractContentService<Model>
     */
    abstract protected function service(): AbstractContentService;

    /**
     * Column key => label, in table order. A column is sortable when the
     * repository lists it; the first column links to the edit screen.
     *
     * @return array<string, string>
     */
    abstract protected function columns(): array;

    /**
     * Filter field => [label, value => label options]. Status is added for every type.
     *
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    protected function filterOptions(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', ContentTypes::get($this->type())['model']);

        if (! in_array($this->view, [self::VIEW_ACTIVE, self::VIEW_TRASH], true)) {
            $this->view = self::VIEW_ACTIVE;
        }

        if ($this->sort !== '' && ! in_array($this->sort, $this->service()->sortableColumns(), true)) {
            $this->sort = '';
        }

        $this->filters = array_intersect_key($this->filters, array_flip($this->allowedFilters()));
    }

    /**
     * Filter keys this screen accepts: the type's columns and the pseudo
     * filters (sample text; needs Arabic for types written in both languages).
     *
     * @return list<string>
     */
    protected function allowedFilters(): array
    {
        $filters = [...$this->service()->filterColumns(), ContentRepositoryInterface::FILTER_PLACEHOLDER];

        if ($this->translates()) {
            $filters[] = ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION;
        }

        return $filters;
    }

    /**
     * Whether the type is written in English and Arabic.
     */
    protected function translates(): bool
    {
        return in_array(HasTranslatableContent::class, class_uses_recursive(ContentTypes::get($this->type())['model']), true);
    }

    /**
     * The translatable fields of a record still empty in Arabic, by label
     * ("Title, Summary"), or [] when it is complete (or not translatable).
     *
     * @return list<string>
     */
    public function missingArabic(Model $item): array
    {
        if (! method_exists($item, 'missingTranslations')) {
            return [];
        }

        return array_map(
            fn (string $column): string => __(Str::ucfirst(str_replace('_', ' ', $column))),
            $item->missingTranslations(Locales::AR),
        );
    }

    /**
     * A filter chip: set one filter to a value, or clear it with null.
     */
    public function setFilter(string $field, ?string $value = null): void
    {
        if (! in_array($field, $this->allowedFilters(), true)) {
            return;
        }

        if ($value === null || $value === '') {
            unset($this->filters[$field]);
        } else {
            $this->filters[$field] = $value;
        }

        $this->selected = [];
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->search = '';
        $this->selected = [];
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, $this->service()->sortableColumns(), true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === ContentRepositoryInterface::SORT_ASC
                ? ContentRepositoryInterface::SORT_DESC
                : ContentRepositoryInterface::SORT_ASC;
        } else {
            $this->sort = $column;
            // Dates read best newest first; names and titles from A.
            $this->direction = in_array($column, self::NEWEST_FIRST, true) ? ContentRepositoryInterface::SORT_DESC : ContentRepositoryInterface::SORT_ASC;
        }

        $this->resetPage();
    }

    public function clearSort(): void
    {
        $this->sort = '';
        $this->direction = ContentRepositoryInterface::SORT_ASC;
    }

    public function showActive(): void
    {
        $this->cancelConfirmation();
        $this->view = self::VIEW_ACTIVE;
        $this->resetPage();
    }

    public function showTrash(): void
    {
        $this->view = self::VIEW_TRASH;
        $this->resetPage();
    }

    public function publish(int $id): void
    {
        $model = $this->service()->findOrFail($id);
        $this->authorize('publish', $model);

        try {
            $this->service()->publish($model, auth()->user());
            $this->notify(__(':type published.', ['type' => __($this->singular())]));
        } catch (ImagesNotReadyException $exception) {
            $this->addError('publish', $exception->getMessage());
        }
    }

    public function unpublish(int $id): void
    {
        $model = $this->service()->findOrFail($id);
        $this->authorize('publish', $model);

        $this->service()->unpublish($model, auth()->user());
        $this->notify(__(':type moved back to draft.', ['type' => __($this->singular())]));
    }

    /**
     * Move one record to the trash at once; the toast offers Undo for
     * AdminUx::UNDO_SECONDS (trash is reversible, so there is no dialog).
     */
    public function trash(int $id): void
    {
        $model = $this->service()->findOrFail($id);
        $this->authorize('delete', $model);

        $this->service()->delete($model, auth()->user());
        $this->selected = array_values(array_diff($this->selected, [$id]));
        $this->toast(__(':type moved to the trash.', ['type' => __($this->singular())]), AdminUx::TOAST_SUCCESS, $this->undoWith('restoreMany', [[$id]]));
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function selectPage(array $ids): void
    {
        $this->selected = array_values(array_unique([...$this->selected, ...array_map('intval', $ids)]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function bulkPublish(): void
    {
        $published = 0;
        $refused = [];

        foreach ($this->selectedRecords() as $model) {
            if ($model->getAttribute('status') === ContentStatus::Published) {
                continue;
            }

            $this->authorize('publish', $model);

            try {
                $this->service()->publish($model, auth()->user());
                $published++;
            } catch (ImagesNotReadyException) {
                $refused[] = $this->cell($model, array_key_first($this->columns()))['text'];
            }
        }

        $this->selected = [];

        if ($refused !== []) {
            $this->toast(__(':count published. Not published yet, because images still need details: :titles.', ['count' => $published, 'titles' => implode(__(', '), $refused)]), AdminUx::TOAST_ERROR);

            return;
        }

        $this->toast(trans_choice('{1} :count :singular published.|[0,*] :count :plural published.', $published, ['singular' => mb_strtolower(__($this->singular())), 'plural' => mb_strtolower(__(ContentTypes::get($this->type())['plural']))]));
    }

    public function bulkUnpublish(): void
    {
        $count = 0;

        foreach ($this->selectedRecords() as $model) {
            if ($model->getAttribute('status') !== ContentStatus::Published) {
                continue;
            }

            $this->authorize('publish', $model);
            $this->service()->unpublish($model, auth()->user());
            $count++;
        }

        $this->selected = [];
        $this->toast(__(':count moved back to draft.', ['count' => $count]));
    }

    public function bulkTrash(): void
    {
        $ids = [];

        foreach ($this->selectedRecords() as $model) {
            $this->authorize('delete', $model);
            $this->service()->delete($model, auth()->user());
            $ids[] = (int) $model->getKey();
        }

        $this->selected = [];
        $this->toast(__(':count moved to the trash.', ['count' => count($ids)]), AdminUx::TOAST_SUCCESS, $this->undoWith('restoreMany', [$ids]));
    }

    /**
     * Undo of a move to the trash (one record or a bulk selection).
     *
     * @param  list<int|string>  $ids
     */
    public function restoreMany(array $ids): void
    {
        $this->authorize('create', ContentTypes::get($this->type())['model']);

        foreach ($ids as $id) {
            $this->service()->restore((int) $id, auth()->user());
        }

        $this->toast(count($ids) === 1 ? __(':type restored.', ['type' => __($this->singular())]) : __(':count restored.', ['count' => count($ids)]));
    }

    /**
     * @return Collection<int, Model>
     */
    protected function selectedRecords(): Collection
    {
        return $this->service()->findMany(array_map('intval', $this->selected));
    }

    public function restore(int $id): void
    {
        $this->authorize('create', ContentTypes::get($this->type())['model']);

        $this->service()->restore($id, auth()->user());
        $this->notify(__(':type restored.', ['type' => __($this->singular())]));
    }

    /**
     * Delete one record from the trash for good, after the dialog's "Delete permanently".
     */
    public function purge(): void
    {
        if (! $this->isConfirming(self::ACTION_PURGE)) {
            return;
        }

        $id = (int) $this->confirmingId;
        $this->cancelConfirmation();

        $model = $this->service()->findTrashed($id);

        if ($model === null) {
            return;
        }

        $this->authorize('forceDelete', $model);

        $this->service()->forceDelete($id, auth()->user());
        $this->toast(__(':type deleted permanently.', ['type' => __($this->singular())]));
    }

    public function moveUp(int $id): void
    {
        $this->move($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->move($id, 1);
    }

    /**
     * Drag-and-drop result: the ids of the rows on this page in their new order.
     *
     * @param  list<int|string>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->authorize('reorder', ContentTypes::get($this->type())['model']);

        if (! $this->canReorder()) {
            return;
        }

        $this->service()->reorderWithin(array_map('intval', $orderedIds));
        $this->notify('Order saved.');
    }

    /**
     * Manual ordering only makes sense in display order with nothing filtered out.
     */
    public function canReorder(): bool
    {
        return $this->view === self::VIEW_ACTIVE
            && $this->sort === ''
            && trim($this->search) === ''
            && array_filter($this->filters, fn ($value): bool => $value !== null && $value !== '') === [];
    }

    public function render(): View
    {
        $type = ContentTypes::get($this->type());

        $items = $this->view === self::VIEW_TRASH
            ? $this->service()->trashed($this->search, self::PER_PAGE)
            : $this->service()->search($this->search, $this->filters, self::PER_PAGE, $this->sort ?: null, $this->direction);

        return view('livewire.admin.content.index', [
            'type' => $type,
            'typeKey' => $this->type(),
            'columns' => $this->columns(),
            'sortable' => $this->service()->sortableColumns(),
            'filterOptions' => $this->filterOptions() + [
                'status' => ['label' => __('Status'), 'options' => ContentStatus::labels()],
            ],
            'items' => $items,
            'trashedCount' => $this->service()->countTrashed(),
            'canReorder' => $this->canReorder(),
            'createUrl' => route(ContentTypes::createRoute($this->type())),
            'editRoute' => ContentTypes::editRoute($this->type()),
            'routeParameter' => ContentTypes::routeParameter($this->type()),
            'singular' => $this->singular(),
            'emptyText' => ContentTypes::emptyText($this->type()),
            'hasFilters' => trim($this->search) !== '' || array_filter($this->filters, fn ($value): bool => $value !== null && $value !== '') !== [],
            'placeholderFilter' => ContentRepositoryInterface::FILTER_PLACEHOLDER,
            'translationFilter' => $this->translates() ? ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION : null,
            'maxChips' => self::MAX_CHIPS,
            'purging' => $this->purgeDialog(),
        ])->layout('components.layouts.admin', ['title' => __($type['plural'])]);
    }

    /**
     * Display value of one cell. Enums show their label, dates the long
     * format, statuses a badge (handled in the view), everything else as text.
     *
     * @return array{text: string, tone?: StatusTone}
     */
    public function cell(Model $item, string $column): array
    {
        $value = $item->getAttribute($column);

        if ($value instanceof ContentStatus) {
            return ['text' => $value->label(), 'tone' => StatusTone::forContent($value)];
        }

        if ($value instanceof BackedEnum) {
            return ['text' => method_exists($value, 'label') ? $value->label() : (string) $value->value];
        }

        if ($value instanceof CarbonInterface) {
            return ['text' => $value->translatedFormat(DateFormats::LONG)];
        }

        if (is_array($value)) {
            return ['text' => implode(', ', array_map('strval', $value))];
        }

        return ['text' => $value === null ? '' : (string) $value];
    }

    public function publicUrl(Model $item): ?string
    {
        if ($item->getAttribute('status') !== ContentStatus::Published) {
            return null;
        }

        $type = ContentTypes::get($this->type());

        return $type['public_param'] === null
            ? route($type['public_route'])
            : route($type['public_route'], [$item->getAttribute($type['public_param'])]);
    }

    /**
     * The permanent delete dialog's copy while it is open.
     *
     * @return array{title: string, body: string, confirm: string}|null
     */
    protected function purgeDialog(): ?array
    {
        if (! $this->isConfirming(self::ACTION_PURGE) || $this->view !== self::VIEW_TRASH) {
            return null;
        }

        $model = $this->service()->findTrashed((int) $this->confirmingId);

        if ($model === null) {
            return null;
        }

        return [
            'title' => ConfirmDialog::purgeTitle($this->singular()),
            'body' => ConfirmDialog::purgeBody($this->cell($model, array_key_first($this->columns()))['text']),
            'confirm' => __(ConfirmDialog::PURGE_LABEL),
        ];
    }

    protected function singular(): string
    {
        return ContentTypes::get($this->type())['singular'];
    }

    protected function move(int $id, int $offset): void
    {
        $model = $this->service()->findOrFail($id);
        $this->authorize('reorder', $model::class);

        $this->service()->move($model, $offset);
    }

    protected function notify(string $message): void
    {
        $this->toast(__($message));
    }
}
