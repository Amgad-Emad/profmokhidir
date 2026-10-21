<?php

namespace Tests\Unit\Services;

use App\Constants\Essay;
use App\Services\EssayService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EssayServiceTest extends TestCase
{
    private EssayService $essay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->essay = $this->app->make(EssayService::class);
    }

    #[Test]
    public function headings_get_unique_ids_and_make_the_table_of_contents(): void
    {
        [$html, $contents] = $this->essay->headings('<h2>The <em>field</em></h2><p>x</p><h3>Practicals</h3><h2>The field</h2><h4>Not listed</h4><h2> </h2>');

        $this->assertSame('<h2 id="part-the-field">The <em>field</em></h2><p>x</p><h3 id="part-practicals">Practicals</h3><h2 id="part-the-field-2">The field</h2><h4>Not listed</h4><h2> </h2>', $html);
        $this->assertSame([
            ['id' => 'part-the-field', 'text' => 'The field', 'level' => 2],
            ['id' => 'part-practicals', 'text' => 'Practicals', 'level' => 3],
            ['id' => 'part-the-field-2', 'text' => 'The field', 'level' => 2],
        ], $contents);
    }

    #[Test]
    public function the_table_of_contents_needs_at_least_two_headings(): void
    {
        $this->assertSame([], $this->essay->prepare('<h2>Only one</h2><p>Text.</p>')['contents']);
        $this->assertCount(Essay::TOC_MIN_HEADINGS, $this->essay->prepare('<h2>One</h2><h2>Two</h2>')['contents']);
    }

    #[Test]
    public function footnote_markers_become_numbered_sidenotes_and_their_paragraphs_are_removed(): void
    {
        [$html, $count] = $this->essay->sidenotes('<p>First.[^b] Second.[^a] Again.[^b] Lost.[^none]</p><p>[^a]: Note <em>A</em>.</p><p>Middle.</p><p> [^b]: Note B.</p>');

        $this->assertSame(3, $count);
        $this->assertStringNotContainsString('[^', $html);
        $this->assertStringContainsString('<p>Middle.</p>', $html);
        $this->assertStringNotContainsString('<p>[^a]', $html);

        // Numbered in reading order: b is note 1 (and again note 3), a is note 2; a marker with no note is dropped.
        preg_match_all('/aria-describedby="(note-\d)"/', $html, $refs);
        $this->assertSame(['note-1', 'note-2', 'note-3'], $refs[1]);
        $this->assertMatchesRegularExpression('/id="note-1" class="sidenote__note" role="note".*Note B\./s', $html);
        $this->assertStringContainsString('Note <em>A</em>.</span></span>', $html);
        $this->assertStringContainsString('Lost.</p>', $html);
    }

    #[Test]
    public function sidenotes_can_be_numbered_after_notes_already_on_the_page(): void
    {
        [$html, $count] = $this->essay->sidenotes('<p>Text.[^1]</p><p>[^1]: Note.</p>', 2);

        $this->assertSame(1, $count);
        $this->assertStringContainsString('aria-describedby="note-2"', $html);
        $this->assertStringContainsString('>2</button>', $html);
    }

    #[Test]
    public function reading_time_is_whole_minutes_at_an_unhurried_pace_and_never_zero(): void
    {
        $words = fn (int $n): string => '<p>'.trim(str_repeat('word ', $n)).'</p>';

        $this->assertSame(1, $this->essay->readingMinutes(''));
        $this->assertSame(1, $this->essay->readingMinutes($words(10)));
        $this->assertSame(1, $this->essay->readingMinutes($words(Essay::WORDS_PER_MINUTE)));
        $this->assertSame(2, $this->essay->readingMinutes($words(Essay::WORDS_PER_MINUTE + 1)));
        $this->assertSame(2, $this->essay->readingMinutes('<p>'.str_repeat('a ', Essay::WORDS_PER_MINUTE).'</p><p>b</p>'), 'Tags separate words');
        $this->assertSame('1 '.Essay::READING_TIME_SUFFIX, $this->essay->readingTime($words(3)));
    }

    #[Test]
    public function prepare_returns_the_finished_body_with_its_contents_reading_time_and_note_count(): void
    {
        $result = $this->essay->prepare('<p>Intro.[^1]</p><h2>One</h2><p>a</p><h2>Two</h2><p>[^1]: Aside.</p>');

        $this->assertSame(1, $result['notes']);
        $this->assertSame(['part-one', 'part-two'], array_column($result['contents'], 'id'));
        $this->assertSame(1, $result['minutes']);
        $this->assertStringContainsString('<h2 id="part-two">Two</h2>', $result['html']);
        $this->assertStringNotContainsString('[^1]', $result['html']);
    }
}
