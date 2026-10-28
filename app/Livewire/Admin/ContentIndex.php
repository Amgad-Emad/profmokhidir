<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Constants\AdminUx;
use App\Constants\ConfirmDialog;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\DateFormats;
use App\Constants\ListToolbar;
use App\Constants\Locales;
use App\Constants\StatusTone;
use App\Exceptions\ImagesNotReadyException;
use App\Livewire\Admin\Concerns\BulkPublishesContent;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\Concerns\HasTranslatableContent;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Services\AbstractContentService;
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
 * The index table every content type shares: the list toolbar (views with
 * counts, search, filters, sort, density; WithListToolbar), sortable columns, drag-and-drop reordering (with move up/down buttons as the keyboard
 * alternative), publish/unpublish (one or a ticked selection), trash with Undo
 * (no dialog: the trash is reversible) and a Trash view to
 * restore from or delete permanently (irreversible, so it asks first in the
 * danger confirmation dialog). Subclasses name the type, its service and its columns.
 */
abstract class ContentIndex extends Component
{
    use AuthorizesRequests, BulkPublishesContent, ConfirmsActions, NotifiesEditors, WithPagination;
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

    public const int PER_PAGE = 20;

    /** Ids ticked for a bulk action (on any page). @var list<int> */
    public array $selected = [];

    /** Columns whose first click (or Sort choice) sorts newest first. */
    public const array NEWEST_FIRST = ['updated_at', 'published_at', 'year', 'date', 'material_date', 'start_year'];

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

        foreach ($this->columns() as $column => $label) {
            if ($column === 'status' || ! in_array($column, $sortable, true)) {
                continue;
            }

            $sorts[] = in_array($column, self::NEWEST_FIRST, true)
                ? new ListSort($column, ListToolbar::SORT_DESC, __(':column, newest first', ['column' => __($label)]))
                : new ListSort($column, ListToolbar::SORT_ASC, __(':column, A to Z', ['column' => __($label)]));
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
     * A column heading: sort by it, or turn its direction round.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, $this->service()->sortableColumns(), true)) {
            return;
        }

        $direction = $this->sort === $column
            ? ($this->direction === ListToolbar::SORT_ASC ? ListToolbar::SORT_DESC : ListToolbar::SORT_ASC)
            // Dates read best newest first; names and titles from A.
            : (in_array($column, self::NEWEST_FIRST, true) ? ListToolbar::SORT_DESC : ListToolbar::SORT_ASC);

        $this->applySort($column, $direction);
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
        return ! $this->onTrash()
            && $this->sort === ''
            && ! $this->listFiltered();
    }

    public function render(): View
    {
        $type = ContentTypes::get($this->type());
        $trash = $this->onTrash();

        $items = $trash
            ? $this->service()->trashed($this->search, self::PER_PAGE)
            : $this->service()->search($this->search, $this->listCriteria(), self::PER_PAGE, $this->sort ?: null, $this->direction);

        return view('livewire.admin.content.index', [
            'type' => $type,
            'typeKey' => $this->type(),
            'columns' => $this->columns(),
            'sortable' => $this->service()->sortableColumns(),
            'items' => $items,
            'trash' => $trash,
            'toolbar' => $this->listToolbar($items->total()),
            'canReorder' => $this->canReorder(),
            'createUrl' => route(ContentTypes::createRoute($this->type())),
            'editRoute' => ContentTypes::editRoute($this->type()),
            'routeParameter' => ContentTypes::routeParameter($this->type()),
            'singular' => $this->singular(),
            'emptyText' => ContentTypes::emptyText($this->type()),
            'hasFilters' => $this->listFiltered(),
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
        if (! $this->isConfirming(self::ACTION_PURGE) || ! $this->onTrash()) {
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
