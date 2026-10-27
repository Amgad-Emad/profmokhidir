<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\PageBlocks;
use App\Constants\PageBlockType;
use App\Constants\Pages;
use App\Models\Page;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Support\PageBlockDefinition;
use App\Support\PageBlockValue;
use App\Support\PreviewContext;
use App\Support\ResponsiveImage;
use App\Support\RichText;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use PDOException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * What the public views print for each editable page block (Admin › Pages).
 * A published page's blocks are cached per page (CacheKeys::page) and the
 * cache is cleared whenever the page is saved; a page that is still a draft,
 * a block that is missing and a required block left empty all fall back to
 * the built-in copy (App\Constants\PageBlocks), so nothing renders empty.
 * During a signed preview of a page its draft values are shown instead. If the
 * database cannot be reached (the error pages), the built-in copy is used.
 * Blocks are per language: the cache holds every language's stored value and
 * each request picks its own. An Arabic page shows the Arabic value; where
 * there is none, the English value with every built-in string translated
 * (copy an editor changed in English and has not translated stays English),
 * and the built-in copy in Arabic wherever the English would fall back to it.
 * Bound per request; the values read are kept for the rest of the request.
 */
class PageContentService
{
    /** @var array<string, array{values: array<string, mixed>, media: array<string, Media>}> */
    private array $loaded = [];

    public function __construct(
        private readonly PageRepositoryInterface $pages,
        private readonly PreviewContext $preview,
    ) {}

    /**
     * Every block of a page as visitors see it, keyed by block key.
     *
     * @return array<string, mixed>
     */
    public function values(string $page): array
    {
        return $this->load($page)['values'];
    }

    /**
     * A short text block, with any placeholders (":year") replaced.
     *
     * @param  array<string, string|int>  $replace
     */
    public function text(string $page, string $key, array $replace = []): string
    {
        $value = $this->values($page)[$key] ?? null;

        return strtr(is_string($value) ? $value : '', array_map('strval', $replace));
    }

    /**
     * A long text block as safe HTML: sanitised again on the way out, with
     * placeholders replaced by escaped values. Plain text is escaped.
     *
     * @param  array<string, string|int>  $replace
     */
    public function html(string $page, string $key, array $replace = []): HtmlString
    {
        $definition = PageBlocks::get($page, $key);
        $value = (string) ($this->values($page)[$key] ?? '');

        $html = match (true) {
            ! str_contains($value, '<') => $definition->inline || $value === '' ? e($value) : '<p>'.e($value).'</p>',
            $definition->inline => RichText::inline($value),
            default => RichText::sanitise($value),
        };

        return new HtmlString(strtr($html, array_map(fn ($value): string => e((string) $value), $replace)));
    }

    /**
     * A link block: its label and the address it goes to.
     *
     * @return array{label: string, url: string}
     */
    public function link(string $page, string $key): array
    {
        $value = $this->values($page)[$key];
        $default = (array) PageBlocks::get($page, $key)->default;

        return ['label' => $value['label'], 'url' => $value['url'] ?? $this->route($value['route'] ?? null, $default['route'])];
    }

    /**
     * A list block's items, in order.
     *
     * @return list<array<string, string>>
     */
    public function items(string $page, string $key): array
    {
        return $this->values($page)[$key];
    }

    /**
     * A list block keyed by each item's key (chapters, sections).
     *
     * @return array<string, array<string, string>>
     */
    public function keyed(string $page, string $key): array
    {
        return array_column($this->items($page, $key), null, 'key');
    }

    /**
     * An image block's media item, its details and crop focus taken from the
     * block (so a preview shows the draft's), or null when none is set.
     */
    public function image(string $page, string $key): ?Media
    {
        $media = $this->load($page)['media'][$key] ?? null;

        if ($media === null) {
            return null;
        }

        $value = $this->values($page)[$key];
        $media = clone $media;

        foreach ([MediaProperties::ALT_TEXT, MediaProperties::CAPTION, MediaProperties::PLACE, MediaProperties::YEAR] as $property) {
            if (filled($value[$property] ?? null)) {
                $media->setCustomProperty($property, $value[$property]);
            }
        }

        if (array_key_exists('focus_x', $value)) {
            $media->setCustomProperty(MediaProperties::FOCUS_X, $value['focus_x']);
            $media->setCustomProperty(MediaProperties::FOCUS_Y, $value['focus_y']);
        }

        return $media;
    }

    /**
     * A page's search title and description (null: automatic) and its share
     * image (null: the default card).
     *
     * @return array{title: string|null, description: string|null, image: string|null, imageAlt: string|null}
     */
    public function seo(string $page): array
    {
        $value = PageBlocks::has($page, 'seo') ? $this->values($page)['seo'] : null;
        $media = $this->load($page)['media']['seo'] ?? null;

        return [
            'title' => $value['title'] ?? null,
            'description' => $value['description'] ?? null,
            'image' => $media === null ? null : ResponsiveImage::largestUrl($media),
            'imageAlt' => $media === null ? null : ($value['alt_text'] ?? $media->getCustomProperty(MediaProperties::ALT_TEXT)),
        ];
    }

    /**
     * The share image of a page, else the site-wide one set under Header & footer.
     *
     * @return array{image: string|null, imageAlt: string|null}
     */
    public function shareImage(?string $page): array
    {
        $own = $page === null ? ['image' => null, 'imageAlt' => null] : $this->seo($page);

        if ($own['image'] !== null) {
            return ['image' => $own['image'], 'imageAlt' => $own['imageAlt']];
        }

        $site = $this->seo(Pages::NAVIGATION);

        return ['image' => $site['image'], 'imageAlt' => $site['imageAlt']];
    }

    /**
     * Forget the cached blocks of one page, or of every page.
     */
    public function flush(?string $page = null): void
    {
        foreach ($page === null ? Pages::all() : [$page] as $key) {
            Cache::forget(CacheKeys::page($key));
        }

        $this->loaded = [];
    }

    /**
     * @return array{values: array<string, mixed>, media: array<string, Media>}
     */
    private function load(string $page): array
    {
        $previewId = $this->preview->idFor(new Page);
        $locale = app()->getLocale();
        $memo = $page.'#'.($previewId ?? 0).'#'.$locale;

        if (isset($this->loaded[$memo])) {
            return $this->loaded[$memo];
        }

        try {
            $stored = $previewId === null
                ? Cache::remember(CacheKeys::page($page), CacheKeys::TTL_SECONDS, fn (): array => $this->stored($page, null))
                : $this->stored($page, $previewId);
        } catch (QueryException|PDOException) {
            // The database is unreachable (an error page is being drawn): the built-in copy.
            $stored = ['values' => [], 'media' => []];
        }

        $values = [];

        foreach (PageBlocks::for($page) as $key => $definition) {
            $values[$key] = $this->localise($definition, $stored['values'][$key] ?? [], $locale);
        }

        return $this->loaded[$memo] = ['values' => $values, 'media' => $stored['media']];
    }

    /**
     * A block's value in the language, from its stored value per language.
     *
     * @param  array<string, mixed>  $stored  locale => stored value
     */
    private function localise(PageBlockDefinition $definition, array $stored, string $locale): mixed
    {
        $english = $stored[Locales::DEFAULT] ?? null;

        if ($locale === Locales::DEFAULT) {
            return PageBlockValue::normalise($definition, $english);
        }

        $localized = $definition->localized();
        $own = $stored[$locale] ?? null;

        if (blank($own)) {
            return PageBlockValue::normalise($localized, $english === null ? null : $definition->translate($english));
        }

        // An Arabic image, link or search value holds its words only; the image,
        // its crop and the destination are the English value's.
        if (is_array($own) && is_array($english) && in_array($definition->type, [PageBlockType::Image, PageBlockType::Link, PageBlockType::Seo], true)) {
            $own = array_replace($english, array_filter($own, fn (mixed $value): bool => filled($value)));
        }

        return PageBlockValue::normalise($localized, $own);
    }

    /**
     * The stored values of a published page (or of the previewed page, drafts
     * included), with the media item each image block names.
     *
     * @return array{values: array<string, mixed>, media: array<string, Media>}
     */
    private function stored(string $page, ?int $previewId): array
    {
        $model = $this->pages->findByKey($page);
        $previewing = $model !== null && $previewId === (int) $model->getKey();

        if ($model === null || (! $previewing && ! $model->isPublished())) {
            return ['values' => [], 'media' => []];
        }

        $values = [];
        $media = [];

        foreach ($this->pages->blocks($model) as $key => $block) {
            if (! PageBlocks::has($page, (string) $key)) {
                continue;
            }

            // Every language's value (raw), so the cached copy serves each language.
            $column = $previewing && $block->hasDraft() ? 'draft_value' : 'value';
            // Raw, so an optional block emptied on purpose ("") stays empty instead of taking its default.
            $raw = json_decode((string) ($block->getAttributes()[$column] ?? 'null'), true);
            $values[$key] = is_array($raw) && Locales::isLocaleMap($raw) ? $raw : [];
            $value = $values[$key][Locales::DEFAULT] ?? null;
            $mediaId = is_array($value) ? (int) ($value['media_id'] ?? 0) : 0;

            if ($block->type->hasImage() && $mediaId > 0 && ($item = $block->media->firstWhere('id', $mediaId)) !== null) {
                $media[$key] = $item;
            }
        }

        return ['values' => $values, 'media' => $media];
    }

    private function route(?string $route, string $fallback): string
    {
        return route($route !== null && Route::has($route) ? $route : $fallback);
    }
}
