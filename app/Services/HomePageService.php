<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\HomeTimeline;
use App\Constants\Pages;
use App\Constants\Sections;
use App\Models\HomeQuote;
use App\Models\Profile;
use Illuminate\Support\HtmlString;
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
        private readonly PageContentService $pages,
        private readonly SectionCardService $cards,
        private readonly SectionVisibilityService $visibility,
    ) {}

    /**
     * @return array{
     *     profile: Profile|null,
     *     portrait: Media|null,
     *     chapters: list<array{number: int, numeral: string, decade: int, years: string, id: string, milestones: list<array{year: int, years: string, title: string, detail: ?string, kind: string, label: string}>, photo: Media|null, interlude: HomeQuote|null}>,
     *     stats: list<array{key: string, value: int, label: string}>,
     *     stories: list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>,
     *     copy: array{label: string, headingFirst: string, headingSecond: string, line: string, epigraph: string, scroll: string, chapters: string, chaptersIntro: HtmlString, chaptersEmpty: string, numbers: string, numbersEmpty: string, continue: string, closing: string, readStory: array{label: string, url: string}, leaveTribute: array{label: string, url: string}},
     *     anchor: string
     * }
     */
    public function data(): array
    {
        $profile = $this->profiles->get();
        $chapters = $this->chapters();

        $home = Pages::HOME;

        return [
            'profile' => $profile,
            // An image set in Admin › Pages › Home replaces the profile portrait in the hero.
            'portrait' => $this->pages->image($home, 'hero_image') ?? $profile?->portrait(),
            'chapters' => $chapters,
            'stats' => $this->stats($profile),
            'stories' => $this->stories(array_filter(array_column($chapters, 'photo'))),
            'copy' => [
                'label' => $this->pages->text($home, 'hero_label'),
                'headingFirst' => $this->pages->text($home, 'hero_heading_first'),
                'headingSecond' => $this->pages->text($home, 'hero_heading_second'),
                'line' => $this->pages->text($home, 'hero_tagline'),
                'epigraph' => $this->pages->text($home, 'hero_epigraph'),
                'scroll' => $this->pages->text($home, 'scroll_cue'),
                'chapters' => $this->pages->text($home, 'chapters_title'),
                'chaptersIntro' => $this->pages->html($home, 'chapters_intro'),
                'chaptersEmpty' => $this->pages->text($home, 'chapters_empty'),
                'numbers' => $this->pages->text($home, 'numbers_title'),
                'numbersEmpty' => $this->pages->text($home, 'numbers_empty'),
                'continue' => $this->pages->text($home, 'continue_title'),
                'closing' => $this->pages->text($home, 'closing_line'),
                'readStory' => $this->pages->link($home, 'button_story'),
                'leaveTribute' => $this->pages->link($home, 'button_tribute'),
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
            // A hidden section's figure is left out with it.
            HomeTimeline::STAT_PUBLICATIONS => $this->visibility->isVisible(Sections::PUBLICATIONS) ? $this->publications->countPublished() : 0,
            HomeTimeline::STAT_YEARS_TEACHING => $this->timeline->yearsOfTeaching(),
            HomeTimeline::STAT_AWARDS => $this->visibility->isVisible(Sections::AWARDS) ? $this->awards->countPublished() : 0,
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
     * The three "Continue the story" section cards (SectionCardService), each with
     * the home page's own line and an archive photo the timeline has not shown,
     * falling back to one it has, or none.
     *
     * @param  array<int, Media>  $shown  photos already used by the chapters
     * @return list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>
     */
    public function stories(array $shown = []): array
    {
        $shownIds = array_map(fn (Media $photo): int => (int) $photo->id, $shown);
        $photos = $this->timeline->archivePhotos();
        usort($photos, fn (Media $a, Media $b): int => in_array((int) $a->id, $shownIds, true) <=> in_array((int) $b->id, $shownIds, true));

        $lines = [
            Sections::ABOUT => $this->pages->text(Pages::HOME, 'story_about'),
            Sections::PUBLICATIONS => $this->pages->text(Pages::HOME, 'story_publications'),
            Sections::TRIBUTES => $this->pages->text(Pages::HOME, 'story_tributes'),
        ];
        $images = [];

        foreach (Sections::homeStory() as $index => $key) {
            $images[$key] = $photos[$index] ?? null;
        }

        return $this->cards->cards(Sections::homeStory(), $lines, $images);
    }
}
