<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\Sections;
use App\Models\Award;
use App\Services\AwardsPageService;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AwardsPageServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_the_section_and_the_published_awards_in_display_order(): void
    {
        $second = Award::factory()->published()->create(['sort_order' => 2, 'year' => 2010]);
        $first = Award::factory()->published()->create(['sort_order' => 1, 'year' => 1990]);
        Award::factory()->draft()->create(['sort_order' => 0]);

        $data = $this->app->make(AwardsPageService::class)->data();

        $this->assertSame('awards', $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertSame([$first->id, $second->id], $data['awards']->pluck('id')->all());
        $this->assertTrue(Cache::has(CacheKeys::AWARDS));
    }

    #[Test]
    public function it_gives_the_chapter_number_of_the_awards_section(): void
    {
        $this->assertSame(
            $this->app->make(NavigationService::class)->chapter(Sections::AWARDS),
            $this->app->make(AwardsPageService::class)->data()['chapter'],
        );
    }
}
