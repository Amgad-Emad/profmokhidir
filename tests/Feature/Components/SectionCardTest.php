<?php

namespace Tests\Feature\Components;

use App\Constants\Disks;
use App\Constants\Sections;
use App\Models\Album;
use App\Models\Profile;
use App\Services\AlbumService;
use App\Services\SectionCardService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    private function render(array $card, string $extra = ''): string
    {
        return Blade::render('<x-section-card :card="$card" '.$extra.' />', ['card' => $card]);
    }

    #[Test]
    public function the_whole_card_is_one_link_named_explore_and_the_section(): void
    {
        $html = $this->render($this->app->make(SectionCardService::class)->card(Sections::CV));

        $this->assertSame(1, substr_count($html, '<a '), 'one link, nothing nested');
        $this->assertStringContainsString('href="'.route('cv').'"', $html);
        $this->assertStringContainsString('aria-labelledby="section-card-cv-action section-card-cv-title"', $html);
        $this->assertStringContainsString('aria-describedby="section-card-cv-summary"', $html);
        $this->assertMatchesRegularExpression('/<span class="section-card__numeral t-label" aria-hidden="true">II<\/span>/', $html);
        $this->assertMatchesRegularExpression('/<span class="section-card__title" id="section-card-cv-title" role="heading" aria-level="3">CV and Achievements<\/span>/', $html);
        $this->assertMatchesRegularExpression('/<span id="section-card-cv-action">Explore<\/span> <span class="section-card__arrow rtl-mirror" aria-hidden="true">→<\/span>/u', $html);
        $this->assertStringNotContainsString('section-card__strip', $html, 'no strip without an image');
    }

    #[Test]
    public function the_heading_level_follows_the_page_and_an_empty_intro_is_not_described(): void
    {
        $card = ['key' => 'awards', 'numeral' => 'IV', 'title' => 'Awards', 'summary' => '', 'url' => '/awards', 'image' => null];
        $html = $this->render($card, 'level="2"');

        $this->assertStringContainsString('aria-level="2"', $html);
        $this->assertStringNotContainsString('aria-describedby', $html);
        $this->assertStringNotContainsString('section-card__summary', $html);
    }

    #[Test]
    public function an_image_is_a_decorative_lazy_duotone_strip(): void
    {
        $photo = $this->app->make(AlbumService::class)->addPhoto(Album::factory()->published()->create(), PlaceholderImage::grey(90, 30), []);
        $html = $this->render($this->app->make(SectionCardService::class)->card(Sections::PUBLICATIONS, null, $photo));

        $this->assertMatchesRegularExpression('/<span class="section-card__strip" aria-hidden="true">\s*<img src="[^"]+"[^>]* alt="" class="photo-archive"[^>]* loading="lazy" decoding="async">/', $html);
    }

    #[Test]
    public function about_and_home_lay_the_cards_out_in_the_full_width_grid(): void
    {
        Profile::factory()->published()->create();

        $about = $this->get(route('about'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<nav class="mt-20" aria-label="Continue reading">\s*<hr class="rule-brass" data-rule>\s*<ul class="section-cards mt-10" data-rise-group>/', $about);
        $this->assertSame(3, substr_count($about, 'data-section-card='));
        $main = explode('<footer', $about)[0];
        $this->assertSame(0, substr_count($main, 'sm:grid-cols-3'), 'no three-column grid inside the reading column');

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('<ul class="section-cards mt-12" data-rise-group>', $home);
        $this->assertSame(3, substr_count($home, 'data-section-card='));
        $this->assertStringNotContainsString('story-card', $home);
    }
}
