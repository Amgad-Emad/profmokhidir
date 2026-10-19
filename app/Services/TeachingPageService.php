<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Assembles the teaching materials page: every published material grouped by
 * course and filtered by topic and type, the filter options, and which files
 * the current visitor may download. Members-only materials are listed for
 * everyone; only their file is withheld from non-members.
 */
class TeachingPageService
{
    public function __construct(
        private readonly TeachingMaterialService $materials,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     courses: array<string, Collection<int, TeachingMaterial>>,
     *     topics: list<string>,
     *     types: list<TeachingMaterialType>,
     *     active: array{topic: string|null, type: TeachingMaterialType|null},
     *     isFiltered: bool,
     *     downloadable: array<int, bool>,
     *     canSeeMembers: bool,
     *     hasAny: bool,
     *     clearUrl: string,
     *     communityUrl: string
     * }
     */
    public function data(?User $viewer, ?string $topic = null, ?string $type = null): array
    {
        $all = $this->materials->published();
        $activeType = $type === null ? null : TeachingMaterialType::tryFrom($type);
        $topics = $this->topics($all);
        $activeTopic = $topic !== null && in_array($topic, $topics, true) ? $topic : null;

        $filtered = $this->filter($all, $activeTopic, $activeType);

        return [
            'section' => $this->navigation->section(Sections::TEACHING, Sections::TEACHING),
            'courses' => $this->materials->groupByCourse($filtered),
            'topics' => $topics,
            'types' => $this->types($all),
            'active' => ['topic' => $activeTopic, 'type' => $activeType],
            'isFiltered' => $activeTopic !== null || $activeType !== null,
            'downloadable' => $filtered->mapWithKeys(fn (TeachingMaterial $material): array => [
                (int) $material->getKey() => $this->materials->canDownload($material, $viewer),
            ])->all(),
            'canSeeMembers' => $this->materials->canSeeMembersMaterials($viewer),
            'hasAny' => $all->isNotEmpty(),
            'clearUrl' => route(Sections::definitions()[Sections::TEACHING]['route']),
            'communityUrl' => route(Sections::definitions()[Sections::COMMUNITY]['route']),
        ];
    }

    /**
     * @param  Collection<int, TeachingMaterial>  $materials
     * @return Collection<int, TeachingMaterial>
     */
    public function filter(Collection $materials, ?string $topic, ?TeachingMaterialType $type): Collection
    {
        return $materials
            ->when($topic !== null, fn (Collection $items) => $items->filter(fn (TeachingMaterial $material): bool => trim((string) $material->topic) === $topic))
            ->when($type !== null, fn (Collection $items) => $items->filter(fn (TeachingMaterial $material): bool => $material->type === $type))
            ->values();
    }

    /**
     * Distinct topics among the given materials, alphabetical.
     *
     * @param  Collection<int, TeachingMaterial>  $materials
     * @return list<string>
     */
    public function topics(Collection $materials): array
    {
        return $materials
            ->map(fn (TeachingMaterial $material): string => trim((string) $material->topic))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Distinct types among the given materials, in TeachingMaterialType order.
     *
     * @param  Collection<int, TeachingMaterial>  $materials
     * @return list<TeachingMaterialType>
     */
    public function types(Collection $materials): array
    {
        $present = $materials->map(fn (TeachingMaterial $material): string => $material->type->value)->unique()->all();

        return array_values(array_filter(
            TeachingMaterialType::cases(),
            fn (TeachingMaterialType $type): bool => in_array($type->value, $present, true),
        ));
    }
}
