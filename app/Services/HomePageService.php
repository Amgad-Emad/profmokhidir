<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\HomeTimeline;
use App\Constants\Sections;
use App\Constants\Taglines;
use App\Models\HomeQuote;
use App\Models\Profile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the home page as a slow walk through his life: the hero with the
 * published portrait, "A life in chapters" (HomeTimelineService) with the
 * editors' quotes between the chapters, the figures of "His work in numbers",
 * the three "Continue the story" cards and the closing line. Only published
 * content is used, and each part reads its own service's cached listing.
 */
class HomePageService
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly PublicationService $publications,
        private readonly AwardService $awards,
        private readonly HomeQuoteService $quotes,
        private readonly HomeTimelineService $timeline,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     profile: Profile|null,
     *     portrait: Media|null,
     *     chapters: list<array{number: int, numeral: string, decade: int, years: string, id: string, milestones: list<array{year: int, years: string, title: string, detail: ?string, kind: string, label: string}>, photo: Media|null, interlude: HomeQuote|null}>,
     *     stats: list<array{key: string, value: int, label: string}>,
     *     stories: list<array{key: string, label: string, title: string, url: string, line: string, photo: Media|null}>,
     *     copy: array{label: string, line: string, epigraph: string, scroll: string, chapters: string, numbers: string, continue: string, closing: string, readStory: string, leaveTribute: string},
     *     anchor: string
     * }
     */
    public function data(): array
    {
        $profile = $this->profiles->get();
        $chapters = $this->chapters();

        return [
            'profile' => $profile,
            'portrait' => $profile?->portrait(),
            'chapters' => $chapters,
            'stats' => $this->stats($profile),
            'stories' => $this->stories(array_filter(array_column($chapters, 'photo'))),
            'copy' => [
                'label' => Taglines::HERO_LABEL,
                'line' => Taglines::HERO_LINE,
                'epigraph' => Taglines::HOME_EPIGRAPH,
                'scroll' => Taglines::SCROLL_CUE,
                'chapters' => Taglines::HOME_CHAPTERS,
                'numbers' => Taglines::HOME_NUMBERS,
                'continue' => Taglines::HOME_CONTINUE,
                'closing' => Taglines::HOME_CLOSING,
                'readStory' => Taglines::READ_STORY,
                'leaveTribute' => Taglines::LEAVE_TRIBUTE,
            ],
            'anchor' => HomeTimeline::ANCHOR,
        ];
    }

    /**
     * The timeline chapters, each followed by the next published quote as its
     * interlude. The last chapter has none (the figures follow it), so at most
     * one quote fewer than there are chapters is shown.
     *
     * @return list<array{number: int, numeral: string, decade: int, years: string, id: string, milestones: list<array{year: int, years: string, title: string, detail: ?string, kind: string, label: string}>, photo: Media|null, interlude: HomeQuote|null}>
     */
    public function chapters(): array
    {
        $chapters = $this->timeline->chapters();
        $quotes = $this->quotes->published()->values();
        $last = count($chapters) - 1;

        foreach ($chapters as $index => $chapter) {
            $chapters[$index]['interlude'] = $index < $last ? $quotes->get($index) : null;
        }

        return $chapters;
    }

    /**
     * "His work in numbers": only the figures that are known and above zero.
     * Students supervised appears only when editors have recorded it.
     *
     * @return list<array{key: string, value: int, label: string}>
     */
    public function stats(?Profile $profile): array
    {
        $values = [
            HomeTimeline::STAT_PUBLICATIONS => $this->publications->countPublished(),
            HomeTimeline::STAT_YEARS_TEACHING => $this->timeline->yearsOfTeaching(),
            HomeTimeline::STAT_AWARDS => $this->awards->countPublished(),
            HomeTimeline::STAT_STUDENTS => $profile?->students_supervised,
        ];

        $stats = [];

        foreach (array_keys(HomeTimeline::stats()) as $key) {
            $value = (int) ($values[$key] ?? 0);

            if ($value > 0) {
                $stats[] = ['key' => $key, 'value' => $value, 'label' => HomeTimeline::statLabel($key, $value)];
            }
        }

        return $stats;
    }

    /**
     * The three "Continue the story" cards. Each takes an archive photo the
     * timeline has not shown, falling back to one it has, or none.
     *
     * @param  array<int, Media>  $shown  photos already used by the chapters
     * @return list<array{key: string, label: string, title: string, url: string, line: string, photo: Media|null}>
     */
    public function stories(array $shown = []): array
    {
        $shownIds = array_map(fn (Media $photo): int => (int) $photo->id, $shown);
        $photos = $this->timeline->archivePhotos();
        usort($photos, fn (Media $a, Media $b): int => in_array((int) $a->id, $shownIds, true) <=> in_array((int) $b->id, $shownIds, true));

        $lines = Taglines::storyLines();
        $stories = [];

        foreach (Sections::homeStory() as $index => $key) {
            $section = $this->navigation->section($key);

            $stories[] = [
                'key' => $key,
                'label' => $section['label'],
                'title' => $section['title'],
                'url' => $section['url'],
                'line' => $lines[$key],
                'photo' => $photos[$index] ?? null,
            ];
        }

        return $stories;
    }
}
