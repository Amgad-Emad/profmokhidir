<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Constants\Seo as SeoConstants;
use App\Constants\SocialCards;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
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

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $schema
     */
    public function __construct(
        SeoService $seo,
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
        ?string $type = null,
        ?array $schema = null,
        public bool $noindex = false,
        ?string $imageAlt = null,
    ) {
        $this->pageTitle = $seo->title($title);
        $this->metaDescription = $seo->description($description);
        $this->canonicalUrl = $seo->canonical($canonical);
        $this->imageUrl = $seo->image($image);
        $this->imageAltText = filled($imageAlt) ? $imageAlt : SocialCards::QUOTE;
        $this->type = $seo->ogType($type);
        $this->jsonLd = $schema === null || $schema === [] ? null : $seo->jsonLd($schema);
    }

    public function siteName(): string
    {
        return SeoConstants::SITE_NAME;
    }

    public function render(): View
    {
        return view('components.seo');
    }
}
