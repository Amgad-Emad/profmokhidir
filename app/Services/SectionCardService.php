<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\PageBlocks;
use App\Constants\SectionCards;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Builds the section cards (<x-section-card>) that lead from one page into
 * others: each section's chapter numeral, page heading, intro, address and the
 * card image set on its page in Admin › Pages, if any. Callers may give their
 * own line or image for a card (the home page's "Continue the story" does).
 */
class SectionCardService
{
    public function __construct(
        private readonly NavigationService $navigation,
        private readonly PageContentService $pages,
        private readonly TypesetterService $typesetter,
        private readonly SectionVisibilityService $visibility,
    ) {}

    /**
     * @param  list<string>  $keys  section keys (App\Constants\Sections), in order
     * @param  array<string, string>  $summaries  section key => line to show instead of the intro
     * @param  array<string, Media|null>  $images  section key => image to show instead of the page's card image
     * @return list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>
     */
    public function cards(array $keys, array $summaries = [], array $images = []): array
    {
        // Hidden sections get no card.
        return array_map(fn (string $key): array => $this->card($key, $summaries[$key] ?? null, $images[$key] ?? null), $this->visibility->visible(array_values($keys)));
    }

    /**
     * @return array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}
     */
    public function card(string $key, ?string $summary = null, ?Media $image = null): array
    {
        $section = $this->navigation->section($key);

        return [
            'key' => $key,
            'numeral' => $this->typesetter->roman($this->navigation->chapter($key)),
            'title' => $section['title'],
            'summary' => filled($summary) ? (string) $summary : $section['summary'],
            'url' => $section['url'],
            'image' => $image ?? $this->image($key),
        ];
    }

    /**
     * The card image set on the section's page, or null (pages without the block have none).
     */
    public function image(string $key): ?Media
    {
        return PageBlocks::has($key, SectionCards::IMAGE_BLOCK) ? $this->pages->image($key, SectionCards::IMAGE_BLOCK) : null;
    }
}
