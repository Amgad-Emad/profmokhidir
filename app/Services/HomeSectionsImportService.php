<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\HomeTimeline;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\PageBlocks;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Pages;
use App\Constants\Sections;
use App\Constants\Taglines;
use App\Support\PageBlockValue;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Fills the home page's chapters, figures and cards (Admin › Pages › Home) with
 * what the home page showed while those sections were drawn from Awards, the
 * albums, the record counts and the section pages, in English and Arabic, so
 * the editor opens on the current data. A list already filled is left alone.
 */
class HomeSectionsImportService
{
    public function __construct(
        private readonly PageService $pages,
        private readonly HomeTimelineService $timeline,
        private readonly HomeQuoteService $quotes,
        private readonly PublicationService $publications,
        private readonly AwardService $awards,
        private readonly ProfileService $profiles,
        private readonly SectionCardService $cards,
        private readonly SectionVisibilityService $visibility,
        private readonly PageContentService $content,
    ) {}

    /**
     * @return int how many lists were filled
     */
    public function import(): int
    {
        // The page rows are created on first use; a fresh database has none yet.
        $page = $this->pages->findByKey(Pages::HOME);

        if ($page === null) {
            $this->pages->install();
            $page = $this->pages->findByKey(Pages::HOME);
        }

        if ($page === null) {
            return 0;
        }

        $live = $this->pages->liveValues($page);
        $keys = array_values(array_filter(
            [HomeBlocks::CHAPTER_ITEMS, HomeBlocks::FIGURES, HomeBlocks::STORY_CARDS],
            fn (string $key): bool => ($live[$key] ?? []) === [],
        ));

        if ($keys === []) {
            return 0;
        }

        $current = App::getLocale();

        try {
            $english = $this->inLocale(Locales::DEFAULT);
            $arabic = $this->inLocale(Locales::AR);
        } finally {
            App::setLocale($current);
        }

        $values = array_intersect_key($english, array_flip($keys));
        $arabicValues = [];

        foreach ($values as $key => $value) {
            $definition = PageBlocks::get(Pages::HOME, $key);
            $arabicValues[$key] = PageBlockValue::fromArabicForm($definition, $arabic[$key], PageBlockValue::normalise($definition, $value));
        }

        $this->pages->publishBlocks($page, $values, $arabicValues);

        return count($values);
    }

    /**
     * The three lists as the home page showed them in one language.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function inLocale(string $locale): array
    {
        App::setLocale($locale);
        $this->content->flush();

        return [
            HomeBlocks::CHAPTER_ITEMS => $this->chapters(),
            HomeBlocks::FIGURES => $this->figures(),
            HomeBlocks::STORY_CARDS => $this->stories(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function chapters(): array
    {
        $chapters = $this->timeline->chapters();
        $quotes = $this->quotes->published()->values();
        $last = count($chapters) - 1;
        $items = [];

        foreach ($chapters as $index => $chapter) {
            $item = ['years' => $chapter['years']];

            foreach (array_slice($chapter['milestones'], 0, HomeBlocks::MILESTONES) as $n => $milestone) {
                $prefix = 'm'.($n + 1).'_';
                $item[$prefix.'year'] = $milestone['years'];
                $item[$prefix.'title'] = $milestone['title'];
                $item[$prefix.'label'] = $milestone['label'];
                $item[$prefix.'detail'] = $milestone['detail'];
            }

            $item += $this->photoFields($chapter['photo']);

            $quote = $index < $last ? $quotes->get($index) : null;
            $item['quote'] = $quote?->quote;
            $item['quote_credit'] = $quote?->credit();

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function figures(): array
    {
        $profile = $this->profiles->get();
        $values = [
            HomeTimeline::STAT_PUBLICATIONS => $this->visibility->isVisible(Sections::PUBLICATIONS) ? $this->publications->countPublished() : 0,
            HomeTimeline::STAT_YEARS_TEACHING => $this->timeline->yearsOfTeaching(),
            HomeTimeline::STAT_AWARDS => $this->visibility->isVisible(Sections::AWARDS) ? $this->awards->countPublished() : 0,
            HomeTimeline::STAT_STUDENTS => $profile?->students_supervised,
        ];
        $figures = [];

        foreach (array_keys(HomeTimeline::stats()) as $key) {
            $value = (int) ($values[$key] ?? 0);

            if ($value > 0) {
                $figures[] = ['value' => number_format($value), 'label' => HomeTimeline::statLabel($key, $value)];
            }
        }

        return $figures;
    }

    /**
     * @return list<array{title: string, line: string, route: string, media_id: int|null}>
     */
    private function stories(): array
    {
        $shown = array_filter(array_column($this->timeline->chapters(), 'photo'));
        $shownIds = array_map(fn (Media $photo): int => (int) $photo->id, $shown);
        $photos = $this->timeline->archivePhotos();
        usort($photos, fn (Media $a, Media $b): int => in_array((int) $a->id, $shownIds, true) <=> in_array((int) $b->id, $shownIds, true));

        // The card lines were blocks of their own (story_about, ...): their saved words, else the built-in line.
        $page = $this->pages->findByKey(Pages::HOME);
        $lines = [
            Sections::ABOUT => ($page ? $this->pages->storedText($page, 'story_about') : null) ?? __(Taglines::STORY_ABOUT),
            Sections::PUBLICATIONS => ($page ? $this->pages->storedText($page, 'story_publications') : null) ?? __(Taglines::STORY_PUBLICATIONS),
            Sections::TRIBUTES => ($page ? $this->pages->storedText($page, 'story_tributes') : null) ?? __(Taglines::STORY_TRIBUTES),
        ];
        $images = [];

        foreach (Sections::homeStory() as $index => $key) {
            $images[$key] = $photos[$index] ?? null;
        }

        return array_map(fn (array $card): array => [
            'title' => $card['title'],
            'line' => $card['summary'],
            'route' => $card['key'],
            'media_id' => $card['image'] === null ? null : (int) $card['image']->getKey(),
        ], $this->cards->cards(Sections::homeStory(), $lines, $images));
    }

    /**
     * @return array{media_id: int|null, alt_text: ?string, caption: ?string, place: ?string, year: ?string}
     */
    private function photoFields(?Media $photo): array
    {
        $year = $photo?->getCustomProperty(MediaProperties::YEAR);

        return [
            'media_id' => $photo === null ? null : (int) $photo->getKey(),
            'alt_text' => $photo?->getCustomProperty(MediaProperties::ALT_TEXT),
            'caption' => $photo?->getCustomProperty(MediaProperties::CAPTION),
            'place' => $photo?->getCustomProperty(MediaProperties::PLACE),
            'year' => is_numeric($year) ? (string) $year : null,
        ];
    }
}
