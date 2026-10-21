<?php

namespace Tests\Unit\Support;

use App\Support\SearchHighlighter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SearchHighlighterTest extends TestCase
{
    private SearchHighlighter $highlighter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->highlighter = new SearchHighlighter;
    }

    #[Test]
    public function it_marks_each_searched_word_as_a_prefix_at_a_word_boundary_ignoring_case(): void
    {
        $html = $this->highlighter->highlight('Soil science and the Nile: a soils survey of Sudan', ['soil', 'nile']);

        $this->assertSame(
            '<mark class="search-mark">Soil</mark> science and the <mark class="search-mark">Nile</mark>: a <mark class="search-mark">soils</mark> survey of Sudan',
            $html,
        );
    }

    #[Test]
    public function it_escapes_the_text_and_never_marks_inside_a_word(): void
    {
        $html = $this->highlighter->highlight('<b>Art</b> & cart "art"', ['art']);

        $this->assertSame('&lt;b&gt;<mark class="search-mark">Art</mark>&lt;/b&gt; &amp; cart &quot;<mark class="search-mark">art</mark>&quot;', $html);
        $this->assertSame('', $this->highlighter->highlight('   ', ['x']));
        $this->assertSame('Plain &amp; safe', $this->highlighter->highlight('Plain & safe', []));
    }

    #[Test]
    public function an_excerpt_strips_tags_centres_on_the_first_match_and_cuts_at_word_boundaries(): void
    {
        $text = '<p>'.str_repeat('word ', 60).'The irrigation of the Gezira scheme changed the economy of the region. '.str_repeat('tail ', 60).'</p>';

        $excerpt = $this->highlighter->excerpt($text, ['gezira'], 120);

        $this->assertStringStartsWith('… ', $excerpt);
        $this->assertStringEndsWith(' …', $excerpt);
        $this->assertStringContainsString('<mark class="search-mark">Gezira</mark>', $excerpt);
        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertDoesNotMatchRegularExpression('/\bwor …$/', $excerpt, 'Cut at a word boundary');
        $this->assertLessThanOrEqual(130, mb_strlen(strip_tags($excerpt)));
    }

    #[Test]
    public function a_short_text_without_a_match_is_returned_whole(): void
    {
        $this->assertSame('A short summary.', $this->highlighter->excerpt('A short summary.', ['nothing']));
        $this->assertSame('', $this->highlighter->excerpt(null, ['x']));
        $this->assertSame('Fish &amp; chips', $this->highlighter->excerpt('Fish &amp; chips', ['x']), 'Entities are decoded once, then escaped');
    }
}
