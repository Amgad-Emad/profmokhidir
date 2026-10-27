<?php

namespace Tests\Feature\Locale;

use App\Constants\Seo;
use App\Models\Commentary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HreflangAndSitemapTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_public_page_names_both_languages_with_english_as_x_default(): void
    {
        foreach (['/publications', '/ar/publications'] as $path) {
            $this->get($path)
                ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/publications').'">', false)
                ->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/ar/publications').'">', false)
                ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/publications').'">', false);
        }
    }

    #[Test]
    public function canonical_and_open_graph_locales_follow_the_page_language(): void
    {
        $this->get('/ar/about')
            ->assertSee('<link rel="canonical" href="'.url('/ar/about').'">', false)
            ->assertSee('<meta property="og:locale" content="ar_AR">', false)
            ->assertSee('<meta property="og:locale:alternate" content="en_GB">', false);

        $this->get('/about')
            ->assertSee('<link rel="canonical" href="'.url('/about').'">', false)
            ->assertSee('<meta property="og:locale" content="en_GB">', false)
            ->assertSee('<meta property="og:locale:alternate" content="ar_AR">', false);
    }

    #[Test]
    public function the_sitemap_index_points_at_one_sitemap_per_language(): void
    {
        $xml = simplexml_load_string($this->get(Seo::SITEMAP_PATH)->assertOk()->getContent());

        $this->assertSame([url('/sitemaps/en.xml'), url('/sitemaps/ar.xml')], array_map(fn ($sitemap) => (string) $sitemap->loc, iterator_to_array($xml->sitemap, false)));
        $this->get('/sitemaps/fr.xml')->assertNotFound();
        $this->get(Seo::ROBOTS_PATH)->assertSee('Disallow: /language');
    }

    #[Test]
    public function the_arabic_sitemap_lists_only_content_that_has_an_arabic_version(): void
    {
        $translated = Commentary::factory()->published()->create(['slug' => 'translated', 'title' => ['en' => 'Translated', 'ar' => 'مترجمة']]);
        $english = Commentary::factory()->published()->create(['slug' => 'english-only', 'title' => 'English only']);

        $arabic = $this->locations('/sitemaps/ar.xml');
        $this->assertContains(url('/ar'), $arabic);
        $this->assertContains(url('/ar/commentary'), $arabic);
        $this->assertContains(url('/ar/commentary/'.$translated->slug), $arabic);
        $this->assertNotContains(url('/ar/commentary/'.$english->slug), $arabic);
        $this->assertEmpty(array_filter($arabic, fn (string $url): bool => ! str_starts_with($url, url('/ar'))));

        $english_ = $this->locations('/sitemaps/en.xml');
        $this->assertContains(url('/commentary/'.$translated->slug), $english_);
        $this->assertContains(url('/commentary/'.$english->slug), $english_);
    }

    /**
     * @return list<string>
     */
    private function locations(string $path): array
    {
        $xml = simplexml_load_string($this->get($path)->assertOk()->getContent());

        return array_map(fn ($url) => (string) $url->loc, iterator_to_array($xml->url, false));
    }
}
