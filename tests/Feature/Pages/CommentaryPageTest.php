<?php

namespace Tests\Feature\Pages;

use App\Constants\PageSizes;
use App\Models\Commentary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentaryPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_archive_renders_an_empty_state_without_published_commentaries(): void
    {
        Commentary::factory()->draft()->create(['title' => 'Draft essay']);

        $this->get(route('commentary'))
            ->assertOk()
            ->assertSee('<title>Commentary Corner · ', false)
            ->assertSeeInOrder(['section-header', 'Commentary', 'rule-brass', 'id="section-title"', 'Commentary Corner'], false)
            ->assertSee('Essays and reflections will be published here in due course.')
            ->assertDontSee('Draft essay')
            ->assertDontSee('aria-label="Topics"', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function the_archive_lists_date_title_summary_and_tags_with_a_tag_filter(): void
    {
        Commentary::factory()->published()->tagged(['Education', 'Policy'])->create([
            'title' => 'On teaching', 'slug' => 'on-teaching', 'date' => '2021-03-12', 'summary' => 'Why the lecture still matters.', 'sort_order' => 1,
        ]);
        Commentary::factory()->published()->tagged(['Research'])->create([
            'title' => 'On fieldwork', 'slug' => 'on-fieldwork', 'date' => null, 'summary' => null, 'sort_order' => 2,
        ]);

        $response = $this->get(route('commentary'))->assertOk();

        $response
            ->assertSeeInOrder([
                'aria-label="Topics"', 'href="'.route('commentary').'" class="filter-pill" aria-current="true">All topics',
                'href="'.e(route('commentary', ['tag' => 'Education'])).'" class="filter-pill">Education',
                'href="'.e(route('commentary', ['tag' => 'Policy'])).'" class="filter-pill">Policy',
                'href="'.e(route('commentary', ['tag' => 'Research'])).'" class="filter-pill">Research',
                'data-rise-group',
                '<time datetime="2021-03-12"', '12 March 2021', 'href="'.route('commentary.show', 'on-teaching').'"', 'On teaching', 'Why the lecture still matters.',
                'href="'.e(route('commentary', ['tag' => 'Education'])).'"', 'Education',
                'href="'.route('commentary.show', 'on-fieldwork').'"', 'On fieldwork',
            ], false)
            ->assertDontSee('Essays and reflections will be published here');

        $this->get(route('commentary', ['tag' => 'research']))
            ->assertOk()
            ->assertSee('On fieldwork')
            ->assertDontSee('On teaching')
            ->assertSee('class="filter-pill" aria-current="true">Research', false);

        // An unknown tag (e.g. a stale link) falls back to the full archive.
        $this->get(route('commentary', ['tag' => 'Nothing']))
            ->assertOk()
            ->assertSee('On teaching')
            ->assertSee('On fieldwork')
            ->assertSee('class="filter-pill" aria-current="true">All topics', false);

        $this->get(route('commentary', ['tag' => str_repeat('x', 101)]))->assertRedirect(route('commentary'));
    }

    #[Test]
    public function the_archive_paginates(): void
    {
        Commentary::factory()->published()->count(PageSizes::COMMENTARIES + 1)->create();

        $this->get(route('commentary'))->assertOk()->assertSee('Page 1 of 2')->assertSee('rel="next"', false);
        $this->get(route('commentary', ['page' => 2]))->assertOk()->assertSee('Page 2 of 2');
    }

    #[Test]
    public function the_detail_page_renders_the_reading_layout_with_byline_date_tags_link_and_neighbours(): void
    {
        $newer = Commentary::factory()->published()->create(['title' => 'Newer piece', 'slug' => 'newer', 'date' => '2022-01-01', 'sort_order' => 1]);
        $current = Commentary::factory()->published()->tagged(['Education'])->create([
            'title' => 'On teaching', 'slug' => 'on-teaching', 'date' => '2021-03-12', 'byline' => 'Prof. Mohamed Osman Khidir',
            'summary' => 'Why the lecture still matters.', 'external_url' => 'https://www.example.org/essay',
            'body' => '<p>First paragraph.</p><script>alert(1)</script><blockquote>A quote.</blockquote><p onclick="x()">Second.</p>',
            'sort_order' => 2,
        ]);
        $older = Commentary::factory()->published()->create(['title' => 'Older piece', 'slug' => 'older', 'date' => '2020-01-01', 'sort_order' => 3]);

        $response = $this->get(route('commentary.show', 'on-teaching'))->assertOk();

        $response
            ->assertSee('<title>On teaching · ', false)
            ->assertSee('<meta name="description" content="Why the lecture still matters.">', false)
            ->assertSeeInOrder([
                'sidenote-host', 'folio', 'chapter-opening', '<span class="sr-only">Chapter </span>VIII</span>', 'Commentary', 'id="section-title"', 'On teaching',
                '<span class="smallcaps">Prof. Mohamed Osman Khidir</span>', '<time datetime="2021-03-12" class="whitespace-nowrap">12 March 2021</time>',
                'First published at www.example.org',
                'Why the lecture still matters.',
                '<div class="drop-cap prose-bio mt-10" data-rise="data-rise"><p>First paragraph.</p>alert(1)<blockquote>A quote.</blockquote><p>Second.</p></div>',
                'href="https://www.example.org/essay" rel="noopener"', 'Read the original · example.org',
                'ornament--fleuron', 'aria-label="Topics"', 'href="'.e(route('commentary', ['tag' => 'Education'])).'"', 'Education',
                'aria-label="More commentary"', 'Previous', 'href="'.route('commentary.show', 'newer').'"', 'Newer piece',
                'Next', 'href="'.route('commentary.show', 'older').'"', 'Older piece',
                'href="'.route('commentary').'"', 'All commentary', 'class="seal"', 'folio--bottom',
            ], false)
            ->assertDontSee('<script', false)
            ->assertDontSee('onclick', false)
            ->assertDontSee('livewire', false);

        $this->get(route('commentary.show', 'newer'))->assertOk()->assertDontSee('>Previous<', false)->assertSee('>Next<', false);
        $this->get(route('commentary.show', 'older'))->assertOk()->assertSee('>Previous<', false)->assertDontSee('>Next<', false);
    }

    #[Test]
    public function the_detail_page_omits_empty_fields_and_hides_drafts(): void
    {
        Commentary::factory()->published()->create(['title' => 'Bare', 'slug' => 'bare', 'byline' => null, 'date' => null, 'summary' => null, 'external_url' => null, 'tags' => [], 'body' => null]);
        Commentary::factory()->draft()->create(['slug' => 'draft-essay']);

        $this->get(route('commentary.show', 'bare'))
            ->assertOk()
            ->assertDontSee('<time', false)
            ->assertDontSee('prose-bio', false)
            ->assertDontSee('Read the original')
            ->assertDontSee('aria-label="Topics"', false);

        $this->get(route('commentary.show', 'draft-essay'))->assertNotFound();
        $this->get(route('commentary.show', 'missing'))->assertNotFound();
    }

    #[Test]
    public function the_commentary_section_is_marked_current_on_list_and_detail_pages(): void
    {
        Commentary::factory()->published()->create(['slug' => 'piece']);

        foreach ([route('commentary'), route('commentary.show', 'piece')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);
            $this->assertSame([route('commentary')], array_values(array_unique($matches[1])));
        }
    }
}
