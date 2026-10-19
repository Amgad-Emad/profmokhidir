<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CvSection;
use App\Constants\Sections;
use App\Models\CvEntry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Assembles the view-only CV page: entries grouped by section in CvSection
 * order, a summary strip with the count per section and the latest update.
 * The CV is never offered as a file.
 *
 * @phpstan-type CvGroup array{key: string, label: string, anchor: string, count: int, entries: Collection<int, CvEntry>}
 */
class CvPageService
{
    public function __construct(
        private readonly CvEntryService $entries,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     lastUpdated: CarbonInterface|null,
     *     groups: list<CvGroup>,
     *     summary: list<array{key: string, label: string, anchor: string, count: int}>,
     *     total: int
     * }
     */
    public function data(): array
    {
        $groups = $this->groups();

        return [
            'section' => $this->navigation->section(Sections::CV, Sections::CV),
            'lastUpdated' => $groups === [] ? null : $this->entries->latestUpdate(),
            'groups' => $groups,
            'summary' => array_map(fn (array $group): array => [
                'key' => $group['key'],
                'label' => $group['label'],
                'anchor' => $group['anchor'],
                'count' => $group['count'],
            ], $groups),
            'total' => array_sum(array_column($groups, 'count')),
        ];
    }

    /**
     * Populated sections only, in CvSection order.
     *
     * @return list<CvGroup>
     */
    public function groups(): array
    {
        $groups = [];

        foreach ($this->entries->publishedBySection() as $key => $entries) {
            $section = CvSection::from($key);

            $groups[] = [
                'key' => $section->value,
                'label' => $section->label(),
                'anchor' => 'cv-'.$section->value,
                'count' => $entries->count(),
                'entries' => $entries,
            ];
        }

        return $groups;
    }
}
