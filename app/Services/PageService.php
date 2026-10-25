<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Constants\PageBlocks;
use App\Constants\Pages;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Support\PageBlockDefinition;
use App\Support\PageBlockValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Editing the page copy (Admin › Pages). Every save runs each value through
 * PageBlockValue (sanitised, capped, defaults for emptied required blocks).
 * Saving keeps the change as a draft beside what visitors see; publishing
 * puts the drafts live and clears the page's cache; unpublishing returns the
 * page to its built-in copy. Images are uploaded to their block at once but
 * only shown once the value that names them is published.
 */
class PageService
{
    public function __construct(
        private readonly PageRepositoryInterface $pages,
        private readonly PageContentService $content,
        private readonly MediaService $media,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Every page in admin order, created with its built-in copy when missing.
     *
     * @return Collection<int, Page>
     */
    public function all(): Collection
    {
        $stored = $this->pages->all()->keyBy('key');
        $missing = array_diff(Pages::all(), $stored->keys()->all());

        if ($missing !== []) {
            $this->install();
        }

        return $this->pages->all()->sortBy(fn (Page $page): int => (int) array_search($page->key, Pages::all(), true))->values();
    }

    public function find(int $id): ?Page
    {
        return $this->pages->find($id);
    }

    public function findOrFail(int $id): Page
    {
        return $this->pages->findOrFail($id);
    }

    public function findByKey(string $key): ?Page
    {
        return $this->pages->findByKey($key);
    }

    /**
     * Create every page (published) and every block missing from it, with the
     * built-in copy as its value. What is already stored is left as it is, so
     * running it again never overwrites an editor's words.
     */
    public function install(): void
    {
        DB::transaction(function (): void {
            foreach (Pages::all() as $position => $key) {
                $page = $this->pages->firstOrCreate($key, [
                    'status' => ContentStatus::Published,
                    'published_at' => now(),
                    'sort_order' => $position + 1,
                ]);
                $stored = $this->pages->blocks($page);

                foreach (array_values(PageBlocks::for($key)) as $order => $definition) {
                    if (! $stored->has($definition->key)) {
                        $this->pages->saveBlock($page, $definition->key, $definition->type, [
                            'value' => $definition->default,
                            'draft_value' => null,
                            'sort_order' => $order + 1,
                        ]);
                    }
                }
            }
        });

        $this->content->flush();
    }

    /**
     * What the edit screen shows: each block's draft, else its live value, else the default.
     *
     * @return array<string, mixed>
     */
    public function formValues(Page $page): array
    {
        $blocks = $this->pages->blocks($page);
        $values = [];

        foreach (PageBlocks::for($page->key) as $key => $definition) {
            $block = $blocks->get($key);
            $values[$key] = PageBlockValue::normalise($definition, $block?->hasDraft() ? $block->draft_value : $block?->value);
        }

        return $values;
    }

    /**
     * What visitors see once the page is published (the stored values, else the defaults).
     *
     * @return array<string, mixed>
     */
    public function liveValues(Page $page): array
    {
        $blocks = $this->pages->blocks($page);
        $values = [];

        foreach (PageBlocks::for($page->key) as $key => $definition) {
            $values[$key] = PageBlockValue::normalise($definition, $blocks->get($key)?->value);
        }

        return $values;
    }

    public function hasDraft(Page $page): bool
    {
        return $this->pages->countDrafts($page) > 0;
    }

    /**
     * Keep the edits as a draft: each block that differs from what visitors
     * see stores the change; one that matches again drops its draft.
     *
     * @param  array<string, mixed>  $values  block key => value (blocks left out keep their draft)
     */
    public function saveDraft(Page $page, array $values): Page
    {
        $live = $this->liveValues($page);

        DB::transaction(function () use ($page, $values, $live): void {
            foreach ($this->definitionsIn($page, $values) as $order => $definition) {
                $value = PageBlockValue::normalise($definition, $values[$definition->key]);

                $this->pages->saveBlock($page, $definition->key, $definition->type, [
                    'draft_value' => $value == $live[$definition->key] ? null : $value,
                    'sort_order' => $order,
                ]);
            }

            $page->touch();
        });

        return $page->refresh();
    }

    /**
     * Put the values live: every block takes the given value (or its draft),
     * drafts are cleared, image details are copied onto the media items, the
     * page is published and its cache cleared.
     *
     * @param  array<string, mixed>  $values
     */
    public function publish(Page $page, array $values, ?User $actor = null): Page
    {
        $values = array_replace($this->formValues($page), array_intersect_key($values, PageBlocks::for($page->key)));
        $wasPublished = $page->isPublished();

        DB::transaction(function () use ($page, $values): void {
            foreach ($this->definitionsIn($page, $values) as $order => $definition) {
                $value = PageBlockValue::normalise($definition, $values[$definition->key]);
                $block = $this->pages->saveBlock($page, $definition->key, $definition->type, [
                    'value' => $value,
                    'draft_value' => null,
                    'sort_order' => $order,
                ]);

                if ($definition->type->hasImage()) {
                    $this->syncImageDetails($block, $value);
                }
            }

            $this->pages->update($page, [
                'status' => ContentStatus::Published,
                'published_at' => $page->published_at ?? now(),
            ]);
        });

        $this->content->flush($page->key);
        $this->audit->record($actor, AuditAction::PAGE_PUBLISHED, $page, ['page' => $page->key, 'first' => ! $wasPublished]);

        return $page->refresh();
    }

    /**
     * Return the page to its built-in copy for visitors, keeping every edit.
     */
    public function unpublish(Page $page, ?User $actor = null): Page
    {
        $page = $this->pages->update($page, ['status' => ContentStatus::Draft]);
        $this->content->flush($page->key);
        $this->audit->record($actor, AuditAction::PAGE_UNPUBLISHED, $page, ['page' => $page->key]);

        return $page;
    }

    /**
     * Drop every unpublished change, so the form shows what visitors see again.
     */
    public function discardDraft(Page $page): Page
    {
        foreach ($this->pages->blocks($page) as $block) {
            if ($block->hasDraft()) {
                $this->pages->saveBlock($page, (string) $block->key, $block->type, ['draft_value' => null]);
            }
        }

        $page->touch();

        return $page->refresh();
    }

    /**
     * Upload an image for an image or share-image block. It is stored on the
     * block at once; the editor's form then names it, and visitors see it once
     * that is published. An earlier upload that never went live is removed.
     *
     * @param  array<string, mixed>  $current  the block's value in the form
     */
    public function uploadImage(Page $page, string $key, UploadedFile $file, array $current = [], ?User $actor = null): Media
    {
        $definition = PageBlocks::get($page->key, $key);

        if (! $definition->type->hasImage()) {
            throw new InvalidArgumentException(sprintf('Block "%s" does not hold an image.', $key));
        }

        $block = $this->pages->block($page, $key) ?? $this->pages->saveBlock($page, $key, $definition->type, ['value' => $definition->default]);
        $previous = (int) ($current['media_id'] ?? 0);

        if ($previous > 0) {
            $this->removeImage($page, $key, $previous, $actor);
        }

        return $this->media->attach($block, $file, MediaCollections::PAGE_IMAGES, [
            MediaProperties::TREATMENT => MediaTreatment::Portrait->value,
        ], actor: $actor);
    }

    /**
     * Delete an uploaded image of a block, unless visitors (or the saved draft) still see it.
     */
    public function removeImage(Page $page, string $key, int $mediaId, ?User $actor = null): bool
    {
        $block = $this->pages->block($page, $key);
        $media = $block?->media()->find($mediaId);

        if ($block === null || $media === null || in_array($mediaId, [(int) ($block->value['media_id'] ?? 0), (int) ($block->draft_value['media_id'] ?? 0)], true)) {
            return false;
        }

        return $this->media->remove($media, $actor);
    }

    /**
     * The uploaded image a block's form value names, for the edit screen.
     */
    public function imageFor(Page $page, string $key, mixed $mediaId): ?Media
    {
        $id = (int) $mediaId;

        return $id > 0 ? $this->pages->block($page, $key)?->media()->find($id) : null;
    }

    /**
     * Copy the published details onto the media item, so the media library shows them too.
     *
     * @param  array<string, mixed>  $value
     */
    private function syncImageDetails(PageBlock $block, array $value): void
    {
        $media = ($value['media_id'] ?? null) === null ? null : $block->media()->find($value['media_id']);

        if ($media === null) {
            return;
        }

        $properties = array_filter([
            MediaProperties::ALT_TEXT => $value['alt_text'] ?? null,
            MediaProperties::CAPTION => $value['caption'] ?? null,
            MediaProperties::PLACE => $value['place'] ?? null,
            MediaProperties::YEAR => $value['year'] ?? null,
        ], fn ($property): bool => $property !== null);

        if ($properties !== []) {
            $this->media->updateProperties($media, $properties);
        }
    }

    /**
     * The definitions of the blocks present in $values, keyed by their 1-based position on the page.
     *
     * @param  array<string, mixed>  $values
     * @return array<int, PageBlockDefinition>
     */
    private function definitionsIn(Page $page, array $values): array
    {
        $definitions = [];

        foreach (array_values(PageBlocks::for($page->key)) as $index => $definition) {
            if (array_key_exists($definition->key, $values)) {
                $definitions[$index + 1] = $definition;
            }
        }

        return $definitions;
    }
}
