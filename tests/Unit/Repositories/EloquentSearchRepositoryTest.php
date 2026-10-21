<?php

namespace Tests\Unit\Repositories;

use App\Constants\Sections;
use App\Models\Album;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\OtherItem;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Video;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Repositories\Eloquent\EloquentSearchRepository;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * InnoDB fulltext indexes only see committed rows, so these tests commit their
 * records (DatabaseTruncation) instead of wrapping them in a transaction, and
 * clear the tables again afterwards.
 */
class EloquentSearchRepositoryTest extends TestCase
{
    use DatabaseTruncation;

    private SearchRepositoryInterface $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = $this->app->make(SearchRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    #[Test]
    public function it_is_the_bound_implementation(): void
    {
        $this->assertInstanceOf(EloquentSearchRepository::class, $this->search);
    }

    #[Test]
    public function tokens_are_lower_case_words_without_punctuation_or_duplicates(): void
    {
        $this->assertSame(['soil', 'science', 'the', "nile's", 'gezira'], $this->search->tokens("Soil—science, 'the' Nile's (Gezira) soil"));
        $this->assertSame([], $this->search->tokens('!!! ---'));
    }

    #[Test]
    public function it_finds_published_records_in_every_searched_section_by_word_prefix(): void
    {
        CvEntry::factory()->published()->create(['title' => 'Lecturer in Agronomy']);
        Publication::factory()->published()->create(['title' => 'Lectures on soil fertility']);
        Award::factory()->published()->create(['name' => 'Distinguished Lecture Medal']);
        Video::factory()->published()->create(['title' => 'Inaugural lecture']);
        Album::factory()->published()->create(['title' => 'The lecture hall, 1975']);
        TeachingMaterial::factory()->published()->create(['title' => 'Lecture 1: Soils']);
        Commentary::factory()->published()->create(['title' => 'Why lectures matter']);
        OtherItem::factory()->published()->create(['title' => 'Lecture timetable']);

        Publication::factory()->draft()->create(['title' => 'Lecture notes (draft)']);
        Commentary::factory()->published()->create(['title' => 'Unrelated essay', 'summary' => 'Nothing about teaching here.']);

        $results = $this->search->search('lect', false, 10);

        $this->assertSame([
            Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS, Sections::VIDEOS,
            Sections::PICTURES, Sections::TEACHING, Sections::COMMENTARY, Sections::OTHER,
        ], array_keys($results), 'Grouped by section in navigation order');

        foreach ($results as $section => $result) {
            $this->assertSame(1, $result['total'], $section);
            $this->assertCount(1, $result['items'], $section);
        }

        $this->assertSame('Lectures on soil fertility', $results[Sections::PUBLICATIONS]['items']->first()->title);
    }

    #[Test]
    public function every_word_must_match_and_short_words_fall_back_to_like(): void
    {
        // Every searched column is pinned: the factory's random text could otherwise contain "ai".
        $fixed = ['authors' => 'Khidir, M. O.', 'venue_publisher' => 'Khartoum University Press'];
        Publication::factory()->published()->create([...$fixed, 'title' => 'AI in agriculture', 'abstract' => 'Machine learning for soil maps.']);
        Publication::factory()->published()->create([...$fixed, 'title' => 'Soil survey of Kordofan', 'abstract' => 'Field notes from the west.']);
        Publication::factory()->published()->create([...$fixed, 'title' => 'Irrigation economics', 'abstract' => 'Costs of the Gezira canals.']);

        $this->assertSame(['AI in agriculture'], $this->search->search('ai', false, 10)[Sections::PUBLICATIONS]['items']->pluck('title')->all());
        $this->assertSame(['AI in agriculture'], $this->search->search('soil ai', false, 10)[Sections::PUBLICATIONS]['items']->pluck('title')->all());
        $this->assertSame(['AI in agriculture', 'Soil survey of Kordofan'], $this->search->search('soil', false, 10)[Sections::PUBLICATIONS]['items']->sortBy('title')->pluck('title')->values()->all());
        $this->assertArrayNotHasKey(Sections::PUBLICATIONS, $this->search->search('soil irrigation', false, 10));
        $this->assertSame([], $this->search->search('***', false, 10));
    }

    #[Test]
    public function stopwords_still_find_their_matches_through_like(): void
    {
        Commentary::factory()->published()->create(['title' => 'About the field']);
        Commentary::factory()->published()->create(['title' => 'Harvest notes']);

        $this->assertSame(['About the field'], $this->search->search('about', false, 10)[Sections::COMMENTARY]['items']->pluck('title')->all());
        $this->assertSame(['About the field'], $this->search->search('the field', false, 10)[Sections::COMMENTARY]['items']->pluck('title')->all());
        $this->assertArrayNotHasKey(Sections::COMMENTARY, $this->search->search('the harvest field', false, 10));
    }

    #[Test]
    public function totals_count_every_match_while_items_are_limited_per_section(): void
    {
        Award::factory()->published()->count(5)->sequence(fn ($sequence) => ['name' => 'Honour '.$sequence->index, 'description' => 'For service to agronomy'])->create();

        $result = $this->search->search('agronomy', false, 2)[Sections::AWARDS];

        $this->assertSame(5, $result['total']);
        $this->assertCount(2, $result['items']);
    }

    #[Test]
    public function members_only_teaching_materials_are_included_only_on_request(): void
    {
        TeachingMaterial::factory()->published()->create(['title' => 'Public irrigation notes']);
        TeachingMaterial::factory()->published()->membersOnly()->create(['title' => 'Members irrigation workbook']);
        TeachingMaterial::factory()->draft()->membersOnly()->create(['title' => 'Draft irrigation workbook']);

        $this->assertSame(['Public irrigation notes'], $this->search->search('irrigation', false, 10)[Sections::TEACHING]['items']->pluck('title')->all());
        $this->assertEqualsCanonicalizing(
            ['Public irrigation notes', 'Members irrigation workbook'],
            $this->search->search('irrigation', true, 10)[Sections::TEACHING]['items']->pluck('title')->all(),
        );
    }

    #[Test]
    public function boolean_operators_and_quotes_in_the_query_are_harmless(): void
    {
        Commentary::factory()->published()->create(['title' => 'On teaching']);

        $this->assertSame(1, $this->search->search('"teach" +- (on)', false, 10)[Sections::COMMENTARY]['total']);
        $this->assertSame(1, $this->search->search("teach'", false, 10)[Sections::COMMENTARY]['total']);
    }
}
