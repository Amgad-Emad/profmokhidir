<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\PublicationType;
use App\Constants\Seo;
use App\Constants\SocialCards;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoServiceTest extends TestCase
{
    use RefreshDatabase;

    private SeoService $seo;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->seo = $this->app->make(SeoService::class);
    }

    #[Test]
    public function titles_follow_the_section_site_pattern_and_fall_back_to_the_site_name(): void
    {
        $this->assertSame('Awards'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME, $this->seo->title('Awards'));
        $this->assertSame('Awards'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME, $this->seo->title("  <b>Awards</b>\n"));
        $this->assertSame(Seo::SITE_NAME, $this->seo->title(null));
        $this->assertSame(Seo::SITE_NAME, $this->seo->title('   '));
    }

    #[Test]
    public function descriptions_take_the_first_filled_candidate_strip_markup_and_cut_to_the_limit(): void
    {
        $this->assertNull($this->seo->description(null, '', '  '));
        $this->assertSame('Second', $this->seo->description(null, '<p>Second</p>', 'Third'));
        $this->assertSame('One two', $this->seo->description("One\n\n  two"));
        $this->assertSame('A & B', $this->seo->description('A &amp; B'));

        $long = $this->seo->description(str_repeat('word ', 60));
        $this->assertSame(Seo::DESCRIPTION_MAX_LENGTH, mb_strlen($long));
        $this->assertStringEndsWith(Seo::DESCRIPTION_ELLIPSIS, $long);
    }

    #[Test]
    public function canonical_image_and_type_have_sensible_defaults(): void
    {
        $this->assertSame('https://example.org/x', $this->seo->canonical('https://example.org/x'));
        $this->assertSame(url()->current(), $this->seo->canonical());
        $this->assertSame(asset(SocialCards::SITE), $this->seo->image());
        $this->assertSame(asset(SocialCards::TRIBUTES), $this->seo->image(SocialCards::TRIBUTES));
        $this->assertSame(asset(SocialCards::TRIBUTES), $this->seo->image(asset(SocialCards::TRIBUTES)), 'Absolute URLs pass through');
        $this->assertSame(Seo::OG_TYPE_WEBSITE, $this->seo->ogType());
        $this->assertSame(Seo::OG_TYPE_WEBSITE, $this->seo->ogType('nonsense'));
        $this->assertSame(Seo::OG_TYPE_ARTICLE, $this->seo->ogType(Seo::OG_TYPE_ARTICLE));
    }

    #[Test]
    public function robots_disallows_the_dashboard_and_chat_and_names_the_sitemap(): void
    {
        $this->assertSame("User-agent: *\nDisallow: /admin\nDisallow: /community/chat\nDisallow: /language\n\nSitemap: ".url(Seo::SITEMAP_PATH)."\n", $this->seo->robots());
    }

    #[Test]
    public function person_schema_includes_only_populated_fields(): void
    {
        $full = Profile::factory()->make([
            'name' => 'Mohamed Osman Khidir',
            'title' => 'Professor Emeritus',
            'affiliation' => 'University of Khartoum',
            'short_bio' => '<p>Teacher and scholar.</p>',
            'expertise' => ['Agronomy', ' ', 'Soil'],
            'contact_email_public' => 'office@example.com',
        ]);

        $schema = $this->seo->person($full, null, 'https://example.org/about');

        $this->assertSame([
            '@context' => Seo::SCHEMA_CONTEXT,
            '@type' => Seo::SCHEMA_PERSON,
            'name' => 'Mohamed Osman Khidir',
            'honorificPrefix' => 'Professor',
            'jobTitle' => 'Professor Emeritus',
            'affiliation' => ['@type' => Seo::SCHEMA_ORGANIZATION, 'name' => 'University of Khartoum'],
            'description' => 'Teacher and scholar.',
            'url' => 'https://example.org/about',
            'knowsAbout' => ['Agronomy', 'Soil'],
            'email' => 'office@example.com',
        ], $schema);

        $sparse = Profile::factory()->make(['name' => 'M. Khidir', 'title' => null, 'affiliation' => '', 'short_bio' => null, 'expertise' => null, 'contact_email_public' => null]);

        $this->assertSame(['@context', '@type', 'name', 'honorificPrefix', 'url'], array_keys($this->seo->person($sparse)));
    }

    #[Test]
    public function scholarly_article_schema_maps_the_publication_fields(): void
    {
        $publication = Publication::factory()->make([
            'title' => 'Dryland Agronomy',
            'type' => PublicationType::Book,
            'authors' => 'Khidir, M. O.; Ali, A.;',
            'year' => 1978,
            'venue_publisher' => 'Khartoum Press',
            'doi' => '/10.1000/dry',
            'abstract' => '<p>Abstract.</p>',
            'url' => null,
        ]);

        $schema = $this->seo->scholarlyArticle($publication, 'https://example.org/publications');

        $this->assertSame(Seo::SCHEMA_SCHOLARLY_ARTICLE, $schema['@type']);
        $this->assertSame('Dryland Agronomy', $schema['headline']);
        $this->assertSame([['@type' => Seo::SCHEMA_PERSON, 'name' => 'Khidir, M. O.'], ['@type' => Seo::SCHEMA_PERSON, 'name' => 'Ali, A.']], $schema['author']);
        $this->assertSame('1978', $schema['datePublished']);
        $this->assertSame(PublicationType::Book->label(), $schema['genre']);
        $this->assertSame(['@type' => Seo::SCHEMA_ORGANIZATION, 'name' => 'Khartoum Press'], $schema['publisher']);
        $this->assertSame(['@type' => Seo::SCHEMA_PROPERTY_VALUE, 'propertyID' => Seo::SCHEMA_DOI, 'value' => '/10.1000/dry'], $schema['identifier']);
        $this->assertSame('https://doi.org/10.1000/dry', $schema['sameAs']);
        $this->assertSame('Abstract.', $schema['abstract']);
        $this->assertSame('https://doi.org/10.1000/dry', $schema['url'], 'DOI link when there is no URL');

        $bare = Publication::factory()->make(['authors' => '', 'year' => null, 'venue_publisher' => null, 'doi' => null, 'abstract' => null, 'url' => null]);
        $bareSchema = $this->seo->scholarlyArticle($bare, 'https://example.org/publications');
        $this->assertSame('https://example.org/publications', $bareSchema['url']);
        $this->assertArrayNotHasKey('author', $bareSchema);
        $this->assertArrayNotHasKey('identifier', $bareSchema);
        $this->assertArrayNotHasKey('datePublished', $bareSchema);

        $this->assertNull($this->seo->scholarlyArticles([]));
        $this->assertCount(2, $this->seo->scholarlyArticles([$publication, $bare]));
    }

    #[Test]
    public function json_ld_is_safe_inside_a_script_element_and_merges_lists_into_a_graph(): void
    {
        $json = $this->seo->jsonLd(['@context' => Seo::SCHEMA_CONTEXT, '@type' => 'Thing', 'name' => '</script><b>&x']);

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertSame('</script><b>&x', json_decode($json, true)['name']);
        $this->assertStringContainsString('"@context":"https://schema.org"', $json, 'Slashes are not escaped');

        $graph = json_decode($this->seo->jsonLd([
            ['@context' => Seo::SCHEMA_CONTEXT, '@type' => 'A'],
            ['@context' => Seo::SCHEMA_CONTEXT, '@type' => 'B'],
        ]), true);

        $this->assertSame(['@context' => Seo::SCHEMA_CONTEXT, '@graph' => [['@type' => 'A'], ['@type' => 'B']]], $graph);
    }
}
