<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Locales;
use App\Constants\Sections;
use App\Constants\Seo;
use App\Models\Album;
use App\Models\Commentary;
use App\Models\OtherItem;
use App\Models\Video;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The sitemaps of published content, one per language under a sitemap index:
 * the home page, every public section and the detail pages of published
 * videos, albums, commentaries and other items. Each entry carries the last
 * change of the content behind it. The Arabic sitemap lists a detail page (and
 * the About page) only when its content has an Arabic version. Lists come
 * from the content services, so they share the public page caches.
 *
 * @phpstan-type Entry array{loc: string, lastmod: string|null}
 */
class SitemapService
{
    public function __construct(
        private readonly NavigationService $navigation,
        private readonly SectionVisibilityService $visibility,
        private readonly ProfileService $profiles,
        private readonly CvEntryService $cvEntries,
        private readonly PublicationService $publications,
        private readonly AwardService $awards,
        private readonly VideoService $videos,
        private readonly AlbumService $albums,
        private readonly TeachingMaterialService $teachingMaterials,
        private readonly CommentaryService $commentaries,
        private readonly OtherItemService $otherItems,
        private readonly TributeService $tributes,
    ) {}

    /**
     * The address of each language's sitemap, for the sitemap index.
     *
     * @return array<string, string> locale => url
     */
    public function sitemaps(): array
    {
        $sitemaps = [];

        foreach (Locales::all() as $locale) {
            $sitemaps[$locale] = route(Seo::SITEMAP_LOCALE_ROUTE, ['locale' => $locale]);
        }

        return $sitemaps;
    }

    /**
     * The entries of one language's sitemap (addresses in that language).
     *
     * @return list<Entry>
     */
    public function entries(string $locale = Locales::DEFAULT): array
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return $this->build($locale);
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * @return list<Entry>
     */
    private function build(string $locale): array
    {
        $profile = $this->profiles->get();
        $lists = [
            Sections::ABOUT => new Collection($profile === null ? [] : [$profile]),
            Sections::CV => $this->cvEntries->published(),
            Sections::PUBLICATIONS => $this->publications->published(),
            Sections::AWARDS => $this->awards->published(),
            Sections::VIDEOS => $this->videos->published(),
            Sections::PICTURES => $this->albums->published(),
            Sections::TEACHING => $this->teachingMaterials->publishedFor(null),
            Sections::COMMENTARY => $this->commentaries->published(),
            Sections::OTHER => $this->otherItems->published(),
            Sections::TRIBUTES => $this->tributes->approved(),
            Sections::COMMUNITY => new Collection,
        ];

        $entries = [[
            'loc' => route('home'),
            'lastmod' => $this->format($this->latest(array_merge(...array_map(fn (Collection $list): array => $list->all(), array_values($lists))))),
        ]];

        foreach ($this->visibility->visible(Sections::all()) as $key) {
            if ($key === Sections::ABOUT && $profile !== null && ! $this->inLocale($profile, $locale)) {
                continue;
            }

            $entries[] = ['loc' => $this->navigation->section($key)['url'], 'lastmod' => $this->format($this->latest($lists[$key]->all()))];
        }

        if ($this->visibility->isVisible(Sections::TRIBUTES)) {
            $entries[] = ['loc' => route(Sections::definitions()[Sections::TRIBUTES]['route'].'.create'), 'lastmod' => null];
        }

        foreach ([Sections::VIDEOS, Sections::PICTURES, Sections::COMMENTARY, Sections::OTHER] as $key) {
            if (! $this->visibility->isVisible($key)) {
                $lists[$key] = new Collection;
            }
        }

        foreach ($lists[Sections::VIDEOS] as $video) {
            if ($this->inLocale($video, $locale)) {
                $entries[] = $this->detail(Sections::VIDEOS, $video, $video->slug);
            }
        }

        foreach ($lists[Sections::PICTURES] as $album) {
            if ($this->inLocale($album, $locale)) {
                $entries[] = $this->detail(Sections::PICTURES, $album, $album->slug);
            }
        }

        foreach ($lists[Sections::COMMENTARY] as $commentary) {
            if ($this->inLocale($commentary, $locale)) {
                $entries[] = $this->detail(Sections::COMMENTARY, $commentary, $commentary->slug);
            }
        }

        foreach ($lists[Sections::OTHER] as $item) {
            if ($item->hasDetail() && $this->inLocale($item, $locale)) {
                $entries[] = $this->detail(Sections::OTHER, $item, $item->slug);
            }
        }

        return $entries;
    }

    /**
     * @param  Video|Album|Commentary|OtherItem  $model
     * @return Entry
     */
    private function detail(string $section, Model $model, string $slug): array
    {
        return [
            'loc' => route(Sections::definitions()[$section]['route'].'.show', $slug),
            'lastmod' => $this->format($this->latest([$model])),
        ];
    }

    /**
     * Whether a page about this content belongs in the language's sitemap:
     * always in English, in another language only when it has a version there.
     */
    private function inLocale(?Model $model, string $locale): bool
    {
        if ($model === null) {
            return false;
        }

        return $locale === Locales::DEFAULT || ! method_exists($model, 'hasLocale') || $model->hasLocale($locale);
    }

    /**
     * The most recent change among the models (updated_at, else published_at).
     *
     * @param  list<Model>  $models
     */
    private function latest(array $models): ?CarbonInterface
    {
        $latest = null;

        foreach ($models as $model) {
            $changed = $model->getAttribute('updated_at') ?? $model->getAttribute('published_at');

            if ($changed instanceof CarbonInterface && ($latest === null || $changed->greaterThan($latest))) {
                $latest = $changed;
            }
        }

        return $latest;
    }

    private function format(?CarbonInterface $date): ?string
    {
        return $date?->toAtomString();
    }
}
