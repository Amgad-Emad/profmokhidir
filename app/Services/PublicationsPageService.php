<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\PageSizes;
use App\Constants\PublicationType;
use App\Constants\Sections;
use App\Models\Publication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Assembles the publications list: the filtered page grouped by year (newest
 * first), the type and year filter options with their URLs, and the active filters.
 *
 * @phpstan-type FilterOption array{value: string, label: string, url: string, active: bool}
 */
class PublicationsPageService
{
    public function __construct(
        private readonly PublicationService $publications,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     publications: LengthAwarePaginator<int, Publication>,
     *     groups: list<array{year: int|null, label: string, items: Collection<int, Publication>}>,
     *     types: list<FilterOption>,
     *     years: list<FilterOption>,
     *     active: array{type: PublicationType|null, year: int|null},
     *     isFiltered: bool,
     *     clearUrl: string,
     *     allTypesUrl: string
     * }
     */
    public function data(?string $type = null, ?int $year = null): array
    {
        $activeType = $type === null ? null : PublicationType::tryFrom($type);
        $filters = $this->publications->filters();
        $activeYear = $year !== null && in_array($year, $filters['years'], true) ? $year : null;

        $paginator = $this->publications->publishedFiltered($activeType?->value, $activeYear, PageSizes::PUBLICATIONS);

        return [
            'section' => $this->navigation->section(Sections::PUBLICATIONS, Sections::PUBLICATIONS),
            'publications' => $paginator,
            'groups' => $this->groupByYear(new Collection($paginator->items())),
            'types' => $this->typeOptions($filters['types'], $activeType, $activeYear),
            'years' => $this->yearOptions($filters['years'], $activeType, $activeYear),
            'active' => ['type' => $activeType, 'year' => $activeYear],
            'isFiltered' => $activeType !== null || $activeYear !== null,
            'clearUrl' => $this->url(),
            'allTypesUrl' => $this->url(null, $activeYear),
        ];
    }

    /**
     * Groups a page of publications by year, newest first; undated items last.
     *
     * @param  Collection<int, Publication>  $publications
     * @return list<array{year: int|null, label: string, items: Collection<int, Publication>}>
     */
    public function groupByYear(Collection $publications): array
    {
        $groups = [];

        foreach ($publications->groupBy(fn (Publication $publication): string => (string) ($publication->year ?? '')) as $year => $items) {
            $groups[] = [
                'year' => $year === '' ? null : (int) $year,
                'label' => $year === '' ? 'Undated' : (string) $year,
                'items' => $items->values(),
            ];
        }

        usort($groups, static function (array $a, array $b): int {
            if ($a['year'] === null || $b['year'] === null) {
                return $a['year'] === null ? 1 : -1;
            }

            return $b['year'] <=> $a['year'];
        });

        return $groups;
    }

    public function url(?PublicationType $type = null, ?int $year = null): string
    {
        return route(Sections::definitions()[Sections::PUBLICATIONS]['route'], array_filter([
            'type' => $type?->value,
            'year' => $year,
        ], fn ($value): bool => $value !== null));
    }

    /**
     * @param  list<PublicationType>  $types
     * @return list<FilterOption>
     */
    private function typeOptions(array $types, ?PublicationType $activeType, ?int $activeYear): array
    {
        return array_map(fn (PublicationType $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'url' => $this->url($type, $activeYear),
            'active' => $type === $activeType,
        ], $types);
    }

    /**
     * @param  list<int>  $years
     * @return list<FilterOption>
     */
    private function yearOptions(array $years, ?PublicationType $activeType, ?int $activeYear): array
    {
        return array_map(fn (int $year): array => [
            'value' => (string) $year,
            'label' => (string) $year,
            'url' => $this->url($activeType, $year),
            'active' => $year === $activeYear,
        ], $years);
    }
}
