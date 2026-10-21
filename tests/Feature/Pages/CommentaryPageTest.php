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
                'class="reading-progress" x-data="readingProgress" data-target="#essay-body" aria-hidden="true"',
                'folio', 'sidenote-host', 'chapter-opening', '<span class="sr-only">Chapter </span>VIII</span>', 'Commentary', 'id="section-title"', 'On teaching',
                '<span class="smallcaps">Prof. Mohamed Osman Khidir</span>', '<time datetime="2021-03-12" class="whitespace-nowrap">12 March 2021</time>',
                'First published at www.example.org', '1 min read',
                'Why the lecture still matters.',
                '<div id="essay-body">',
                '<div class="drop-cap prose-bio essay__body mt-10" data-rise="data-rise"><p>First paragraph.</p>alert(1)<blockquote>A quote.</blockquote><p>Second.</p></div>',
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
    public function the_essay_has_an_epigraph_a_table_of_contents_sidenotes_and_further_reading_by_tag(): void
    {
        $paragraph = str_repeat('word ', 250);
        Commentary::factory()->published()->tagged(['Education', 'Policy'])->create([
            'title' => 'On teaching', 'slug' => 'on-teaching', 'external_url' => null,
            'epigraph' => 'The field is the first classroom.', 'epigraph_attribution' => 'From a lecture, 1984',
            'body' => '<p>Opening.[^a]</p><h2>The field</h2><p>'.$paragraph.'</p><h3>Practicals</h3><p>'.$paragraph.'</p><h2>The field</h2><p>End.[^b][^missing]</p>'
                .'<p>[^a]: First <em>note</em>.</p><p>[^b]: Second note.</p>',
        ]);
        Commentary::factory()->published()->tagged(['Policy', 'Education'])->create(['title' => 'Closely related', 'slug' => 'closely', 'date' => '2001-01-01']);
        Commentary::factory()->published()->tagged(['education'])->create(['title' => 'Also related', 'slug' => 'also', 'date' => '2020-01-01']);
        Commentary::factory()->published()->tagged(['Sudan'])->create(['title' => 'Unrelated piece', 'slug' => 'unrelated']);
        Commentary::factory()->draft()->tagged(['Education'])->create(['title' => 'Draft related', 'slug' => 'draft-related']);

        $response = $this->get(route('commentary.show', 'on-teaching'))->assertOk();

        $response
            ->assertSeeInOrder([
                'essay__layout--contents',
                '<nav class="essay__contents" aria-labelledby="essay-contents-title" x-data="essayContents">', 'Contents',
                'href="#part-the-field"', 'The field', 'class="essay__contents-sub"', 'href="#part-practicals"', 'href="#part-the-field-2"',
                'chapter-opening', 'On teaching', 'chapter-opening__epigraph', 'The field is the first classroom.', '<span class="smallcaps">From a lecture, 1984</span>',
                '3 min read',
                '<details class="essay__contents-inline"', 'href="#part-the-field"',
                '<p>Opening.<span class="sidenote"', 'aria-describedby="note-1"', '>1</button>', 'First <em>note</em>.',
                '<h2 id="part-the-field">The field</h2>', '<h3 id="part-practicals">Practicals</h3>', '<h2 id="part-the-field-2">The field</h2>',
                '<p>End.<span class="sidenote"', 'aria-describedby="note-2"', 'Second note.',
                'class="essay__print-source"',
                'aria-labelledby="further-reading-title"', 'Further reading',
                'href="'.route('commentary.show', 'closely').'"', 'Closely related',
                'href="'.route('commentary.show', 'also').'"', 'Also related',
            ], false)
            ->assertDontSee('[^', false)
            ->assertDontSee('Unrelated piece')
            ->assertDontSee('Draft related');
    }

    #[Test]
    public function an_origin_note_beside_the_date_keeps_body_sidenotes_numbered_after_it(): void
    {
        Commentary::factory()->published()->create([
            'slug' => 'noted', 'date' => '2021-03-12', 'external_url' => 'https://www.example.org/essay',
            'body' => '<p>Text.[^1]</p><p>[^1]: Body note.</p>',
        ]);

        $this->get(route('commentary.show', 'noted'))
            ->assertOk()
            ->assertSeeInOrder(['aria-describedby="note-origin"', '>1</button>', 'First published at', 'aria-describedby="note-2"', '>2</button>', 'Body note.'], false);
    }

    #[Test]
    public function short_essays_have_no_table_of_contents_and_no_further_reading_without_shared_tags(): void
    {
        Commentary::factory()->published()->tagged(['Solo'])->create(['slug' => 'short', 'body' => '<h2>Only heading</h2><p>Text.</p>', 'epigraph' => null]);

        $this->get(route('commentary.show', 'short'))
            ->assertOk()
            ->assertSee('<h2 id="part-only-heading">Only heading</h2>', false)
            ->assertDontSee('essay__contents', false)
            ->assertDontSee('essay__layout--contents', false)
            ->assertDontSee('chapter-opening__epigraph', false)
            ->assertDontSee('Further reading');
    }

    #[Test]
    public function commentaries_print_as_articles(): void
    {
        $css = (string) file_get_contents(resource_path('css/archive.css'));
        $print = substr($css, (int) strpos($css, '@media print'));

        $this->assertNotSame('', $print);

        foreach (['.site-header', '.site-footer', '.reading-progress', '.essay__aside', '.essay__topics', '.essay__more', '.further-reading'] as $hidden) {
            $this->assertStringContainsString($hidden, $print, "{$hidden} is hidden on paper");
        }

        $this->assertStringContainsString('.essay__print-source', $print, 'The printed article ends with its address');
        $this->assertStringContainsString("content: ' (' attr(href) ')'", $print, 'Links print their address');
        $this->assertMatchesRegularExpression('/\.essay \.sidenote__note,\s*\.essay \.sidenote__note:not\(\.is-open\)\s*\{\s*display: block !important;/', $print, 'Sidenotes print inline');
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
