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
            ->assertSeeInOrder(['section-header', 'Awards', 'rule-brass', 'id="section-title"'], false)
            ->assertSee('Awards and recognitions will be listed here once they are published.')
            ->assertDontSee('Draft medal')
            ->assertDontSee('class="timeline', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_renders_a_timeline_with_every_populated_field_in_display_order(): void
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
                '<ol class="timeline', 'data-rise-group',
                'timeline__item', 'Lifetime Achievement Award',
                'timeline__item', '>2010<', 'Order of Merit', 'Republic of the Sudan', 'For services to agricultural education.',
                'href="https://www.example.org/honours" rel="noopener"', 'Source · example.org',
            ], false)
            ->assertDontSee('Awards and recognitions will be listed here');

        $this->assertSame(1, substr_count($response->getContent(), 'Source · '));
        $this->assertSame(1, substr_count($response->getContent(), 'font-display text-xl font-light italic text-ash'), 'Empty organisation is not rendered');
    }

    #[Test]
    public function the_awards_section_is_marked_current_in_the_navigation(): void
    {
        $html = $this->get(route('awards'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertSame([route('awards')], array_values(array_unique($matches[1])));
    }
}
