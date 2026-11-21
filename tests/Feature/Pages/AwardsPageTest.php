<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Models\Award;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AwardsPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_an_empty_state_without_published_awards(): void
    {
        Award::factory()->draft()->create(['name' => 'Draft medal']);

        $this->get(route('awards'))
            ->assertOk()
            ->assertSee('<title>Awards · ', false)
            ->assertSeeInOrder(['chapter-opening', 'Awards', 'rule-brass', 'id="section-title"'], false)
            ->assertSee('Awards and recognitions will be listed here once they are published.')
            ->assertDontSee('Draft medal')
            ->assertDontSee('class="honour-roll', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_renders_a_roll_of_honour_with_every_populated_field_in_display_order(): void
    {
        Award::factory()->published()->create([
            'name' => 'Order of Merit', 'organisation' => 'Republic of the Sudan', 'year' => 2010,
            'description' => 'For services to agricultural education.', 'source_url' => 'https://www.example.org/honours', 'sort_order' => 2,
        ]);
        Award::factory()->published()->create([
            'name' => 'Lifetime Achievement Award', 'organisation' => null, 'year' => null, 'description' => '', 'source_url' => null, 'sort_order' => 1,
        ]);

        $response = $this->get(route('awards'))->assertOk();

        $response
            ->assertSeeInOrder([
                'class="honour-roll"', 'data-ornament="double-rule"', '<ol class="honour-roll__list">',
                'class="honour"', 'class="honour__name"', 'Lifetime Achievement Award',
                'class="honour"', 'data-ornament="rule-dot"', 'class="honour__name"', 'Order of Merit',
                'class="honour__organisation">Republic of the Sudan', 'honour__separator', 'class="honour__year">2010',
                'For services to agricultural education.',
                'href="https://www.example.org/honours" rel="noopener"', 'Source · example.org',
                'data-ornament="double-rule"',
            ], false)
            ->assertDontSee('Awards and recognitions will be listed here');

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'Source · '));
        $this->assertSame(1, substr_count($html, 'class="honour__meta"'), 'An award with neither organisation nor year has no meta line');
        $this->assertSame(1, substr_count($html, 'data-ornament="rule-dot"'), 'Ornaments separate awards; none before the first');
        $this->assertSame(2, substr_count($html, 'data-ornament="double-rule"'), 'The roll opens and closes with a double rule');
        $this->assertStringNotContainsString('class="timeline', $html);
    }

    #[Test]
    public function the_awards_section_is_marked_current_in_the_navigation(): void
    {
        $html = $this->get(route('awards'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertSame([route('awards')], array_values(array_unique($matches[1])));
    }

    #[Test]
    public function an_award_hangs_its_first_photograph_above_its_name_with_the_others_as_thumbnails_in_one_lightbox(): void
    {
        Storage::fake(Disks::PUBLIC);
        Award::factory()->published()->withImages(3)->create(['name' => 'Gold Order of Science', 'sort_order' => 1]);
        Award::factory()->published()->create(['name' => 'Shield without a photograph', 'sort_order' => 2]);

        $response = $this->get(route('awards'))->assertOk();

        $response->assertSeeInOrder([
            'class="honour-roll"', 'x-data="lightbox"',
            'class="honour__exhibit"', 'honour__lead', 'data-lightbox-item', 'alt="Placeholder photograph 1 of Gold Order of Science"',
            'class="honour__thumbs" aria-label="More photographs of Gold Order of Science"', 'honour__thumb', 'aspect-ratio: 1 / 1', 'honour__thumb',
            'class="honour__name">Gold Order of Science',
            'class="honour__name">Shield without a photograph',
            '<dialog', 'class="lightbox on-dark"',
        ], false);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'class="honour__exhibit"'), 'An award without photographs has no exhibit');
        $this->assertSame(3, substr_count($html, 'data-lightbox-item'));
        $this->assertSame(1, substr_count($html, '<dialog'));
        $roll = substr($html, strpos($html, '<ol class="honour-roll__list">'), strpos($html, '</ol>') - strpos($html, '<ol class="honour-roll__list">'));
        $this->assertStringNotContainsString('<figcaption', $roll, 'The award name is the label; photo captions are kept for the lightbox');
        $response->assertDontSee('livewire', false);
    }

    #[Test]
    public function a_roll_without_photographs_has_no_lightbox(): void
    {
        Award::factory()->published()->create();

        $html = $this->get(route('awards'))->assertOk()->getContent();

        $this->assertStringNotContainsString('x-data="lightbox"', $html);
        $this->assertStringNotContainsString('<dialog', $html);
    }
}
