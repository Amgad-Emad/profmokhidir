<?php

namespace Tests\Unit\Repositories;

use App\Constants\PublicationType;
use App\Models\Publication;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Repositories\Eloquent\EloquentPublicationRepository;
use App\Support\PublicationFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPublicationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PublicationRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(PublicationRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentPublicationRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_by_slug_and_checks_slug_existence(): void
    {
        $draft = Publication::factory()->draft()->create(['slug' => 'dryland-agronomy']);
        $published = Publication::factory()->published()->create(['slug' => 'soil-fertility']);

        $this->assertTrue($draft->is($this->repository->findBySlug('dryland-agronomy')));
        $this->assertNull($this->repository->findPublishedBySlug('dryland-agronomy'));
        $this->assertTrue($published->is($this->repository->findPublishedBySlug('soil-fertility')));
        $this->assertNull($this->repository->findBySlug('missing'));

        $this->assertTrue($this->repository->slugExists('soil-fertility'));
        $this->assertFalse($this->repository->slugExists('soil-fertility', $published->id));
        $this->assertTrue($this->repository->slugExists('soil-fertility', $draft->id));
        $this->assertFalse($this->repository->slugExists('nope'));
    }

    #[Test]
    public function it_paginates_published_publications_filtered_by_type_year_range_and_topic(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1990)->topic('Agronomy')->count(2)->create();
        Publication::factory()->published()->type(PublicationType::JournalArticle)->year(1990)->topic('Soil')->create();
        Publication::factory()->published()->type(PublicationType::Book)->year(2001)->topic('Soil')->create();
        Publication::factory()->draft()->type(PublicationType::Book)->year(1990)->topic('Agronomy')->create();

        $this->assertSame(4, $this->repository->paginatePublishedFiltered(new PublicationFilters)->total());
        $this->assertSame(3, $this->repository->paginatePublishedFiltered(new PublicationFilters(PublicationType::Book))->total());
        $this->assertSame(3, $this->repository->paginatePublishedFiltered(new PublicationFilters(null, 1990, 1990))->total());
        $this->assertSame(2, $this->repository->paginatePublishedFiltered(new PublicationFilters(PublicationType::Book, 1990, 1990))->total());
        $this->assertSame(1, $this->repository->paginatePublishedFiltered(new PublicationFilters(null, 1991))->total());
        $this->assertSame(3, $this->repository->paginatePublishedFiltered(new PublicationFilters(null, null, 2000))->total());
        $this->assertSame(2, $this->repository->paginatePublishedFiltered(new PublicationFilters(topic: 'Soil'))->total());
        $this->assertSame(1, $this->repository->paginatePublishedFiltered(new PublicationFilters(PublicationType::Book, topic: 'Soil'))->total());
        $this->assertTrue($this->repository->paginatePublishedFiltered(new PublicationFilters)->first()->relationLoaded('media'));
    }

    #[Test]
    public function the_catalogue_is_ordered_newest_year_first_with_undated_records_last(): void
    {
        Publication::factory()->published()->create(['title' => 'Undated', 'year' => null, 'sort_order' => 1]);
        Publication::factory()->published()->create(['title' => 'Old', 'year' => 1971, 'sort_order' => 2]);
        Publication::factory()->published()->create(['title' => 'New B', 'year' => 2001, 'sort_order' => 5]);
        Publication::factory()->published()->create(['title' => 'New A', 'year' => 2001, 'sort_order' => 3]);

        $this->assertSame(['New A', 'New B', 'Old', 'Undated'], $this->repository->paginatePublishedFiltered(new PublicationFilters)->pluck('title')->all());
    }

    #[Test]
    public function it_counts_matching_records_per_decade_newest_first_with_undated_under_an_empty_key(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1971)->create();
        Publication::factory()->published()->type(PublicationType::Book)->year(1979)->create();
        Publication::factory()->published()->type(PublicationType::Report)->year(2003)->create();
        Publication::factory()->published()->type(PublicationType::Book)->create(['year' => null]);
        Publication::factory()->draft()->type(PublicationType::Book)->year(1975)->create();

        $this->assertSame([2000 => 1, 1970 => 2, '' => 1], $this->repository->publishedDecadeCounts(new PublicationFilters));
        $this->assertSame([1970 => 2, '' => 1], $this->repository->publishedDecadeCounts(new PublicationFilters(PublicationType::Book)));
        $this->assertSame([], $this->repository->publishedDecadeCounts(new PublicationFilters(PublicationType::Chapter)));
    }

    #[Test]
    public function it_lists_featured_publications_in_display_order_and_distinct_topics(): void
    {
        Publication::factory()->published()->featured()->topic('Soil')->create(['title' => 'Second', 'sort_order' => 2]);
        Publication::factory()->published()->featured()->topic('Agronomy')->create(['title' => 'First', 'sort_order' => 1]);
        Publication::factory()->published()->featured()->topic(null)->create(['title' => 'Third', 'sort_order' => 3]);
        Publication::factory()->published()->topic('')->create(['title' => 'Not featured']);
        Publication::factory()->draft()->featured()->topic('Draft topic')->create(['title' => 'Draft']);

        $this->assertSame(['First', 'Second', 'Third'], $this->repository->publishedFeatured(5)->pluck('title')->all());
        $this->assertSame(['First', 'Second'], $this->repository->publishedFeatured(2)->pluck('title')->all());
        $this->assertSame(['Agronomy', 'Soil'], $this->repository->publishedTopics());
    }

    #[Test]
    public function latest_published_returns_the_newest_years_first(): void
    {
        $old = Publication::factory()->published()->year(1975)->create();
        $new = Publication::factory()->published()->year(2015)->create();
        $mid = Publication::factory()->published()->year(1999)->create();
        Publication::factory()->draft()->year(2024)->create();

        $this->assertSame([$new->id, $mid->id], $this->repository->latestPublished(2)->pluck('id')->all());
        $this->assertSame([$new->id, $mid->id, $old->id], $this->repository->latestPublished()->pluck('id')->all());
    }

    #[Test]
    public function it_lists_distinct_published_years_and_types(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1990)->count(2)->create();
        Publication::factory()->published()->type(PublicationType::Report)->year(2005)->create();
        Publication::factory()->draft()->type(PublicationType::Chapter)->year(2020)->create();

        $this->assertSame([2005, 1990], $this->repository->publishedYears());
        $this->assertSame([PublicationType::Book, PublicationType::Report], $this->repository->publishedTypes());
    }

    #[Test]
    public function search_matches_authors_and_filters_by_type(): void
    {
        Publication::factory()->type(PublicationType::Book)->create(['title' => 'Alpha', 'authors' => 'Khidir, M. O.']);
        Publication::factory()->type(PublicationType::Report)->create(['title' => 'Beta', 'authors' => 'Khidir, M. O.; Ali, A.']);

        $this->assertSame(2, $this->repository->search('Khidir')->total());
        $this->assertSame(1, $this->repository->search('Khidir', ['type' => PublicationType::Report])->total());
        $this->assertSame(1, $this->repository->search(null, ['type' => 'book'])->total());
    }
}
