<?php

namespace Tests\Unit\Repositories;

use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Models\Tribute;
use App\Models\User;
use App\Repositories\Contracts\TributeRepositoryInterface;
use App\Repositories\Eloquent\EloquentTributeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentTributeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TributeRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(TributeRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentTributeRepository::class, $this->repository);
    }

    #[Test]
    public function it_filters_paginates_and_counts_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Tribute::factory()->approved($admin)->count(3)->create();
        Tribute::factory()->pending()->count(2)->create();
        Tribute::factory()->rejected()->create();

        $this->assertCount(3, $this->repository->withStatus(TributeStatus::Approved));
        $this->assertCount(2, $this->repository->withStatus(TributeStatus::Pending));
        $this->assertSame(1, $this->repository->countByStatus(TributeStatus::Rejected));
        $this->assertSame(2, $this->repository->paginateByStatus(TributeStatus::Pending)->total());
        $this->assertSame(6, $this->repository->paginateByStatus()->total());
        $this->assertSame($admin->id, $this->repository->withStatus(TributeStatus::Approved)->first()->approved_by);
    }

    #[Test]
    public function published_returns_only_approved_tributes_in_display_order(): void
    {
        $secondOrder = Tribute::factory()->approved()->create(['sort_order' => 2, 'approved_at' => now()->subDay()]);
        $firstOld = Tribute::factory()->approved()->create(['sort_order' => 1, 'approved_at' => now()->subDays(10)]);
        $firstNew = Tribute::factory()->approved()->create(['sort_order' => 1, 'approved_at' => now()->subDays(2)]);
        Tribute::factory()->pending()->create(['sort_order' => 0]);
        Tribute::factory()->rejected()->create(['sort_order' => 0]);

        $this->assertSame(
            [$firstNew->id, $firstOld->id, $secondOrder->id],
            $this->repository->published()->pluck('id')->all(),
        );
        $this->assertSame(3, $this->repository->countPublished());
    }

    #[Test]
    public function latest_approved_orders_by_approval_time(): void
    {
        $old = Tribute::factory()->approved()->create(['approved_at' => now()->subDays(30)]);
        $new = Tribute::factory()->approved()->create(['approved_at' => now()->subDay()]);
        $mid = Tribute::factory()->approved()->create(['approved_at' => now()->subDays(7)]);
        Tribute::factory()->pending()->create();

        $this->assertSame([$new->id, $mid->id], $this->repository->latestApproved(2)->pluck('id')->all());
        $this->assertSame([$new->id, $mid->id, $old->id], $this->repository->latestApproved()->pluck('id')->all());
    }

    #[Test]
    public function it_counts_recent_submissions_from_a_hashed_ip(): void
    {
        Tribute::factory()->count(2)->create(['ip_hash' => 'abc']);
        Tribute::factory()->create(['ip_hash' => 'abc', 'created_at' => now()->subHours(3)]);
        Tribute::factory()->create(['ip_hash' => 'other']);

        $this->assertSame(2, $this->repository->countRecentFromIp('abc', now()->subHour()));
        $this->assertSame(3, $this->repository->countRecentFromIp('abc', now()->subDay()));
        $this->assertSame(0, $this->repository->countRecentFromIp('nobody', now()->subDay()));
    }

    #[Test]
    public function email_and_ip_hash_are_never_serialised(): void
    {
        $tribute = Tribute::factory()->approved()->create(['email' => 'private@example.com', 'ip_hash' => 'hash']);

        $array = $this->repository->findPublished($tribute->id)->toArray();

        $this->assertArrayNotHasKey('email', $array);
        $this->assertArrayNotHasKey('ip_hash', $array);
        $this->assertArrayHasKey('message', $array);
    }

    #[Test]
    public function search_filters_by_relationship(): void
    {
        Tribute::factory()->create(['name' => 'Ahmed', 'relationship' => 'student']);
        Tribute::factory()->create(['name' => 'Ahmed', 'relationship' => 'colleague']);

        $this->assertSame(2, $this->repository->search('Ahmed')->total());
        $this->assertSame(1, $this->repository->search('Ahmed', ['relationship' => 'student'])->total());
    }

    #[Test]
    public function paginate_approved_filters_by_relationship_in_display_order_and_can_leave_one_out(): void
    {
        $student = Tribute::factory()->approved()->relationship(TributeRelationship::Student)->create(['sort_order' => 2]);
        $colleague = Tribute::factory()->approved()->relationship(TributeRelationship::Colleague)->create(['sort_order' => 1]);
        $family = Tribute::factory()->approved()->relationship(TributeRelationship::Family)->create(['sort_order' => 3]);
        Tribute::factory()->pending()->relationship(TributeRelationship::Student)->create();

        $this->assertSame([$colleague->id, $student->id, $family->id], $this->repository->paginateApproved(null, 10)->pluck('id')->all());
        $this->assertSame([$student->id], $this->repository->paginateApproved(TributeRelationship::Student, 10)->pluck('id')->all());
        $this->assertSame([$colleague->id, $family->id], $this->repository->paginateApproved(null, 10, $student->id)->pluck('id')->all());
        $this->assertSame(1, $this->repository->paginateApproved(null, 1)->lastPage() === 3 ? 1 : 0);
        $this->assertSame(0, $this->repository->paginateApproved(TributeRelationship::Friend, 10)->total());
    }

    #[Test]
    public function featured_returns_the_latest_featured_approved_tribute_and_can_be_cleared(): void
    {
        $this->assertNull($this->repository->featured());

        $older = Tribute::factory()->featured()->create(['featured_at' => now()->subDay()]);
        $newer = Tribute::factory()->featured()->create(['featured_at' => now()]);
        $pendingButFlagged = Tribute::factory()->pending()->create(['is_featured' => true, 'featured_at' => now()->addMinute()]);

        $this->assertTrue($this->repository->featured()->is($newer));

        $this->assertSame(2, $this->repository->clearFeatured($newer->id));
        $this->assertTrue($newer->fresh()->is_featured);
        $this->assertFalse($older->fresh()->is_featured);
        $this->assertNull($older->fresh()->featured_at);
        $this->assertFalse($pendingButFlagged->fresh()->is_featured);

        $this->assertSame(1, $this->repository->clearFeatured());
        $this->assertNull($this->repository->featured());
    }

    #[Test]
    public function approved_without_share_card_lists_only_approved_tributes_lacking_a_card(): void
    {
        $missing = Tribute::factory()->approved()->create(['share_card_path' => null, 'sort_order' => 2]);
        $missingFirst = Tribute::factory()->approved()->create(['share_card_path' => null, 'sort_order' => 1]);
        Tribute::factory()->approved()->create(['share_card_path' => 'tributes/share/tribute-1.png']);
        Tribute::factory()->pending()->create(['share_card_path' => null]);

        $this->assertSame([$missingFirst->id, $missing->id], $this->repository->approvedWithoutShareCard()->pluck('id')->all());
    }
}
