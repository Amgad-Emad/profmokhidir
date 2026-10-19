<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Constants\Taglines;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\Tribute;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles everything the home page shows: the published profile and its
 * portrait, the newest publications, the number of awards, the latest approved
 * tributes and the row of section links. Each part is cached by its own service.
 */
class HomePageService
{
    public const int PUBLICATIONS_SHOWN = 3;

    public const int TRIBUTES_SHOWN = 3;

    public function __construct(
        private readonly ProfileService $profiles,
        private readonly PublicationService $publications,
        private readonly AwardService $awards,
        private readonly TributeService $tributes,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     profile: Profile|null,
     *     portrait: Media|null,
     *     publications: Collection<int, Publication>,
     *     awardCount: int,
     *     tributes: Collection<int, Tribute>,
     *     sections: list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}>,
     *     copy: array{label: string, line: string, quote: string, readStory: string, leaveTribute: string}
     * }
     */
    public function data(): array
    {
        $profile = $this->profiles->get();

        return [
            'profile' => $profile,
            'portrait' => $profile?->portrait(),
            'publications' => $this->publications->latest(self::PUBLICATIONS_SHOWN),
            'awardCount' => $this->awards->countPublished(),
            'tributes' => $this->tributes->latestApproved(self::TRIBUTES_SHOWN),
            'sections' => $this->sections(),
            'copy' => [
                'label' => Taglines::HERO_LABEL,
                'line' => Taglines::HERO_LINE,
                'quote' => Taglines::HOME_QUOTE,
                'readStory' => Taglines::READ_STORY,
                'leaveTribute' => Taglines::LEAVE_TRIBUTE,
            ],
        ];
    }

    /**
     * The quiet cards linking to the sections not covered by the home chapters.
     *
     * @return list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}>
     */
    public function sections(): array
    {
        return array_map(fn (string $key): array => $this->navigation->section($key), Sections::homeRow());
    }
}
