<?php

namespace Tests\Unit\Services;

use App\Constants\Typesetting;
use App\Services\TypesetterService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TypesetterServiceTest extends TestCase
{
    private TypesetterService $typesetter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typesetter = $this->app->make(TypesetterService::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function plainText(): array
    {
        $nbsp = Typesetting::NBSP;
        $narrow = Typesetting::NARROW_NBSP;

        return [
            'double quotes' => ['He said "knowledge is light".', 'He said “knowledge is light”.'],
            'single quotes and apostrophes' => ["'It's Khidir's way,' she said.", '‘It’s Khidir’s way,’ she said.'],
            'abbreviated decade' => ["in the '70s", 'in the ’70s'],
            'quote after a dash or bracket' => ['("now")', '(“now”)'],
            'year range' => ['1975-1992', '1975–1992'],
            'spaced year range' => ['1975 - 1992', '1975–1992'],
            'year to present' => ['1990-present', '1990–present'],
            'page range' => ['pp. 12-34', "pp.{$nbsp}12–34"],
            'spaced hyphen between words' => ['a pause - then more', 'a pause – then more'],
            'double hyphen' => ['a pause -- then more', 'a pause – then more'],
            'ellipsis' => ['and so...', 'and so…'],
            'unit' => ['12 ha at 35 °C', "12{$narrow}ha at 35{$narrow}°C"],
            'initials' => ['M. O. Khidir', "M.{$nbsp}O.{$nbsp}Khidir"],
            'title' => ['Prof. Khidir', "Prof.{$nbsp}Khidir"],
        ];
    }

    #[Test]
    #[DataProvider('plainText')]
    public function it_typesets_plain_text(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->typesetter->text($input));
    }

    #[Test]
    public function it_leaves_numbers_that_are_not_ranges_alone(): void
    {
        $this->assertSame('Call 0912-345-678 on 2024-03-01, ISBN 978-3-16-148410-0.', $this->typesetter->text('Call 0912-345-678 on 2024-03-01, ISBN 978-3-16-148410-0.'));
        $this->assertSame('a well-known agronomist', $this->typesetter->text('a well-known agronomist'));
    }

    #[Test]
    public function it_never_changes_urls_or_email_addresses(): void
    {
        $text = 'See https://example.org/a-b--c\'d... or write to a.b@example.org "today".';

        $this->assertSame('See https://example.org/a-b--c\'d... or write to a.b@example.org “today”.', $this->typesetter->text($text));
    }

    #[Test]
    public function empty_input_gives_an_empty_string(): void
    {
        $this->assertSame('', $this->typesetter->text(null));
        $this->assertSame('', $this->typesetter->text(''));
        $this->assertSame('', $this->typesetter->html(null));
        $this->assertSame('', $this->typesetter->html('   '));
        $this->assertSame('', $this->typesetter->inline(null)->toHtml());
    }

    #[Test]
    public function inline_escapes_and_sets_capitals_in_small_caps(): void
    {
        $html = $this->typesetter->inline('Reports for the FAO & "UNESCO" <b>, 1975-1980')->toHtml();

        $this->assertSame('Reports for the <span class="caps">FAO</span> &amp; “<span class="caps">UNESCO</span>” &lt;b&gt;, 1975–1980', $html);
    }

    #[Test]
    public function single_capitals_roman_numerals_and_the_mark_are_not_small_caps(): void
    {
        $this->assertSame('I wrote II and MK.', $this->typesetter->inline('I wrote II and MK.')->toHtml());
        $this->assertSame('PhD and BSc', $this->typesetter->inline('PhD and BSc')->toHtml());
    }

    #[Test]
    public function html_typesets_text_nodes_only_and_keeps_tags_and_attributes(): void
    {
        $html = $this->typesetter->html('<p>He said "<em>hello</em>" to the FAO &amp; me.</p><p><a href="https://x.org/a-b?q=1975-1992" rel="noopener">1975-1992</a></p>');

        $this->assertSame('<p>He said “<em>hello</em>” to the <span class="caps">FAO</span> &amp; me.</p><p><a href="https://x.org/a-b?q=1975-1992" rel="noopener">1975–1992</a></p>', $html);
    }

    #[Test]
    public function quote_direction_restarts_at_each_block(): void
    {
        $this->assertSame('<p>“One.”</p><p>“Two.”</p>', $this->typesetter->html('<p>"One."</p><p>"Two."</p>'));
    }

    #[Test]
    public function a_pull_quote_hangs_its_opening_quotation_mark(): void
    {
        $html = $this->typesetter->html('<blockquote>"Knowledge," he said.</blockquote><p>"Not here."</p>');

        $this->assertSame('<blockquote><span class="hang-quote">“</span>Knowledge,” he said.</blockquote><p>“Not here.”</p>', $html);
    }

    #[Test]
    public function code_is_left_verbatim(): void
    {
        $this->assertSame('<p><code>"a" -- b...</code> “c”</p>', $this->typesetter->html('<p><code>"a" -- b...</code> "c"</p>'));
    }

    #[Test]
    public function it_converts_chapter_numbers_to_capital_roman_numerals(): void
    {
        $this->assertSame('I', $this->typesetter->roman(1));
        $this->assertSame('IV', $this->typesetter->roman(4));
        $this->assertSame('VIII', $this->typesetter->roman(8));
        $this->assertSame('XI', $this->typesetter->roman(11));
        $this->assertSame('MCMLXXV', $this->typesetter->roman(1975));
        $this->assertSame('0', $this->typesetter->roman(0));
        $this->assertSame('4000', $this->typesetter->roman(4000));
    }
}
