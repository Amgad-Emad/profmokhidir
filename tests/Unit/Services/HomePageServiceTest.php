<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\HomeTimeline;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Taglines;
use App\Models\Award;
use App\Models\HomeQuote;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\HomePageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomePageServiceTest extends TestCase
{
    use RefreshDatabase;

    private HomePageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(HomePageService::class);
    }

    #[Test]
    public function it_returns_empty_parts_and_the_fixed_copy_when_nothing_is_published(): void
    {
        Profile::factory()->draft()->create(['students_supervised' => 40]);
        Publication::factory()->draft()->count(2)->create();
        Award::factory()->draft()->create(['year' => 1990]);
        HomeQuote::factory()->draft()->create();

        $data = $this->service->data();

        $this->assertNull($data['profile']);
        $this->assertNull($data['portrait']);
        $this->assertSame([], $data['chapters']);
        $this->assertSame([], $data['stats']);
        $this->assertSame(HomeTimeline::ANCHOR, $data['anchor']);
        // No page copy stored: the built-in wording (App\Constants\PageDefinitions\HomeBlocks).
        $copy = $data['copy'];
        $this->assertSame((string) $copy['chaptersIntro'], HomeBlocks::CHAPTERS_INTRO);
        unset($copy['chaptersIntro']);
        $this->assertSame([
            'label' => Taglines::HERO_LABEL,
            'headingFirst' => HomeBlocks::HERO_HEADING_FIRST,
            'headingSecond' => HomeBlocks::HERO_HEADING_SECOND,
            'line' => Taglines::HERO_LINE,
            'epigraph' => Taglines::HOME_EPIGRAPH,
            'scroll' => Taglines::SCROLL_CUE,
            'chapters' => Taglines::HOME_CHAPTERS,
            'chaptersEmpty' => HomeBlocks::CHAPTERS_EMPTY,
            'numbers' => Taglines::HOME_NUMBERS,
            'numbersEmpty' => HomeBlocks::NUMBERS_EMPTY,
            'continue' => Taglines::HOME_CONTINUE,
            'closing' => Taglines::HOME_CLOSING,
            'readStory' => ['label' => Taglines::READ_STORY, 'url' => route('about')],
            'leaveTribute' => ['label' => Taglines::LEAVE_TRIBUTE, 'url' => route('tributes.create')],
        ], $copy);
    }
}
