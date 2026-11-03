<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Constants\AdminUx;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Exceptions\ImagesNotReadyException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The bulk actions of a content index (ContentIndex), shown in the list
 * toolbar's bulk bar while rows are ticked (WithDataTable ticks them): Publish, Unpublish and
 * Move to trash (undoable from the toast for AdminUx::UNDO_SECONDS).
 */
trait BulkPublishesContent
{
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
                $refused[] = $this->recordTitle($model);
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
}
