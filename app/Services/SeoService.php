<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Seo;
use App\Constants\SocialCards;
use App\Models\Profile;
use App\Models\Publication;
use App\Support\ResponsiveImage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Builds what the <head> of a public page needs: the title pattern, a meta
 * description cut from content, the canonical URL, the share image, the
 * robots.txt body and schema.org JSON-LD for the Professor and his publications.
 */
class SeoService
{
    /**
     * "Section · Professor Mohamed Osman Khidir", or the site name alone.
     */
    public function title(?string $title = null): string
    {
        $title = $this->clean($title);

        return $title === null ? Seo::SITE_NAME : $title.Seo::TITLE_SEPARATOR.Seo::SITE_NAME;
    }

    /**
     * The first non-empty candidate, stripped of markup and cut to the limit.
     */
    public function description(?string ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $text = $this->clean($candidate);

            if ($text !== null) {
                return Str::limit($text, Seo::DESCRIPTION_MAX_LENGTH - mb_strlen(Seo::DESCRIPTION_ELLIPSIS), Seo::DESCRIPTION_ELLIPSIS);
            }
        }

        return null;
    }

    /**
     * The canonical address: the given URL or the current one without its query string.
     */
    public function canonical(?string $url = null): string
    {
        return $url ?? url()->current();
    }

    /**
     * Absolute URL of a share card (the shared site card by default).
     */
    public function image(?string $card = null): string
    {
        return asset($card ?? SocialCards::SITE);
    }

    public function ogType(?string $type = null): string
    {
        return in_array($type, Seo::ogTypes(), true) ? $type : Seo::OG_TYPE_WEBSITE;
    }

    /**
     * The robots.txt body: crawlers stay out of the dashboard and chat, and are pointed at the sitemap.
     */
    public function robots(): string
    {
        $lines = ['User-agent: *'];

        foreach (Seo::disallowedPaths() as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url(Seo::SITEMAP_PATH);

        return implode("\n", $lines)."\n";
    }

    /**
     * schema.org Person for the About page.
     *
     * @return array<string, mixed>
     */
    public function person(Profile $profile, ?Media $portrait = null, ?string $url = null): array
    {
        $expertise = array_values(array_filter(array_map(
            fn ($item): ?string => is_string($item) ? $this->clean($item) : null,
            is_array($profile->expertise) ? $profile->expertise : [],
        )));

        return $this->compact([
            '@context' => Seo::SCHEMA_CONTEXT,
            '@type' => Seo::SCHEMA_PERSON,
            'name' => $this->clean($profile->name),
            'honorificPrefix' => 'Professor',
            'jobTitle' => $this->clean($profile->title),
            'affiliation' => $this->clean($profile->affiliation) === null ? null : [
                '@type' => Seo::SCHEMA_ORGANIZATION,
                'name' => $this->clean($profile->affiliation),
            ],
            'description' => $this->description($profile->short_bio),
            'image' => $portrait === null ? null : ResponsiveImage::largestUrl($portrait),
            'url' => $url ?? url()->current(),
            'knowsAbout' => $expertise === [] ? null : $expertise,
            'email' => $this->clean($profile->contact_email_public),
        ]);
    }

    /**
     * schema.org ScholarlyArticle for one publication.
     *
     * @return array<string, mixed>
     */
    public function scholarlyArticle(Publication $publication, ?string $listUrl = null): array
    {
        $authors = array_values(array_filter(array_map(
            fn (string $name): ?string => $this->clean($name),
            explode(Seo::AUTHOR_SEPARATOR, (string) $publication->authors),
        )));

        $doi = $this->clean($publication->doi);
        $venue = $this->clean($publication->venue_publisher);

        return $this->compact([
            '@context' => Seo::SCHEMA_CONTEXT,
            '@type' => Seo::SCHEMA_SCHOLARLY_ARTICLE,
            'headline' => $this->clean($publication->title),
            'name' => $this->clean($publication->title),
            'author' => $authors === [] ? null : array_map(fn (string $name): array => ['@type' => Seo::SCHEMA_PERSON, 'name' => $name], $authors),
            'datePublished' => $publication->year === null ? null : (string) $publication->year,
            'genre' => $publication->type?->label(),
            'publisher' => $venue === null ? null : ['@type' => Seo::SCHEMA_ORGANIZATION, 'name' => $venue],
            'identifier' => $doi === null ? null : ['@type' => Seo::SCHEMA_PROPERTY_VALUE, 'propertyID' => Seo::SCHEMA_DOI, 'value' => $doi],
            'sameAs' => $doi === null ? null : Seo::DOI_BASE_URL.ltrim($doi, '/'),
            'abstract' => $this->description($publication->abstract),
            'url' => $this->clean($publication->url) ?? ($doi === null ? null : Seo::DOI_BASE_URL.ltrim($doi, '/')) ?? $listUrl,
        ]);
    }

    /**
     * One ScholarlyArticle per publication on the page, or null when there are none.
     *
     * @param  iterable<Publication>  $publications
     * @return list<array<string, mixed>>|null
     */
    public function scholarlyArticles(iterable $publications, ?string $listUrl = null): ?array
    {
        $graph = [];

        foreach ($publications as $publication) {
            $graph[] = $this->scholarlyArticle($publication, $listUrl);
        }

        return $graph === [] ? null : $graph;
    }

    /**
     * JSON-LD text safe to print inside a <script> element. A list of schemas
     * becomes a graph that shares the context.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $schema
     */
    public function jsonLd(array $schema): string
    {
        if (array_is_list($schema)) {
            $schema = [
                '@context' => Seo::SCHEMA_CONTEXT,
                '@graph' => array_map(fn (array $item): array => array_diff_key($item, ['@context' => true]), $schema),
            ];
        }

        return json_encode($schema, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * Plain, single-spaced text or null when blank.
     */
    private function clean(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function compact(array $values): array
    {
        return array_filter($values, fn ($value): bool => $value !== null && $value !== [] && $value !== '');
    }
}
