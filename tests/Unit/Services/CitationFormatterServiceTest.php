<?php

namespace Tests\Unit\Services;

use App\Constants\CitationStyle;
use App\Constants\PublicationType;
use App\Models\Publication;
use App\Services\CitationFormatterService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * One reference per style and type of work. Publications are made in memory:
 * the formatter reads only their attributes.
 */
class CitationFormatterServiceTest extends TestCase
{
    private CitationFormatterService $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = $this->app->make(CitationFormatterService::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function article(array $attributes = []): Publication
    {
        return Publication::factory()->make([
            'type' => PublicationType::JournalArticle,
            'title' => 'Water use efficiency of sorghum.',
            'authors' => 'Khidir, Mohamed Osman; Smith, John',
            'year' => 1971,
            'venue_publisher' => 'Experimental Agriculture',
            'doi' => '10.1000/abc',
            'url' => 'https://www.example.org/article',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function book(array $attributes = []): Publication
    {
        return Publication::factory()->make([
            'type' => PublicationType::Book,
            'title' => 'Principles of Dryland Agronomy',
            'authors' => 'Khidir, M. O.',
            'year' => 1978,
            'venue_publisher' => 'Khartoum University Press',
            'doi' => null,
            'url' => null,
            ...$attributes,
        ]);
    }

    #[Test]
    public function apa_lists_family_names_with_initials_the_year_and_the_doi(): void
    {
        $this->assertSame(
            'Khidir, M. O., & Smith, J. (1971). Water use efficiency of sorghum. Experimental Agriculture. https://doi.org/10.1000/abc',
            $this->formatter->format($this->article(), CitationStyle::Apa),
        );
        $this->assertSame(
            'Khidir, M. O., &amp; Smith, J. (1971). Water use efficiency of sorghum. <i>Experimental Agriculture</i>. https://doi.org/10.1000/abc',
            $this->formatter->html($this->article(), CitationStyle::Apa),
        );
    }

    #[Test]
    public function apa_italicises_a_book_title_with_a_roman_full_stop_and_handles_missing_parts(): void
    {
        $this->assertSame(
            'Khidir, M. O. (1978). <i>Principles of Dryland Agronomy</i>. Khartoum University Press.',
            $this->formatter->html($this->book(), CitationStyle::Apa),
        );

        $this->assertSame(
            '<i>Principles of Dryland Agronomy</i>. (n.d.). Khartoum University Press.',
            $this->formatter->html($this->book(['authors' => null, 'year' => null]), CitationStyle::Apa),
        );

        $this->assertSame(
            'Khidir, M. O., Smith, J., & Jones, A. (1971). Water use efficiency of sorghum. Experimental Agriculture. https://www.example.org/article',
            $this->formatter->format($this->article(['authors' => 'Khidir, M. O.; Smith, J.; Jones, A.', 'doi' => null]), CitationStyle::Apa),
            'Three authors take a serial comma and an ampersand; with no DOI the link is used',
        );

        $this->assertSame(
            'Khidir, M. O. (1986). Agronomy of the savanna. In Agriculture in Semi-Arid Africa.',
            $this->formatter->format($this->article(['type' => PublicationType::Chapter, 'title' => 'Agronomy of the savanna', 'authors' => 'Khidir, M. O.', 'year' => 1986, 'venue_publisher' => 'In: Agriculture in Semi-Arid Africa', 'doi' => null, 'url' => null]), CitationStyle::Apa),
            'A chapter is "In" its book, without the "In:" the editor typed',
        );
    }

    #[Test]
    public function mla_quotes_a_contained_title_and_italicises_the_container(): void
    {
        $this->assertSame(
            'Khidir, Mohamed Osman, and John Smith. “Water use efficiency of sorghum.” Experimental Agriculture, 1971, https://doi.org/10.1000/abc.',
            $this->formatter->format($this->article(), CitationStyle::Mla),
        );
        $this->assertSame(
            'Khidir, Mohamed Osman, and John Smith. “Water use efficiency of sorghum.” <i>Experimental Agriculture</i>, 1971, https://doi.org/10.1000/abc.',
            $this->formatter->html($this->article(), CitationStyle::Mla),
        );
    }

    #[Test]
    public function mla_uses_et_al_for_three_or_more_authors_and_sets_a_book_with_its_publisher(): void
    {
        $this->assertSame(
            'Khidir, M. O., et al. “Water use efficiency of sorghum.” Experimental Agriculture, 1971, https://doi.org/10.1000/abc.',
            $this->formatter->format($this->article(['authors' => 'Khidir, M. O.; Smith, J.; Jones, A.']), CitationStyle::Mla),
        );
        $this->assertSame(
            'Khidir, M. O. <i>Principles of Dryland Agronomy</i>. Khartoum University Press, 1978.',
            $this->formatter->html($this->book(), CitationStyle::Mla),
        );
    }

    #[Test]
    public function chicago_puts_the_year_after_the_journal_and_lists_every_author(): void
    {
        $this->assertSame(
            'Khidir, Mohamed Osman, and John Smith. “Water use efficiency of sorghum.” <i>Experimental Agriculture</i> (1971). https://doi.org/10.1000/abc.',
            $this->formatter->html($this->article(), CitationStyle::Chicago),
        );
        $this->assertSame(
            'Khidir, M. O., J. Smith, and A. Jones. “Water use efficiency of sorghum.” Experimental Agriculture (1971). https://doi.org/10.1000/abc.',
            $this->formatter->format($this->article(['authors' => 'Khidir, M. O.; Smith, J.; Jones, A.']), CitationStyle::Chicago),
        );
    }

    #[Test]
    public function chicago_sets_a_book_with_publisher_and_year_and_shortens_long_author_lists(): void
    {
        $this->assertSame(
            'Khidir, M. O. <i>Principles of Dryland Agronomy</i>. Khartoum University Press, 1978.',
            $this->formatter->html($this->book(), CitationStyle::Chicago),
        );

        $eleven = implode('; ', array_map(fn (int $n): string => 'Author'.$n.', A.', range(1, 11)));
        $this->assertStringStartsWith(
            'Author1, A., A. Author2, A. Author3, A. Author4, A. Author5, A. Author6, A. Author7, et al. “',
            $this->formatter->format($this->article(['authors' => $eleven]), CitationStyle::Chicago),
        );
    }

    #[Test]
    public function bibtex_writes_an_entry_of_the_matching_type_with_a_readable_key(): void
    {
        $this->assertSame(
            "@article{khidir1971water,\n  author = {Khidir, Mohamed Osman and Smith, John},\n  title = {Water use efficiency of sorghum},\n  journal = {Experimental Agriculture},\n  year = {1971},\n  doi = {10.1000/abc}\n}",
            $this->formatter->format($this->article(), CitationStyle::Bibtex),
        );

        $this->assertSame(
            "@book{khidir1978principles,\n  author = {Khidir, M. O.},\n  title = {Principles of Dryland Agronomy},\n  publisher = {Khartoum University Press},\n  year = {1978}\n}",
            $this->formatter->format($this->book(), CitationStyle::Bibtex),
        );
    }

    #[Test]
    public function bibtex_escapes_special_characters_and_falls_back_to_the_url(): void
    {
        $report = $this->book([
            'type' => PublicationType::Report, 'title' => 'Fertility & yield: 50% of plots', 'authors' => null, 'year' => null,
            'venue_publisher' => 'FAO', 'url' => 'https://www.example.org/report_1',
        ]);

        $this->assertSame(
            "@techreport{anonfertility,\n  title = {Fertility \\& yield: 50\\% of plots},\n  institution = {FAO},\n  url = {https://www.example.org/report_1}\n}",
            $this->formatter->format($report, CitationStyle::Bibtex),
        );

        $this->assertSame('@inproceedings{', substr($this->formatter->format($this->article(['type' => PublicationType::ConferencePaper]), CitationStyle::Bibtex), 0, 15));
        $this->assertSame('@incollection{', substr($this->formatter->format($this->article(['type' => PublicationType::Chapter]), CitationStyle::Bibtex), 0, 14));
        $this->assertSame('@misc{', substr($this->formatter->format($this->article(['type' => PublicationType::Other]), CitationStyle::Bibtex), 0, 6));
        $this->assertSame('khidir1971water', $this->formatter->bibtexKey($this->article(['title' => 'The water of sorghum'])), 'Leading stop words are skipped');
    }

    #[Test]
    public function bibtex_html_is_escaped_text_and_all_returns_every_style_in_order(): void
    {
        $tricky = $this->book(['title' => '<script>Title</script>']);

        $this->assertStringNotContainsString('<script>', $this->formatter->html($tricky, CitationStyle::Bibtex));
        $this->assertStringNotContainsString('<script>', $this->formatter->html($tricky, CitationStyle::Apa));

        $all = $this->formatter->all($this->article());

        $this->assertSame(CitationStyle::cases(), array_column($all, 'style'));
        $this->assertSame(['APA', 'MLA', 'Chicago', 'BibTeX'], array_column($all, 'label'));
        $this->assertSame([false, false, false, true], array_column($all, 'code'));
        $this->assertSame($this->formatter->format($this->article(), CitationStyle::Mla), $all[1]['text']);
    }

    #[Test]
    public function a_title_ending_in_a_question_mark_keeps_it_instead_of_a_full_stop(): void
    {
        $question = $this->article(['title' => 'Is sorghum drought tolerant?']);

        $this->assertStringContainsString('“Is sorghum drought tolerant?”', $this->formatter->format($question, CitationStyle::Mla));
        $this->assertStringContainsString('Is sorghum drought tolerant? Experimental', $this->formatter->format($question, CitationStyle::Apa));
        $this->assertStringContainsString('<i>Is it?</i> Khartoum', $this->formatter->html($this->book(['title' => 'Is it?']), CitationStyle::Apa));
    }
}
