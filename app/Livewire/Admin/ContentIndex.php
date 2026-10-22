<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Constants\AdminUx;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\DateFormats;
use App\Constants\StatusTone;
use App\Exceptions\ImagesNotReadyException;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Services\AbstractContentService;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The index table every content type shares: search, filters, sortable
 * columns, drag-and-drop reordering (with move up/down buttons as the keyboard
 * alternative), publish/unpublish (one or a ticked selection), trash with Undo
 * (no dialog: the trash is reversible) and a Trash view to
 * restore from. Subclasses name the type, its service and its columns.
 */
abstract class ContentIndex extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithPagination;

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

        $this->filters = array_intersect_key($this->filters, array_flip([...$this->service()->filterColumns(), ContentRepositoryInterface::FILTER_PLACEHOLDER]));
    }

    /**
     * A filter chip: set one filter to a value, or clear it with null.
     */
    public function setFilter(string $field, ?string $value = null): void
    {
        if (! in_array($field, [...$this->service()->filterColumns(), ContentRepositoryInterface::FILTER_PLACEHOLDER], true)) {
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
            $this->notify(sprintf('%s published.', $this->singular()));
        } catch (ImagesNotReadyException $exception) {
            $this->addError('publish', $exception->getMessage());
        }
    }

    public function unpublish(int $id): void
    {
        $model = $this->service()->findOrFail($id);
        $this->authorize('publish', $model);

        $this->service()->unpublish($model, auth()->user());
        $this->notify(sprintf('%s moved back to draft.', $this->singular()));
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
        $this->toast(sprintf('%s moved to the trash.', $this->singular()), AdminUx::TOAST_SUCCESS, $this->undoWith('restoreMany', [[$id]]));
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
            $this->toast(sprintf('%d published. Not published yet, because images still need details: %s.', $published, implode(', ', $refused)), AdminUx::TOAST_ERROR);

            return;
        }

        $this->toast(sprintf('%d %s published.', $published, $published === 1 ? strtolower($this->singular()) : strtolower(ContentTypes::get($this->type())['plural'])));
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
        $this->toast(sprintf('%d moved back to draft.', $count));
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
        $this->toast(sprintf('%d moved to the trash.', count($ids)), AdminUx::TOAST_SUCCESS, $this->undoWith('restoreMany', [$ids]));
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

        $this->toast(count($ids) === 1 ? sprintf('%s restored.', $this->singular()) : sprintf('%d restored.', count($ids)));
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
        $this->notify(sprintf('%s restored.', $this->singular()));
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
                'status' => ['label' => 'Status', 'options' => ContentStatus::labels()],
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
            'maxChips' => self::MAX_CHIPS,
        ])->layout('components.layouts.admin', ['title' => $type['plural']]);
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
            return ['text' => $value->format(DateFormats::LONG)];
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
        $this->toast($message);
    }
}
