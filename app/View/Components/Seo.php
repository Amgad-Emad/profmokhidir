<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Constants\Locales;
use App\Constants\Seo as SeoConstants;
use App\Constants\SocialCards;
use App\Services\LocaleService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * The <head> metadata of a public page: title in the site pattern, meta
 * description, canonical link, Open Graph and Twitter cards with the branded
 * share image, and optional schema.org JSON-LD.
 */
class Seo extends Component
{
    public string $pageTitle;

    public ?string $metaDescription;

    public string $canonicalUrl;

    public string $imageUrl;

    public string $type;

    public ?string $jsonLd;

    public string $imageAltText;

    /** hreflang => address of this page in each language (empty for pages in one form only). @var array<string, string> */
    public array $alternates;

    public string $ogLocale;

    /** @var list<string> */
    public array $ogAlternateLocales;

    public ?string $defaultAlternate;

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $schema
     * @param  list<string>|null  $locales  languages the page exists in (content without an Arabic version: English alone)
     */
    public function __construct(
        SeoService $seo,
        LocaleService $localeService,
        Request $request,
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
        ?string $type = null,
        ?array $schema = null,
        public bool $noindex = false,
        ?string $imageAlt = null,
        ?array $locales = null,
    ) {
        $this->pageTitle = $seo->title($title);
        $this->metaDescription = $seo->description($description);
        $this->canonicalUrl = $seo->canonical($canonical);
        $this->imageUrl = $seo->image($image);
        $this->imageAltText = filled($imageAlt) ? $imageAlt : SocialCards::QUOTE;
        $this->type = $seo->ogType($type);
        $this->jsonLd = $schema === null || $schema === [] ? null : $seo->jsonLd($schema);

        $this->alternates = $localeService->alternates($request, $locales);
        $current = app()->getLocale();
        $this->ogLocale = Locales::og($current);
        $this->ogAlternateLocales = array_values(array_map(
            fn (string $locale): string => Locales::og($locale),
            array_filter(Locales::all(), fn (string $locale): bool => $locale !== $current && isset($this->alternates[Locales::definitions()[$locale]['hreflang']])),
        ));
        $this->defaultAlternate = $this->alternates[Locales::definitions()[Locales::DEFAULT]['hreflang']] ?? null;
    }

    public function siteName(): string
    {
        return __(SeoConstants::SITE_NAME);
    }

    public function render(): View
    {
        return view('components.seo');
    }
}
