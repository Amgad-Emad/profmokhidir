<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\CvSection;
use App\Models\CvEntry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepositoryInterface<CvEntry>
 */
interface CvEntryRepositoryInterface extends ContentRepositoryInterface
{
    /**
     * @return Collection<int, CvEntry>
     */
    public function publishedInSection(CvSection $section): Collection;

    /**
     * The most recent last_updated_at across published entries.
     */
    public function latestUpdate(): ?CarbonInterface;
}
