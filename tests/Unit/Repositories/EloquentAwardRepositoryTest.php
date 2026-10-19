<?php

namespace Tests\Unit\Repositories;

use App\Constants\ContentStatus;
use App\Models\Award;
use App\Repositories\Contracts\AwardRepositoryInterface;
use App\Repositories\Eloquent\EloquentAwardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Also covers the shared BaseContentRepository behaviour.
 */
class EloquentAwardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AwardRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(AwardRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentAwardRepository::class, $this->repository);
    }

    #[Test]
    public function search_matches_a_term_across_search_columns(): void
    {
        Award::factory()->create(['name' => 'Order of Merit', 'organisation' => 'Republic', 'description' => 'x']);
        Award::factory()->create(['name' => 'Fellowship', 'organisation' => 'Academy of Merit', 'description' => 'y']);
        Award::factory()->create(['name' => 'Prize', 'organisation' => 'Society', 'description' => 'For merit in teaching']);
        Award::factory()->create(['name' => 'Other', 'organisation' => 'Other', 'description' => 'z']);

        $this->assertSame(3, $this->repository->search('merit')->total());
        $this->assertSame(4, $this->repository->search('')->total());
        $this->assertSame(4, $this->repository->search('   ')->total());
    }

    #[Test]
    public function search_filters_by_status_and_ignores_unknown_filter_columns(): void
    {
        Award::factory()->published()->count(2)->create();
        Award::factory()->draft()->create();

        $this->assertSame(2, $this->repository->search(null, ['status' => ContentStatus::Published])->total());
        $this->assertSame(1, $this->repository->search(null, ['status' => 'draft'])->total());
        $this->assertSame(3, $this->repository->search(null, ['status' => ''])->total());
        $this->assertSame(3, $this->repository->search(null, ['status' => null])->total());
        $this->assertSame(3, $this->repository->search(null, ['not_a_column' => 'anything'])->total());
    }

    #[Test]
    public function search_escapes_like_wildcards(): void
    {
        Award::factory()->create(['name' => '100% Attendance', 'organisation' => 'a', 'description' => 'a']);
        Award::factory()->create(['name' => 'snake_case award', 'organisation' => 'b', 'description' => 'b']);
        Award::factory()->create(['name' => 'Plain name', 'organisation' => 'c', 'description' => 'c']);

        $this->assertSame(1, $this->repository->search('%')->total());
        $this->assertSame(1, $this->repository->search('_')->total());
        $this->assertSame(1, $this->repository->search('100% Att')->total());
    }

    #[Test]
    public function reorder_writes_sequential_sort_orders(): void
    {
        $a = Award::factory()->sortOrder(5)->create();
        $b = Award::factory()->sortOrder(6)->create();
        $c = Award::factory()->sortOrder(7)->create();

        $this->repository->reorder([$c->id, $a->id, $b->id]);

        $this->assertSame(1, $c->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(3, $b->fresh()->sort_order);
    }

    #[Test]
    public function next_sort_order_follows_the_highest_existing(): void
    {
        $this->assertSame(1, $this->repository->nextSortOrder());

        Award::factory()->sortOrder(4)->create();
        Award::factory()->sortOrder(9)->create();

        $this->assertSame(10, $this->repository->nextSortOrder());
    }

    #[Test]
    public function count_published_excludes_drafts(): void
    {
        Award::factory()->published()->count(3)->create();
        Award::factory()->draft()->count(2)->create();

        $this->assertSame(3, $this->repository->countPublished());
    }

    #[Test]
    public function published_excludes_drafts_and_orders_by_sort_order_year_desc_then_id_desc(): void
    {
        $second = Award::factory()->published()->sortOrder(2)->create(['year' => 2020]);
        $firstOld = Award::factory()->published()->sortOrder(1)->create(['year' => 1990]);
        $firstNewA = Award::factory()->published()->sortOrder(1)->create(['year' => 2000]);
        $firstNewB = Award::factory()->published()->sortOrder(1)->create(['year' => 2000]);
        Award::factory()->draft()->sortOrder(0)->create(['year' => 2024]);

        $this->assertSame(
            [$firstNewB->id, $firstNewA->id, $firstOld->id, $second->id],
            $this->repository->published()->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_paginates_and_finds_published_records(): void
    {
        $published = Award::factory()->published()->count(3)->create();
        $draft = Award::factory()->draft()->create();

        $this->assertSame(3, $this->repository->paginatePublished(2)->total());
        $this->assertCount(2, $this->repository->paginatePublished(2)->items());
        $this->assertTrue($published->first()->is($this->repository->findPublished($published->first()->id)));
        $this->assertNull($this->repository->findPublished($draft->id));
    }

    #[Test]
    public function base_crud_helpers_work(): void
    {
        $award = $this->repository->create(['name' => 'Medal', 'year' => 1999]);

        $this->assertTrue($award->is($this->repository->findOrFail($award->id)));
        $this->assertSame('Great Medal', $this->repository->update($award->id, ['name' => 'Great Medal'])->name);
        $this->assertCount(1, $this->repository->all());
        $this->assertSame(1, $this->repository->paginate()->total());
        $this->assertTrue($this->repository->delete($award->id));
        $this->assertNull($this->repository->find($award->id));
    }
}
