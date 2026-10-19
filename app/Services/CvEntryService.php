<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\CvSection;
use App\Models\CvEntry;
use App\Repositories\Contracts\CvEntryRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends AbstractContentService<CvEntry>
 */
class CvEntryService extends AbstractContentService
{
    public function __construct(CvEntryRepositoryInterface $entries, AuditLogService $audit, Repository $cache)
    {
        parent::__construct($entries, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::CV_ENTRIES;
    }

    /**
     * Published entries grouped by section, in CvSection order; empty sections omitted.
     *
     * @return array<string, Collection<int, CvEntry>>
     */
    public function publishedBySection(): array
    {
        $grouped = $this->published()->groupBy(fn (CvEntry $entry): string => $entry->section->value);
        $ordered = [];

        foreach (CvSection::cases() as $section) {
            if ($grouped->has($section->value)) {
                $ordered[$section->value] = $grouped->get($section->value)->values();
            }
        }

        return $ordered;
    }

    public function latestUpdate(): ?CarbonInterface
    {
        /** @var CvEntryRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository->latestUpdate();
    }

    protected function prepare(array $data, $existing): array
    {
        if (isset($data['section']) && is_string($data['section'])) {
            $data['section'] = CvSection::from($data['section']);
        }

        if (array_key_exists('end_year', $data) && $data['end_year'] === '') {
            $data['end_year'] = null;
        }

        return $data;
    }
}
