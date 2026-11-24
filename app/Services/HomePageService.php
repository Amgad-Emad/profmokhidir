<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\HomeTimeline;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Pages;
use App\Models\Profile;
use App\Support\PageBlockDefinition;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the home page as a slow walk through his life: the hero with the
 * published portrait, "A life in chapters" with a quote after a chapter, the
 * figures of "His work in numbers", the "Continue the story" cards and the
 * closing line. Every section reads the home page's own blocks (Admin › Pages ›
 * Home), so the editor shows exactly what the page shows: only what an editor
 * entered is shown, never the built-in copy, placeholders or empty-state lines.
 */
class HomePageService
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly PageContentService $pages,
        private readonly MediaService $media,
    ) {}

    /**
     * @return array{
     *     profile: Profile|null,
     *     portrait: Media|null,
     *     chapters: list<array{number: int, numeral: string, years: string, id: string, milestones: list<array{years: string, title: string, label: string, detail: ?string}>, photo: Media|null, interlude: array{quote: string, credit: ?string}|null}>,
     *     stats: list<array{key: string, value: string, label: string}>,
     *     description: string,
     *     stories: list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>,
     *     copy: array{label: string, headingFirst: string, headingSecond: string, line: string, epigraph: string, scroll: string, chapters: string, chaptersIntro: HtmlString, numbers: string, continue: string, closing: string, readStory: array{label: string, url: string}|null, leaveTribute: array{label: string, url: string}|null},
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
            // Only the image set in Admin › Pages › Home: no fallback to the profile portrait.
            'portrait' => $this->pages->image($home, 'hero_image'),
            'chapters' => $chapters,
            'stats' => $this->stats(),
            'stories' => $this->stories(),
            // The head's description only (never shown on the page): the tagline, or its built-in copy when none is entered.
            'description' => $this->pages->text($home, 'hero_tagline'),
            'copy' => [
                'label' => $this->text('hero_label'),
                'headingFirst' => $this->text('hero_heading_first'),
                'headingSecond' => $this->text('hero_heading_second'),
                'line' => $this->text('hero_tagline'),
                'epigraph' => $this->text('hero_epigraph'),
                'scroll' => $this->text('scroll_cue'),
                'chapters' => $this->text('chapters_title'),
                'chaptersIntro' => $this->pages->entered($home, 'chapters_intro') ? $this->pages->html($home, 'chapters_intro') : new HtmlString(''),
                'numbers' => $this->text('numbers_title'),
                'continue' => $this->text('continue_title'),
                'closing' => $this->text('closing_line'),
                'readStory' => $this->link('button_story'),
                'leaveTribute' => $this->link('button_tribute'),
            ],
            'anchor' => HomeTimeline::ANCHOR,
            // Sections an editor took off the page (Admin › Pages › Home, "Show on the page").
            'hidden' => array_combine(HomeBlocks::HIDEABLE, array_map(fn (string $section): bool => $this->pages->isHidden($home, $section), HomeBlocks::HIDEABLE)),
        ];
    }

    /**
     * "A life in chapters" as set in Admin › Pages › Home: each chapter's years,
     * milestones, photo (with the chapter's own alt text, caption, place and
     * year) and the quote that follows it.
     *
     * @return list<array{number: int, numeral: string, years: string, id: string, milestones: list<array{years: string, title: string, label: string, detail: ?string}>, photo: Media|null, interlude: array{quote: string, credit: ?string}|null}>
     */
    public function chapters(): array
    {
        $chapters = [];

        foreach (PageBlockDefinition::visibleItems($this->pages->items(Pages::HOME, HomeBlocks::CHAPTER_ITEMS)) as $index => $item) {
            $milestones = [];

            for ($n = 1; $n <= HomeBlocks::MILESTONES; $n++) {
                $title = $item['m'.$n.'_title'] ?? null;

                if (blank($title)) {
                    continue;
                }

                $milestones[] = [
                    'years' => (string) ($item['m'.$n.'_year'] ?? ''),
                    'title' => (string) $title,
                    'label' => (string) ($item['m'.$n.'_label'] ?? ''),
                    'detail' => filled($item['m'.$n.'_detail'] ?? null) ? (string) $item['m'.$n.'_detail'] : null,
                ];
            }

            $chapters[] = [
                'number' => $index + 1,
                'numeral' => (bool) ($item['hide_numeral'] ?? false) ? '' : (string) ($item['numeral'] ?? ''),
                'years' => (string) ($item['years'] ?? ''),
                'id' => $this->chapterId((string) ($item['years'] ?? ''), $index + 1),
                'milestones' => $milestones,
                'photo' => $this->photo($item),
                'interlude' => filled($item['quote'] ?? null)
                    ? ['quote' => (string) $item['quote'], 'credit' => filled($item['quote_credit'] ?? null) ? (string) $item['quote_credit'] : null]
                    : null,
            ];
        }

        return $chapters;
    }

    /**
     * "His work in numbers" as set in Admin › Pages › Home.
     *
     * @return list<array{key: string, value: string, label: string}>
     */
    public function stats(): array
    {
        $stats = [];

        foreach (PageBlockDefinition::visibleItems($this->pages->items(Pages::HOME, HomeBlocks::FIGURES)) as $index => $item) {
            $stats[] = ['key' => 'figure-'.($index + 1), 'value' => (string) ($item['value'] ?? ''), 'label' => (string) ($item['label'] ?? '')];
        }

        return $stats;
    }

    /**
     * The "Continue the story" cards as set in Admin › Pages › Home.
     *
     * @return list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>
     */
    public function stories(): array
    {
        $cards = [];

        foreach (PageBlockDefinition::visibleItems($this->pages->items(Pages::HOME, HomeBlocks::STORY_CARDS)) as $index => $item) {
            $cards[] = [
                'key' => 'card-'.($index + 1),
                'numeral' => (string) ($item['numeral'] ?? ''),
                'title' => (string) ($item['title'] ?? ''),
                'summary' => (string) ($item['line'] ?? ''),
                'url' => $this->pages->url($item['route'] ?? null),
                'image' => $this->media->find((int) ($item['media_id'] ?? 0)),
            ];
        }

        return $cards;
    }

    /**
     * A chapter's anchor: its decade when the years begin with one (chapter-1960s), else its position.
     */
    private function chapterId(string $years, int $position): string
    {
        return preg_match('/^(\d{3})\d/', $years, $match) === 1 ? 'chapter-'.$match[1].'0s' : HomeTimeline::ANCHOR.'-'.$position;
    }

    /**
     * A short text block as the editor entered it, or '' when it holds only the built-in copy.
     */
    private function text(string $key): string
    {
        return $this->pages->entered(Pages::HOME, $key) ? $this->pages->text(Pages::HOME, $key) : '';
    }

    /**
     * A link block when the editor gave it a label, else null (no button).
     *
     * @return array{label: string, url: string}|null
     */
    private function link(string $key): ?array
    {
        return $this->pages->entered(Pages::HOME, $key) ? $this->pages->link(Pages::HOME, $key) : null;
    }

    /**
     * A chapter's photo, described by the chapter (in the current language),
     * not by wherever the file came from. Nothing is saved to the media item.
     *
     * @param  array<string, mixed>  $item
     */
    private function photo(array $item): ?Media
    {
        $found = $this->media->find((int) ($item['media_id'] ?? 0));

        if ($found === null) {
            return null;
        }

        $photo = clone $found;
        $locale = app()->getLocale();

        foreach ([MediaProperties::ALT_TEXT, MediaProperties::CAPTION, MediaProperties::PLACE, MediaProperties::YEAR] as $property) {
            $value = $item[$property] ?? null;
            $paths = [$property];

            if ($locale !== Locales::DEFAULT && in_array($property, MediaProperties::translatable(), true)) {
                $paths[] = MediaProperties::translationPath($locale, $property);
            }

            foreach ($paths as $path) {
                blank($value) ? $photo->forgetCustomProperty($path) : $photo->setCustomProperty($path, $property === MediaProperties::YEAR ? (int) $value : $value);
            }
        }

        return $photo;
    }
}
