<?php

namespace Tests\Unit\Constants;

use App\Constants\Search;
use App\Constants\Sections;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchTest extends TestCase
{
    #[Test]
    public function the_searched_sections_are_real_sections_in_navigation_order(): void
    {
        $order = Sections::all();
        $positions = array_map(fn (string $key): int => array_search($key, $order, true), Search::sections());

        $this->assertNotContains(false, $positions, 'Every searched section exists');
        $this->assertSame($positions, array_values(array_filter($positions, 'is_int')));
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Searched sections follow the navigation order');

        $this->assertSame([
            Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS, Sections::VIDEOS,
            Sections::PICTURES, Sections::TEACHING, Sections::COMMENTARY, Sections::OTHER,
        ], Search::sections());
        $this->assertTrue(Search::isSearched(Sections::PUBLICATIONS));
        $this->assertFalse(Search::isSearched(Sections::TRIBUTES), 'Tributes and the community are not searched');
        $this->assertFalse(Search::isSearched(Sections::COMMUNITY));
    }

    #[Test]
    public function the_route_limits_and_shortcuts_are_fixed(): void
    {
        $this->assertSame('search', Search::ROUTE);
        $this->assertSame('/search', Search::PATH);
        $this->assertSame('q', Search::PARAM);
        $this->assertSame(2, Search::MIN_LENGTH);
        $this->assertSame(100, Search::MAX_LENGTH);
        $this->assertSame(3, Search::FULLTEXT_MIN_TOKEN);
        $this->assertLessThan(Search::PAGE_PER_SECTION, Search::DIALOG_PER_SECTION);
        $this->assertSame('/ Meta+K Control+K', Search::keyShortcuts());
    }

    #[Test]
    public function short_words_and_stopwords_are_not_looked_up_in_the_index(): void
    {
        $this->assertTrue(Search::isIndexed('soil'));
        $this->assertFalse(Search::isIndexed('ai'), 'Below innodb_ft_min_token_size');
        $this->assertFalse(Search::isIndexed('about'), 'On the InnoDB stopword list');
        $this->assertFalse(Search::isIndexed('the'));
        $this->assertContains('with', Search::stopwords());
    }

    #[Test]
    public function the_summary_line_counts_results_and_sections(): void
    {
        $this->assertSame('No results', Search::summary(0, 0));
        $this->assertSame('1 result in 1 section', Search::summary(1, 1));
        $this->assertSame('12 results in 3 sections', Search::summary(12, 3));
    }
}
