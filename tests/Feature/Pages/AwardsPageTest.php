<?php

namespace Tests\Feature\Pages;

use App\Models\Award;
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
}
