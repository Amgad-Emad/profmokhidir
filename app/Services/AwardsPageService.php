<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Models\Award;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the roll of honour from the published awards in display order,
 * with each award's photographs (certificate, medal or shield) in their order.
 */
class AwardsPageService
{
    public function __construct(
        private readonly AwardService $awards,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     chapter: int,
     *     awards: Collection<int, Award>,
     *     awardImages: array<int, \Illuminate\Support\Collection<int, Media>>,
     *     hasImages: bool
     * }
     */
    public function data(): array
    {
        $awards = $this->awards->published();
        $images = $awards->mapWithKeys(fn (Award $award): array => [(int) $award->getKey() => $this->awards->images($award)->values()->toBase()])->all();

        return [
            'section' => $this->navigation->section(Sections::AWARDS, Sections::AWARDS),
            'chapter' => $this->navigation->chapter(Sections::AWARDS),
            'awards' => $awards,
            'awardImages' => $images,
            'hasImages' => collect($images)->contains(fn ($photos): bool => $photos->isNotEmpty()),
        ];
    }
}
