<?php

namespace Tests\Feature\Seo;

use App\Constants\Breadcrumbs;
use App\Constants\Disks;
use App\Constants\Seo;
use App\Constants\SocialCards;
use App\Models\Album;
use App\Models\Commentary;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every public page carries a unique title in the site pattern, a description,
 * a canonical link, Open Graph and Twitter cards with the branded share image,
 * and the About and Publications pages add schema.org JSON-LD.
 */
class MetaTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    /**
     * @return list<array{string, string}>
     */
    private function publicPages(): array
    {
        $video = Video::factory()->published()->create();
        $album = Album::factory()->published()->create();
        $commentary = Commentary::factory()->published()->create();
        $item = OtherItem::factory()->published()->create(['destination_url' => null, 'body' => '<p>Body</p>']);

        return [
            [route('home'), Seo::SITE_NAME],
            [route('about'), 'About Prof. Mokhidir'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('cv'), 'CV and Achievements'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('publications'), 'Publications'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('awards'), 'Awards'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('videos'), 'Videos'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('videos.show', $video->slug), e($video->title).Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('pictures'), 'Pictures'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('pictures.show', $album->slug), e($album->title).Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('teaching'), 'Teaching Materials'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('commentary'), 'Commentary Corner'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('commentary.show', $commentary->slug), e($commentary->title).Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('community'), 'Scientific Community'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('other'), 'Other Information'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('other.show', $item->slug), e($item->title).Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('tributes'), 'Tributes'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
            [route('tributes.create'), 'Leave a tribute'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME],
        ];
    }

    #[Test]
    public function every_public_page_has_a_unique_title_description_canonical_and_share_cards(): void
    {
        $titles = [];

        foreach ($this->publicPages() as [$url, $title]) {
            $response = $this->get($url);
            $this->assertSame(200, $response->status(), $url);
            $html = $response->getContent();

            preg_match('/<title>(.*?)<\/title>/', $html, $match);
            $this->assertSame($title, $match[1], $url);
            $titles[] = $match[1];

            $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]{10,}">/', $html, $url);
            $this->assertStringContainsString('<link rel="canonical" href="'.$url.'">', $html, $url);
            $this->assertStringContainsString('<meta property="og:url" content="'.$url.'">', $html, $url);
            $this->assertStringContainsString('<meta property="og:title" content="'.$title.'">', $html, $url);
            $this->assertStringContainsString('<meta property="og:site_name" content="'.Seo::SITE_NAME.'">', $html, $url);
            $card = $url === route('tributes') ? SocialCards::TRIBUTES : SocialCards::SITE;
            $this->assertStringContainsString('<meta property="og:image" content="'.asset($card).'">', $html, $url);
            $this->assertStringContainsString('<meta property="og:image:width" content="'.SocialCards::WIDTH.'">', $html, $url);
            $this->assertStringContainsString('<meta name="twitter:card" content="'.Seo::TWITTER_CARD.'">', $html, $url);
            $this->assertStringContainsString('<meta name="twitter:title" content="'.$title.'">', $html, $url);
            $this->assertStringContainsString('<meta name="twitter:image" content="'.asset($card).'">', $html, $url);
            $this->assertStringNotContainsString('name="robots"', $html, $url.' is indexable');
            $this->assertSame(1, substr_count($html, '<title>'), $url);
        }

        $this->assertSame($titles, array_unique($titles), 'Titles are unique');
    }

    #[Test]
    public function the_tributes_page_keeps_its_own_share_card(): void
    {
        $this->get(route('tributes'))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset(SocialCards::TRIBUTES).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset(SocialCards::TRIBUTES).'">', false);
    }

    #[Test]
    public function descriptions_come_from_content_and_are_cut_to_the_limit(): void
    {
        $long = str_repeat('Knowledge is the light we pass on. ', 10);
        Profile::factory()->published()->create(['short_bio' => '<p>'.$long.'</p>']);

        $html = $this->get(route('about'))->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]+)">/', $html, $match);
        $this->assertLessThanOrEqual(Seo::DESCRIPTION_MAX_LENGTH, mb_strlen($match[1]));
        $this->assertStringStartsWith('Knowledge is the light we pass on.', $match[1]);
        $this->assertStringEndsWith(Seo::DESCRIPTION_ELLIPSIS, $match[1]);
        $this->assertStringNotContainsString('&lt;p&gt;', $match[1]);
    }

    #[Test]
    public function the_about_page_carries_person_json_ld_from_the_published_profile(): void
    {
        Profile::factory()->published()->create([
            'name' => 'Mohamed Osman Khidir',
            'title' => 'Professor Emeritus of Agronomy',
            'affiliation' => 'University of Khartoum',
            'expertise' => ['Agronomy', 'Soil fertility'],
        ]);

        $html = $this->get(route('about'))->assertOk()->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $match);
        $graph = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);

        // The Person shares one graph with the page's BreadcrumbList.
        $this->assertSame(Seo::SCHEMA_CONTEXT, $graph['@context']);
        $this->assertSame([Seo::SCHEMA_PERSON, Breadcrumbs::SCHEMA_LIST], array_column($graph['@graph'], '@type'));
        $schema = $graph['@graph'][0];
        $this->assertSame('Mohamed Osman Khidir', $schema['name']);
        $this->assertSame('Professor Emeritus of Agronomy', $schema['jobTitle']);
        $this->assertSame(['@type' => Seo::SCHEMA_ORGANIZATION, 'name' => 'University of Khartoum'], $schema['affiliation']);
        $this->assertSame(['Agronomy', 'Soil fertility'], $schema['knowsAbout']);
        $this->assertSame(route('about'), $schema['url']);
        $this->assertStringContainsString('<meta property="og:type" content="'.Seo::OG_TYPE_PROFILE.'">', $html);
    }

    #[Test]
    public function the_about_page_has_only_the_breadcrumb_list_without_a_published_profile(): void
    {
        Profile::factory()->draft()->create();

        $html = $this->get(route('about'))->assertOk()->getContent();

        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $this->assertCount(1, $matches[1]);
        $this->assertSame(Breadcrumbs::SCHEMA_LIST, json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR)['@type']);
        $this->assertStringNotContainsString(Seo::SCHEMA_PERSON, $matches[1][0]);
    }

    #[Test]
    public function the_publications_page_carries_a_scholarly_article_per_listed_publication(): void
    {
        Publication::factory()->published()->create(['title' => 'Dryland Agronomy', 'doi' => '10.1000/dry', 'year' => 1978, 'authors' => 'Khidir, M. O.; Ali, A.']);
        Publication::factory()->published()->create(['title' => 'Soil Water', 'doi' => null, 'url' => null, 'year' => 1990]);
        Publication::factory()->draft()->create(['title' => 'Unpublished draft']);

        $html = $this->get(route('publications'))->assertOk()->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $match);
        $schema = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Seo::SCHEMA_CONTEXT, $schema['@context']);
        $this->assertSame([Seo::SCHEMA_SCHOLARLY_ARTICLE, Seo::SCHEMA_SCHOLARLY_ARTICLE, Breadcrumbs::SCHEMA_LIST], array_column($schema['@graph'], '@type'));
        $schema['@graph'] = array_values(array_filter($schema['@graph'], fn (array $item): bool => $item['@type'] === Seo::SCHEMA_SCHOLARLY_ARTICLE));
        $this->assertCount(2, $schema['@graph']);
        $this->assertEqualsCanonicalizing(['Dryland Agronomy', 'Soil Water'], array_column($schema['@graph'], 'headline'));
        $this->assertStringNotContainsString('Unpublished draft', $match[1]);

        $dry = collect($schema['@graph'])->firstWhere('headline', 'Dryland Agronomy');
        $this->assertSame('1978', $dry['datePublished']);
        $this->assertSame(['@type' => Seo::SCHEMA_PROPERTY_VALUE, 'propertyID' => Seo::SCHEMA_DOI, 'value' => '10.1000/dry'], $dry['identifier']);
        $this->assertSame('https://doi.org/10.1000/dry', $dry['sameAs']);
        $this->assertSame(['Khidir, M. O.', 'Ali, A.'], array_column($dry['author'], 'name'));
    }

    #[Test]
    public function filtered_publication_pages_point_their_canonical_at_the_unfiltered_list(): void
    {
        Publication::factory()->published()->create(['year' => 1990]);

        $this->get(route('publications', ['year' => 1990]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('publications').'">', false);
    }

    #[Test]
    public function signed_in_and_transactional_screens_are_not_indexed(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('<meta name="robots" content="'.Seo::ROBOTS_NOINDEX.'">', false);
        $this->get(route('tributes.thanks'))->assertOk()->assertSee('<meta name="robots" content="'.Seo::ROBOTS_NOINDEX.'">', false);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="'.Seo::ROBOTS_NOINDEX.'">', false)
            ->assertSee('<title>Dashboard · Admin · '.Seo::SITE_NAME.'</title>', false);
    }
}
