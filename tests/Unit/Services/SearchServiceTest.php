<?php

namespace Tests\Unit\Services;

use App\Constants\CvSection;
use App\Constants\Sections;
use App\Models\Album;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\OtherItem;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Models\Video;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Committed records (DatabaseTruncation) because the fulltext index ignores
 * rows inside an open transaction; the tables are cleared again afterwards.
 */
class SearchServiceTest extends TestCase
{
    use DatabaseTruncation;

    private SearchService $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = $this->app->make(SearchService::class);
    }

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    #[Test]
    public function the_query_is_trimmed_single_spaced_and_cut_to_the_maximum(): void
    {
        $this->assertSame('soil science', $this->search->normalise("  soil \n\t science  "));
        $this->assertSame('', $this->search->normalise(null));
        $this->assertSame(100, mb_strlen($this->search->normalise(str_repeat('a', 150))));
    }

    #[Test]
    public function a_query_is_searchable_from_two_characters_with_at_least_one_word(): void
    {
        $this->assertFalse($this->search->isSearchable('a'));
        $this->assertFalse($this->search->isSearchable('!!'));
        $this->assertFalse($this->search->isSearchable(' '));
        $this->assertTrue($this->search->isSearchable('ai'));
    }

    #[Test]
    public function members_only_material_is_visible_to_approved_members_and_admins_only(): void
    {
        $this->assertFalse($this->search->canSeeMembersOnly(null));
        $this->assertFalse($this->search->canSeeMembersOnly(User::factory()->applicant()->create()));
        $this->assertFalse($this->search->canSeeMembersOnly(User::factory()->editor()->create()));
        $this->assertTrue($this->search->canSeeMembersOnly(User::factory()->member()->create()));
        $this->assertTrue($this->search->canSeeMembersOnly(User::factory()->admin()->create()));
    }

    #[Test]
    public function results_are_grouped_by_section_with_labels_links_and_highlighted_titles(): void
    {
        CvEntry::factory()->published()->section(CvSection::Appointment)->create(['title' => 'Professor of Agronomy', 'organisation' => 'University of Khartoum', 'start_year' => 1980, 'end_year' => null]);
        $publication = Publication::factory()->published()->create(['title' => 'Agronomy of the Gezira', 'year' => 1978, 'abstract' => 'A long study of agronomy in irrigated cotton.']);
        $award = Award::factory()->published()->create(['name' => 'Agronomy Society Medal', 'organisation' => 'Sudan Agronomy Society', 'year' => 1990]);
        Video::factory()->published()->create(['title' => 'Agronomy lecture', 'slug' => 'agronomy-lecture']);
        $album = Album::factory()->published()->create(['title' => 'Agronomy field days']);
        TeachingMaterial::factory()->published()->course('Tropical Agronomy')->create(['title' => 'Agronomy reading list']);
        TeachingMaterial::factory()->published()->membersOnly()->course('Tropical Agronomy')->create(['title' => 'Agronomy workbook']);
        Commentary::factory()->published()->create(['title' => 'Agronomy and the nation', 'slug' => 'agronomy-nation']);
        OtherItem::factory()->published()->create(['title' => 'Agronomy prize fund', 'slug' => 'agronomy-prize']);

        $results = $this->search->search('agronomy', User::factory()->member()->create());

        $this->assertSame('agronomy', $results['query']);
        $this->assertSame(['agronomy'], $results['tokens']);
        $this->assertSame(9, $results['total']);
        $this->assertSame(8, $results['sections']);
        $this->assertSame('9 results in 8 sections', $results['summary']);
        $this->assertSame(['CV & Achievements', 'Publications', 'Awards', 'Videos', 'Pictures', 'Teaching', 'Commentary', 'Other Information'], array_column($results['groups'], 'label'));

        $byKey = array_column($results['groups'], null, 'key');

        $this->assertSame(route('cv').'#cv-appointment', $byKey[Sections::CV]['items'][0]['url']);
        $this->assertSame(['Appointments', 'University of Khartoum', '1980–present'], $byKey[Sections::CV]['items'][0]['meta']);
        $this->assertSame(route('publications').'#publication-'.$publication->slug, $byKey[Sections::PUBLICATIONS]['items'][0]['url']);
        $this->assertSame('<mark class="search-mark">Agronomy</mark> of the Gezira', $byKey[Sections::PUBLICATIONS]['items'][0]['title']);
        $this->assertStringContainsString('<mark class="search-mark">agronomy</mark> in irrigated cotton', $byKey[Sections::PUBLICATIONS]['items'][0]['excerpt']);
        $this->assertSame(route('awards').'#award-'.$award->getKey(), $byKey[Sections::AWARDS]['items'][0]['url']);
        $this->assertSame(route('videos.show', 'agronomy-lecture'), $byKey[Sections::VIDEOS]['items'][0]['url']);
        $this->assertSame(route('pictures.show', $album), $byKey[Sections::PICTURES]['items'][0]['url']);
        $this->assertSame(route('teaching').'#course-tropical-agronomy', $byKey[Sections::TEACHING]['items'][0]['url']);
        $this->assertSame(2, $byKey[Sections::TEACHING]['total']);
        $this->assertEqualsCanonicalizing([false, true], array_column($byKey[Sections::TEACHING]['items'], 'members'));
        $this->assertSame(route('commentary.show', 'agronomy-nation'), $byKey[Sections::COMMENTARY]['items'][0]['url']);
        $this->assertSame(route('other.show', 'agronomy-prize'), $byKey[Sections::OTHER]['items'][0]['url']);
        $this->assertSame(route('publications'), $byKey[Sections::PUBLICATIONS]['url']);
    }

    #[Test]
    public function guests_do_not_see_members_only_materials(): void
    {
        TeachingMaterial::factory()->published()->create(['title' => 'Public workbook']);
        TeachingMaterial::factory()->published()->membersOnly()->create(['title' => 'Members workbook']);

        $results = $this->search->search('workbook');

        $this->assertSame(1, $results['total']);
        $this->assertSame('Public <mark class="search-mark">workbook</mark>', $results['groups'][0]['items'][0]['title']);
    }

    #[Test]
    public function the_per_section_limit_leaves_a_count_of_the_rest(): void
    {
        Award::factory()->published()->count(6)->sequence(fn ($sequence) => ['name' => 'Medal '.$sequence->index, 'description' => 'For irrigation research'])->create();

        $results = $this->search->search('irrigation', null, 4);

        $this->assertSame(6, $results['total']);
        $this->assertSame(6, $results['groups'][0]['total']);
        $this->assertSame(2, $results['groups'][0]['more']);
        $this->assertCount(4, $results['groups'][0]['items']);
    }

    #[Test]
    public function an_unsearchable_query_returns_nothing_without_touching_the_database(): void
    {
        $results = $this->search->search('a');

        $this->assertSame(['query' => 'a', 'tokens' => ['a'], 'total' => 0, 'sections' => 0, 'summary' => 'No results', 'groups' => []], $results);
    }
}
