<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\CvSection;
use App\Models\CvEntry;
use App\Repositories\Contracts\CvEntryRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * @extends BaseContentRepository<CvEntry>
 */
class EloquentCvEntryRepository extends BaseContentRepository implements CvEntryRepositoryInterface
{
    protected array $searchColumns = ['title', 'organisation', 'role_description'];

    protected array $sortableColumns = ['title', 'section', 'start_year'];

    protected array $filterColumns = ['status', 'section'];

    public function __construct(CvEntry $entry)
    {
        parent::__construct($entry);
    }

    public function publishedInSection(CvSection $section): Collection
    {
        return $this->publishedQuery()->inSection($section)->get();
    }

    public function latestUpdate(): ?CarbonInterface
    {
        $value = $this->query()->published()->max('last_updated_at');

        return $value === null ? null : Carbon::parse($value);
    }
}
