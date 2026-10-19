<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Constants\ContentStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared draft/published behaviour: `status`, `published_at`, `sort_order`,
 * a published() scope and an ordered() scope (sort_order, then the model's
 * date column descending, then id descending).
 */
trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ]);
    }

    /**
     * Date column used as the secondary ordering key. Override per model.
     */
    public function orderDateColumn(): string
    {
        return property_exists($this, 'orderDateColumn') ? $this->orderDateColumn : 'published_at';
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), ContentStatus::Published->value);
    }

    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), ContentStatus::Draft->value);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy($this->qualifyColumn('sort_order'))
            ->orderByDesc($this->qualifyColumn($this->orderDateColumn()))
            ->orderByDesc($this->getQualifiedKeyName());
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published;
    }

    public function isDraft(): bool
    {
        return ! $this->isPublished();
    }
}
