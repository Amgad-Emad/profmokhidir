<?php

namespace Tests\Feature\Seo;

use App\Constants\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RobotsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function robots_txt_keeps_crawlers_out_of_the_dashboard_and_chat_and_points_at_the_sitemap(): void
    {
        $response = $this->get(Seo::ROBOTS_PATH)
            ->assertOk()
            ->assertHeader('Content-Type', Seo::TEXT_CONTENT_TYPE);

        $this->assertSame(
            "User-agent: *\nDisallow: /admin\nDisallow: /community/chat\nDisallow: /language\n\nSitemap: ".url(Seo::SITEMAP_PATH)."\n",
            $response->getContent(),
        );
        $this->assertSame(['/admin', '/community/chat', '/language'], Seo::disallowedPaths());
    }

    #[Test]
    public function no_static_robots_file_shadows_the_route(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
        $this->assertFileDoesNotExist(public_path('sitemap.xml'));
    }
}
