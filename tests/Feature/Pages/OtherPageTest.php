<?php

namespace Tests\Feature\Pages;

use App\Models\OtherItem;
use App\Services\OtherPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtherPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_an_empty_state_without_published_items(): void
    {
        OtherItem::factory()->draft()->create(['title' => 'Draft notice']);

        $this->get(route('other'))
            ->assertOk()
            ->assertSee('<title>Other Information · ', false)
            ->assertSeeInOrder(['section-header', 'Other Information', 'rule-brass', 'id="section-title"'], false)
            ->assertSee('Further information will be added here as it becomes available.')
            ->assertDontSee('Draft notice')
            ->assertDontSee('livewire', false);

        $this->assertSame(url('/other'), route('other'));
    }

    #[Test]
    public function it_groups_items_by_category_with_type_summary_and_destination_or_detail_link(): void
    {
        OtherItem::factory()->published()->category('In the press')->create([
            'title' => 'Interview with the Tribune', 'slug' => 'tribune', 'item_type' => 'press', 'summary' => 'A long conversation.',
            'date' => '2018-06-02', 'destination_url' => 'https://www.tribune.example.com/interview', 'body' => '<p>Ignored when a destination exists.</p>',
        ]);
        OtherItem::factory()->published()->category('Announcements')->create([
            'title' => 'Memorial lecture', 'slug' => 'memorial-lecture', 'item_type' => 'announcement', 'summary' => null,
            'date' => null, 'destination_url' => null, 'body' => '<p>Details of the lecture.</p>',
        ]);
        OtherItem::factory()->published()->create([
            'title' => 'Plain note', 'slug' => 'plain-note', 'item_type' => null, 'category' => null, 'summary' => 'Just a note.', 'destination_url' => null, 'body' => null,
        ]);

        $response = $this->get(route('other'))->assertOk();

        $response
            ->assertSeeInOrder([
                'id="category-announcements"', 'Announcements', 'data-rise-group',
                'Announcement</p>', 'href="'.route('other.show', 'memorial-lecture').'"', 'Memorial lecture', 'Read more<span class="sr-only"> about Memorial lecture</span>',
                'id="category-in-the-press"', 'In the press',
                'Press · <time datetime="2018-06-02">2 June 2018</time>', 'Interview with the Tribune', 'A long conversation.',
                'href="https://www.tribune.example.com/interview" rel="noopener"', 'Visit · tribune.example.com',
                'id="category-further-information"', OtherPageService::UNCATEGORISED_LABEL, 'Plain note', 'Just a note.',
            ], false)
            ->assertDontSee('href="'.route('other.show', 'tribune').'"', false)
            ->assertDontSee('href="'.route('other.show', 'plain-note').'"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'Read more<span'));
    }

    #[Test]
    public function the_detail_page_renders_the_sanitised_body_and_is_only_available_when_there_is_one(): void
    {
        OtherItem::factory()->published()->category('Announcements')->create([
            'title' => 'Memorial lecture', 'slug' => 'memorial-lecture', 'item_type' => 'announcement', 'summary' => 'Held each spring.',
            'date' => '2024-04-10', 'destination_url' => null, 'body' => '<p>Details.</p><script>x()</script><h2>Programme</h2>',
        ]);
        OtherItem::factory()->published()->create(['slug' => 'linked', 'destination_url' => 'https://example.org', 'body' => '<p>Body</p>']);
        OtherItem::factory()->published()->create(['slug' => 'bodiless', 'destination_url' => null, 'body' => null]);
        OtherItem::factory()->draft()->create(['slug' => 'draft-item', 'destination_url' => null, 'body' => '<p>Draft</p>']);

        $this->get(route('other.show', 'memorial-lecture'))
            ->assertOk()
            ->assertSee('<title>Memorial lecture · ', false)
            ->assertSeeInOrder([
                'max-w-measure', 'id="section-title"', 'Memorial lecture',
                'Announcement · Announcements · <time datetime="2024-04-10">10 April 2024</time>',
                'Held each spring.',
                '<div class="prose-bio mt-10" data-rise><p>Details.</p>x()<h2>Programme</h2></div>',
                'href="'.route('other').'"', 'All other information',
            ], false)
            ->assertDontSee('<script', false);

        $this->get(route('other.show', 'linked'))->assertNotFound();
        $this->get(route('other.show', 'bodiless'))->assertNotFound();
        $this->get(route('other.show', 'draft-item'))->assertNotFound();
        $this->get(route('other.show', 'missing'))->assertNotFound();
    }

    #[Test]
    public function the_other_section_is_marked_current_on_list_and_detail_pages(): void
    {
        OtherItem::factory()->published()->create(['slug' => 'note', 'destination_url' => null, 'body' => '<p>Body</p>']);

        foreach ([route('other'), route('other.show', 'note')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);
            $this->assertSame([route('other')], array_values(array_unique($matches[1])));
        }
    }
}
