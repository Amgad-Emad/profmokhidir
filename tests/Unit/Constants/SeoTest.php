<?php

namespace Tests\Unit\Constants;

use App\Constants\Seo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SeoTest extends TestCase
{
    #[Test]
    public function it_fixes_the_site_name_pattern_limits_and_crawler_paths(): void
    {
        $this->assertSame('Professor Mohamed Osman Khidir', Seo::SITE_NAME);
        $this->assertSame(' · ', Seo::TITLE_SEPARATOR);
        $this->assertSame(160, Seo::DESCRIPTION_MAX_LENGTH);
        $this->assertSame('summary_large_image', Seo::TWITTER_CARD);
        $this->assertSame(['website', 'article', 'profile'], Seo::ogTypes());
        $this->assertSame(['/admin', '/community/chat'], Seo::disallowedPaths());
        $this->assertSame('/sitemap.xml', Seo::SITEMAP_PATH);
        $this->assertSame('/robots.txt', Seo::ROBOTS_PATH);
        $this->assertSame('https://schema.org', Seo::SCHEMA_CONTEXT);
    }
}
