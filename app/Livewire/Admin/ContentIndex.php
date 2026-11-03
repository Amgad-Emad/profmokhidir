<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Constants\AdminUx;
use App\Constants\ConfirmDialog;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\DataTable;
use App\Constants\DataTableColumnType;
use App\Constants\ListToolbar;
use App\Constants\Locales;
use App\Constants\Placeholders;
use App\Constants\StatusTone;
use App\Exceptions\ImagesNotReadyException;
use App\Livewire\Admin\Concerns\BulkPublishesContent;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\Concerns\HasTranslatableContent;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Services\AbstractContentService;
use App\Services\DataTableService;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use App\Support\DataTable\RowAction;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The index every content type shares: the list toolbar (views with counts,
 * search, filters, sort, density; WithListToolbar) over the shared data table
 * (WithDataTable): a primary cell with the title and a supporting line, sortable
 * columns, the Columns menu, a quick publish switch with Undo, row actions
 * (Edit, View on site, Duplicate, Move to trash with Undo), selection with bulk
 * actions, and a Reorder mode for the display order. The Trash view restores or
 * deletes permanently (irreversible, so it asks first in the danger dialog).
 * Subclasses name the type, its service, its columns and its supporting line.
 */
abstract class ContentIndex extends Component
{
    use AuthorizesRequests, BulkPublishesContent, ConfirmsActions, NotifiesEditors, WithDataTable, WithPagination;
    use WithListToolbar {
        WithListToolbar::listViewCount as baseListViewCount;
    }

    /** The confirmation that guards a permanent delete from the trash. */
    public const string ACTION_PURGE = 'purge';

    /** The trash view (ListToolbar::VIEW_TRASH), kept under its old name. */
    public const string VIEW_TRASH = ListToolbar::VIEW_TRASH;

    public const string VIEW_DRAFTS = 'drafts';

    public const string VIEW_PUBLISHED = 'published';

    public const string VIEW_NEEDS_ARABIC = 'needs-arabic';

    public const string VIEW_PLACEHOLDER = 'placeholder';

    public const int PER_PAGE = DataTable::DEFAULT_PAGE_SIZE;

    /** Ids ticked for a bulk action (on any page). @var list<int> */
    public array $selected = [];

    /** Sort choices that start with the newest or largest. */
    public const array NEWEST_FIRST = ['updated_at', 'published_at', 'year', 'date', 'material_date', 'start_year'];

    /** ContentTypes key of the type this screen manages. */
    abstract protected function type(): string;

    /**
     * @return AbstractContentService<Model>
     */
    abstract protected function service(): AbstractContentService;

    /**
     * The muted second line of the primary cell (course · type · file size).
     *
     * @return list<string|null>
     */
    protected function supporting(Model $item): array
    {
        return [];
    }

    /**
     * A 40px image for the primary cell (an album's cover, a video's poster), or null.
     */
    protected function thumbnail(Model $item): ?string
    {
        return null;
    }

    /**
     * The type's own filters, between Status and Language (Type, Year, Featured...).
     *
     * @return list<ListFilter>
     */
    protected function contentFilters(): array
    {
        return [];
    }

    /**
     * The type's own views, after Placeholder text and before Trash.
     *
     * @return list<ListView>
     */
    protected function contentViews(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', ContentTypes::get($this->type())['model']);
    }

    protected function listScreen(): string
    {
        return ContentTypes::get($this->type())['nav'];
    }

    /**
     * Status, the type's own filters, Language (types written in both
     * languages) and Sample text (in the sheet, the views and the chips only).
     *
     * @return list<ListFilter>
     */
    protected function listFilters(): array
    {
        return array_values(array_filter([
            ListFilter::single('status', __('Status'), ContentStatus::labels()),
            ...$this->contentFilters(),
            $this->translates() ? ListFilter::flag(ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION, __('Language'), __('Needs Arabic translation'), __('Needs Arabic')) : null,
            ListFilter::flag(ContentRepositoryInterface::FILTER_PLACEHOLDER, __('Sample text'), __('Still holds sample text'), __('Sample text'), toolbar: false),
        ]));
    }

    /**
     * @return list<ListView>
     */
    protected function listViews(): array
    {
        return array_values(array_filter([
            new ListView(ListToolbar::VIEW_ALL, __('All')),
            new ListView(self::VIEW_DRAFTS, __('Drafts'), ['status' => ContentStatus::Draft->value]),
            new ListView(self::VIEW_PUBLISHED, __('Published'), ['status' => ContentStatus::Published->value]),
            $this->translates() ? new ListView(self::VIEW_NEEDS_ARABIC, __('Needs Arabic'), [ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION => ListToolbar::FLAG_ON]) : null,
            new ListView(self::VIEW_PLACEHOLDER, __('list-toolbar.placeholder_view'), [ContentRepositoryInterface::FILTER_PLACEHOLDER => ListToolbar::FLAG_ON]),
            ...$this->contentViews(),
            new ListView(self::VIEW_TRASH, __('Trash'), trash: true),
        ]));
    }

    /**
     * Display order, recently updated, then one choice per sortable column.
     *
     * @return list<ListSort>
     */
    protected function listSorts(): array
    {
        $sorts = [
            new ListSort('', ListToolbar::SORT_ASC, __('Display order')),
            new ListSort('updated_at', ListToolbar::SORT_DESC, __('Recently updated')),
        ];
        $sortable = $this->service()->sortableColumns();

        foreach ($this->dataTableColumns() as $column) {
            if ($column->type === DataTableColumnType::Status || $column->key === 'updated_at' || ! in_array($column->key, $sortable, true)) {
                continue;
            }

            $sorts[] = in_array($column->key, self::NEWEST_FIRST, true)
                ? new ListSort($column->key, ListToolbar::SORT_DESC, __(':column, newest first', ['column' => $column->label]))
                : new ListSort($column->key, ListToolbar::SORT_ASC, __(':column, A to Z', ['column' => $column->label]));
        }

        return $sorts;
    }

    protected function listSortColumns(): array
    {
        return $this->service()->sortableColumns();
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->service()->count($search, $criteria);
    }

    protected function listGroupedCounts(string $key, array $criteria, string $search): ?array
    {
        return in_array($key, $this->service()->filterColumns(), true) ? $this->service()->countBy($key, $search, $criteria) : null;
    }

    protected function listViewCount(ListView $view): int
    {
        return $view->trash ? $this->service()->countTrashed() : $this->baseListViewCount($view);
    }

    /**
     * The Status and Updated columns every type ends with ("Deleted" in the trash).
     *
     * @return list<Column>
     */
    protected function closingColumns(): array
    {
        return [
            Column::status('status', __('Status')),
            Column::relative('updated_at', $this->onTrash() ? __('Deleted') : __('Updated'), sortable: ! $this->onTrash()),
        ];
    }

    protected function sortsNewestFirst(Column $column): bool
    {
        return in_array($column->key, self::NEWEST_FIRST, true);
    }

    protected function dataTableMatchingIds(): array
    {
        return $this->service()->search($this->search, $this->listCriteria(), DataTable::MAX_SELECT_ALL)->getCollection()->modelKeys();
    }

    protected function dataTableReorderable(): bool
    {
        return auth()->user()?->can('reorder', ContentTypes::get($this->type())['model']) ?? false;
    }

    protected function dataTableCanReorder(): bool
    {
        return $this->canReorder();
    }

    protected function dataTableSaveOrder(array $orderedIds): void
    {
        $this->reorder($orderedIds);
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

    public function clearSort(): void
    {
        $this->applySort('');
    }

    public function showActive(): void
    {
        $this->applyView(ListToolbar::VIEW_ALL);
    }

    public function showTrash(): void
    {
        $this->applyView(self::VIEW_TRASH);
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
     * The status cell's switch: publish a draft or take a published record back
     * to draft at once, with Undo in the toast (the same switch the other way).
     */
    public function togglePublished(int $id): void
    {
        $model = $this->service()->findOrFail($id);
        $this->authorize('publish', $model);
        $undo = $this->undoWith('togglePublished', [$id]);

        if ($model->getAttribute('status') === ContentStatus::Published) {
            $this->service()->unpublish($model, auth()->user());
            $this->toast(__(':type moved back to draft.', ['type' => __($this->singular())]), AdminUx::TOAST_SUCCESS, $undo);

            return;
        }

        try {
            $this->service()->publish($model, auth()->user());
            $this->toast(__(':type published.', ['type' => __($this->singular())]), AdminUx::TOAST_SUCCESS, $undo);
        } catch (ImagesNotReadyException $exception) {
            $this->addError('publish', $exception->getMessage());
        }
    }

    /**
     * A draft copy of a record (its words, details and files), then its edit screen.
     */
    public function duplicate(int $id): void
    {
        $model = $this->service()->findOrFail($id);
        $this->authorize('create', $model::class);

        $copy = $this->service()->duplicate($model, auth()->user());
        session()->flash('admin.status', __(':type duplicated. This is the copy, saved as a draft.', ['type' => __($this->singular())]));

        $this->redirectRoute(ContentTypes::editRoute($this->type()), [ContentTypes::routeParameter($this->type()) => $copy->getKey()]);
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
     * The new order of the rows on this page (Reorder mode's Save).
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
        return ! $this->onTrash()
            && $this->sort === ''
            && ! $this->listFiltered();
    }

    public function render(): View
    {
        $type = ContentTypes::get($this->type());
        $trash = $this->onTrash();

        $items = $trash
            ? $this->service()->trashed($this->search, $this->perPage)
            : $this->service()->search($this->search, $this->listCriteria(), $this->perPage, $this->sort ?: null, $this->direction);

        $plural = __($type['plural']);

        return view('livewire.admin.content.index', [
            'type' => $type,
            'items' => $items,
            'trash' => $trash,
            'toolbar' => $this->listToolbar($items->total()),
            'table' => $this->dataTable($items, $items->getCollection()->map(fn (Model $item): Row => $this->row($item, $trash))->all(), [
                'caption' => $trash ? __(':items in the trash', ['items' => $plural]) : $plural,
                'selectable' => ! $trash,
                'id' => 'content-table',
            ]),
            'createUrl' => route(ContentTypes::createRoute($this->type())),
            'singular' => $this->singular(),
            'emptyText' => ContentTypes::emptyText($this->type()),
            'hasFilters' => $this->listFiltered(),
            'purging' => $this->purgeDialog(),
        ])->layout('components.layouts.admin', ['title' => $plural]);
    }

    /**
     * One record as a table row: the title linked to its edit screen (when the
     * user may edit it), badges for missing Arabic and sample text, the type's
     * own cells, and the actions the user is allowed.
     */
    protected function row(Model $item, bool $trash): Row
    {
        $user = auth()->user();
        $id = (int) $item->getKey();
        $title = $this->recordTitle($item);
        $editUrl = ! $trash && $user?->can('update', $item) ? route(ContentTypes::editRoute($this->type()), [ContentTypes::routeParameter($this->type()) => $id]) : null;
        $badges = [];

        $missing = $trash ? [] : $this->missingArabic($item);

        if ($missing !== []) {
            $badges[] = ['label' => __('Needs Arabic'), 'tone' => StatusTone::Bronze, 'hook' => 'data-needs-arabic', 'title' => __('Not yet in Arabic: :fields', ['fields' => implode(__(', '), $missing)])];
        }

        if (Placeholders::contains($item->getAttributes())) {
            $badges[] = ['label' => __('list-toolbar.placeholder_view'), 'tone' => StatusTone::Ash, 'hook' => 'data-placeholder-badge'];
        }

        $cells = [];

        foreach ($this->dataTableColumns() as $column) {
            if (! $column->isPrimary()) {
                $cells[$column->key] = $this->cellFor($item, $column, $trash);
            }
        }

        return new Row(
            id: $id,
            title: $title,
            url: $editUrl,
            supporting: $this->supporting($item),
            thumbnail: $this->thumbnail($item),
            badges: $badges,
            cells: $cells,
            actions: $trash ? $this->trashActions($item) : $this->actions($item, $editUrl),
        );
    }

    /**
     * @return list<RowAction>
     */
    protected function actions(Model $item, ?string $editUrl): array
    {
        $user = auth()->user();
        $id = (int) $item->getKey();
        $publicUrl = $this->publicUrl($item);
        $published = $item->getAttribute('status') === ContentStatus::Published;

        return array_values(array_filter([
            $editUrl ? RowAction::link(__('Edit'), 'pencil', $editUrl, inline: true, hook: 'data-edit') : null,
            $publicUrl ? RowAction::link(__('View on site'), 'external', $publicUrl, inline: true, newTab: true, hook: 'data-view-on-site') : null,
            $user?->can('create', $item::class) ? RowAction::wire(__('Duplicate'), 'copy', 'duplicate', [$id], inline: true, hook: 'data-duplicate') : null,
            $user?->can('publish', $item) ? ($published
                ? RowAction::wire(__('Unpublish'), 'minus', 'unpublish', [$id], hook: 'data-unpublish-row')
                : RowAction::wire(__('Publish'), 'check', 'publish', [$id], hook: 'data-publish-row')) : null,
            $user?->can('delete', $item) ? RowAction::wire(__('Move to trash'), 'trash', 'trash', [$id], inline: true, danger: true, hook: 'data-delete') : null,
        ]));
    }

    /**
     * @return list<RowAction>
     */
    protected function trashActions(Model $item): array
    {
        $user = auth()->user();
        $id = (int) $item->getKey();

        return array_values(array_filter([
            $user?->can('create', $item::class) ? RowAction::wire(__('Restore'), 'undo', 'restoreMany', [[$id]], inline: true, hook: 'data-restore') : null,
            $user?->can('forceDelete', $item) ? RowAction::wire(__(ConfirmDialog::PURGE_LABEL), 'trash', 'confirm', [self::ACTION_PURGE, $id], inline: true, danger: true, hook: 'data-purge') : null,
        ]));
    }

    /**
     * What one column shows for a record: status with the publish switch,
     * relative moments, short dates, enum labels and plain text.
     */
    protected function cellFor(Model $item, Column $column, bool $trash = false): Cell
    {
        $tables = app(DataTableService::class);
        $value = $column->key === 'updated_at' && $trash ? $item->getAttribute('deleted_at') : $item->getAttribute($column->key);

        if ($value instanceof ContentStatus) {
            $toggle = ! $trash && auth()->user()?->can('publish', $item)
                ? ['call' => RowAction::call('togglePublished', [(int) $item->getKey()]), 'on' => $value === ContentStatus::Published, 'label' => __('Published: :item', ['item' => $this->recordTitle($item)])]
                : null;

            return Cell::status($value->label(), StatusTone::forContent($value), $toggle);
        }

        return match ($column->type) {
            DataTableColumnType::Relative => $tables->relative($value instanceof CarbonInterface ? $value : null),
            DataTableColumnType::Date => $tables->date($value instanceof BackedEnum ? $value->value : $value),
            default => Cell::text(match (true) {
                $value instanceof BackedEnum => method_exists($value, 'label') ? $value->label() : (string) $value->value,
                is_array($value) => implode(__(', '), array_map('strval', $value)),
                default => $value === null ? '' : (string) $value,
            }),
        };
    }

    /**
     * The record's title in the current language (sample-text markers left out: the row shows a badge).
     */
    public function recordTitle(Model $item): string
    {
        $primary = collect($this->dataTableColumns())->first(fn (Column $column): bool => $column->isPrimary());
        $title = (string) $item->getAttribute($primary?->key ?? 'title');
        $clean = trim(str_replace([Placeholders::MARKER, Placeholders::MARKER_AR], '', $title));

        return $clean === '' ? $title : $clean;
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
        if (! $this->isConfirming(self::ACTION_PURGE) || ! $this->onTrash()) {
            return null;
        }

        $model = $this->service()->findTrashed((int) $this->confirmingId);

        if ($model === null) {
            return null;
        }

        return [
            'title' => ConfirmDialog::purgeTitle($this->singular()),
            'body' => ConfirmDialog::purgeBody($this->recordTitle($model)),
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
