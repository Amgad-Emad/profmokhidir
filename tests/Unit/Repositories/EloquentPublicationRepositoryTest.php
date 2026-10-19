<?php

namespace Tests\Unit\Repositories;

use App\Constants\PublicationType;
use App\Models\Publication;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Repositories\Eloquent\EloquentPublicationRepository;
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
    public function it_paginates_published_publications_filtered_by_type_and_year(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1990)->count(2)->create();
        Publication::factory()->published()->type(PublicationType::JournalArticle)->year(1990)->create();
        Publication::factory()->published()->type(PublicationType::Book)->year(2001)->create();
        Publication::factory()->draft()->type(PublicationType::Book)->year(1990)->create();

        $this->assertSame(4, $this->repository->paginatePublishedFiltered()->total());
        $this->assertSame(3, $this->repository->paginatePublishedFiltered(PublicationType::Book)->total());
        $this->assertSame(3, $this->repository->paginatePublishedFiltered(null, 1990)->total());
        $this->assertSame(2, $this->repository->paginatePublishedFiltered(PublicationType::Book, 1990)->total());
        $this->assertTrue($this->repository->paginatePublishedFiltered()->first()->relationLoaded('media'));
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
