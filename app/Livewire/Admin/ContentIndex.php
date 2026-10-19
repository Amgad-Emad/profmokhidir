<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\DateFormats;
use App\Constants\StatusTone;
use App\Exceptions\ImagesNotReadyException;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Services\AbstractContentService;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The index table every content type shares: search, filters, sortable
 * columns, drag-and-drop reordering (with move up/down buttons as the keyboard
 * alternative), publish/unpublish, trash with confirmation and a Trash view to
 * restore from. Subclasses name the type, its service and its columns.
 */
abstract class ContentIndex extends Component
{
    use AuthorizesRequests, ConfirmsActions, WithPagination;

    public const string VIEW_ACTIVE = 'active';

    public const string VIEW_TRASH = 'trash';

    public const string ACTION_DELETE = 'delete';

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

        $this->filters = array_intersect_key($this->filters, array_flip($this->service()->filterColumns()));
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
            $this->direction = ContentRepositoryInterface::SORT_ASC;
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

    public function delete(): void
    {
        if (! $this->isConfirming(self::ACTION_DELETE)) {
            return;
        }

        $model = $this->service()->findOrFail((int) $this->confirmingId);
        $this->authorize('delete', $model);

        $this->service()->delete($model, auth()->user());
        $this->cancelConfirmation();
        $this->notify(sprintf('%s moved to the trash.', $this->singular()));
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
        session()->flash('admin.status', $message);
    }
}
