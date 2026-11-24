<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\HomeTimeline;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Pages;
use App\Constants\Taglines;
use App\Models\Award;
use App\Models\HomeQuote;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\HomePageService;
use App\Services\PageService;
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
    public function it_returns_nothing_but_what_an_editor_entered_when_nothing_is_published(): void
    {
        Profile::factory()->published()->withPortrait()->create(['students_supervised' => 40]);
        Publication::factory()->published()->count(2)->create();
        Award::factory()->published()->create(['year' => 1990]);
        HomeQuote::factory()->published()->create();
        // A home page that is not published shows none of its blocks, and never the built-in copy.
        $pages = $this->app->make(PageService::class);
        $pages->install();
        $pages->unpublish($pages->findByKey(Pages::HOME));

        $data = $this->service->data();

        $this->assertInstanceOf(Profile::class, $data['profile']);
        $this->assertNull($data['portrait'], 'The profile portrait is not used in place of the hero image');
        $this->assertSame([], $data['chapters']);
        $this->assertSame([], $data['stats']);
        $this->assertSame([], $data['stories']);
        $this->assertSame(HomeTimeline::ANCHOR, $data['anchor']);
        $this->assertSame(Taglines::HERO_LINE, $data['description'], 'Only the head keeps a description');
        $copy = $data['copy'];
        $this->assertSame('', (string) $copy['chaptersIntro']);
        unset($copy['chaptersIntro']);
        $this->assertSame([
            'label' => '',
            'headingFirst' => '',
            'headingSecond' => '',
            'line' => '',
            'epigraph' => '',
            'scroll' => '',
            'chapters' => '',
            'numbers' => '',
            'continue' => '',
            'closing' => '',
            'readStory' => null,
            'leaveTribute' => null,
        ], $copy);
    }

    #[Test]
    public function it_returns_the_wording_an_editor_published(): void
    {
        $pages = $this->app->make(PageService::class);
        $pages->install();
        $pages->publishBlocks($pages->findByKey(Pages::HOME), [
            'hero_label' => 'Entered label',
            'hero_heading_first' => '',
            'chapters_intro' => 'Entered intro.',
            'button_story' => ['label' => 'Entered button', 'route' => 'cv'],
            'button_tribute' => ['label' => '', 'route' => 'tributes.create'],
            HomeBlocks::STORY_CARDS => [['title' => 'Card', 'numeral' => '', 'line' => '', 'route' => 'cv']],
        ]);

        $data = $this->service->data();

        $this->assertSame('Entered label', $data['copy']['label']);
        $this->assertSame('', $data['copy']['headingFirst']);
        $this->assertSame('Entered intro.', (string) $data['copy']['chaptersIntro']);
        $this->assertSame(['label' => 'Entered button', 'url' => route('cv')], $data['copy']['readStory']);
        $this->assertNull($data['copy']['leaveTribute']);
        $this->assertSame('', $data['stories'][0]['numeral'], 'No numeral is made up for a card');
    }
}
