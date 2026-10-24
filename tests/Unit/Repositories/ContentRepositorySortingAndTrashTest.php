<?php

namespace Tests\Unit\Repositories;

use App\Models\Award;
use App\Models\Profile;
use App\Repositories\Contracts\AwardRepositoryInterface;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shared admin helpers of BaseContentRepository, through awards.
 */
class ContentRepositorySortingAndTrashTest extends TestCase
{
    use RefreshDatabase;

    private AwardRepositoryInterface $awards;

    protected function setUp(): void
    {
        parent::setUp();

        $this->awards = $this->app->make(AwardRepositoryInterface::class);
    }

    #[Test]
    public function search_sorts_by_sortable_columns_and_falls_back_to_display_order(): void
    {
        Award::factory()->sortOrder(2)->create(['name' => 'Beta', 'year' => 1990]);
        Award::factory()->sortOrder(1)->create(['name' => 'Alpha', 'year' => 2000]);
        Award::factory()->sortOrder(3)->create(['name' => 'Gamma', 'year' => 1980]);

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->awards->search()->pluck('name')->all());
        $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->awards->search(null, [], 20, 'year')->pluck('name')->all());
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->awards->search(null, [], 20, 'year', ContentRepositoryInterface::SORT_DESC)->pluck('name')->all());
        $this->assertSame(['Beta'], $this->awards->search('bet', [], 20, 'name')->pluck('name')->all());
        $this->assertSame(['Beta'], $this->awards->search(null, ['year' => 1990])->pluck('name')->all());
        $this->assertSame(['status', 'year'], $this->awards->filterColumns());
        $this->assertSame(['sort_order', 'status', 'published_at', 'updated_at', 'name', 'year', 'organisation'], $this->awards->sortableColumns());
    }

    #[Test]
    public function unknown_sort_columns_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->awards->search(null, [], 20, 'password');
    }

    #[Test]
    public function trashed_records_are_listed_restored_and_excluded_from_the_rest(): void
    {
        $kept = Award::factory()->sortOrder(1)->create(['name' => 'Kept']);
        $gone = Award::factory()->sortOrder(2)->create(['name' => 'Gone']);
        $gone->delete();

        $this->assertSame([$kept->getKey()], $this->awards->orderedIds());
        $this->assertSame([$gone->getKey()], $this->awards->trashed()->pluck('id')->all());
        $this->assertSame([$gone->getKey()], $this->awards->trashed('gon')->pluck('id')->all());
        $this->assertSame([], $this->awards->trashed('kept')->pluck('id')->all());
        $this->assertSame(1, $this->awards->countTrashed());
        $this->assertNull($this->awards->find($gone->getKey()));
        $this->assertNotNull($this->awards->findTrashed($gone->getKey()));
        $this->assertNull($this->awards->findTrashed($kept->getKey()));
        $this->assertSame(3, $this->awards->nextSortOrder());

        $restored = $this->awards->restore($gone->getKey());

        $this->assertNull($restored->deleted_at);
        $this->assertSame([$kept->getKey(), $gone->getKey()], $this->awards->orderedIds());
        $this->assertSame(0, $this->awards->countTrashed());
    }

    #[Test]
    public function distinct_lists_filterable_values_only(): void
    {
        Award::factory()->create(['year' => 1990]);
        Award::factory()->create(['year' => 2010]);
        Award::factory()->create(['year' => 1990]);
        Award::factory()->create(['year' => null]);

        $this->assertSame([2010, 1990], $this->awards->distinct('year'));

        $this->expectException(InvalidArgumentException::class);
        $this->awards->distinct('name');
    }

    #[Test]
    public function types_without_soft_deletes_have_no_trash(): void
    {
        $profiles = $this->app->make(ProfileRepositoryInterface::class);
        Profile::factory()->create(['sort_order' => 4]);

        $this->assertSame(0, $profiles->countTrashed());
        $this->assertSame(5, $profiles->nextSortOrder());

        $this->expectException(LogicException::class);
        $profiles->trashed();
    }

    #[Test]
    public function force_delete_removes_a_trashed_record_for_good_and_refuses_a_live_one(): void
    {
        $trashed = Award::factory()->create(['name' => 'Gone']);
        $live = Award::factory()->create(['name' => 'Kept']);
        $trashed->delete();

        $this->awards->forceDelete($trashed->getKey());

        $this->assertNull(Award::withTrashed()->find($trashed->getKey()));
        $this->assertSame(0, $this->awards->countTrashed());

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->awards->forceDelete($live->getKey());
    }
}
