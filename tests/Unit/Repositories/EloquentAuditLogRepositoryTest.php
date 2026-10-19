<?php

namespace Tests\Unit\Repositories;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\Tribute;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Eloquent\EloquentAuditLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentAuditLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(AuditLogRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentAuditLogRepository::class, $this->repository);
    }

    #[Test]
    public function record_stores_the_actor_subject_and_meta(): void
    {
        $admin = User::factory()->admin()->create();
        $tribute = Tribute::factory()->create();

        $log = $this->repository->record($admin->id, AuditAction::TRIBUTE_APPROVED, $tribute, ['name' => $tribute->name]);

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(AuditAction::TRIBUTE_APPROVED, $log->action);
        $this->assertSame($tribute->getMorphClass(), $log->subject_type);
        $this->assertSame($tribute->id, $log->subject_id);
        $this->assertSame(['name' => $tribute->name], $log->meta);
        $this->assertNotNull($log->created_at);
        $this->assertTrue($tribute->is($log->subject));
        $this->assertTrue($admin->is($log->user));
    }

    #[Test]
    public function record_accepts_no_actor_subject_or_meta(): void
    {
        $log = $this->repository->record(null, AuditAction::USER_DELETED);

        $this->assertNull($log->user_id);
        $this->assertNull($log->subject_type);
        $this->assertNull($log->subject_id);
        $this->assertNull($log->meta);
        $this->assertNull($log->fresh()->meta);
    }

    #[Test]
    public function recent_returns_newest_first_with_the_user_loaded(): void
    {
        $logs = AuditLog::factory()->count(3)->create();

        $recent = $this->repository->recent(2);

        $this->assertSame([$logs[2]->id, $logs[1]->id], $recent->pluck('id')->all());
        $this->assertTrue($recent->first()->relationLoaded('user'));
    }

    #[Test]
    public function it_lists_logs_for_a_subject_and_by_action(): void
    {
        $tribute = Tribute::factory()->create();
        $other = Tribute::factory()->create();

        $approved = $this->repository->record(null, AuditAction::TRIBUTE_APPROVED, $tribute);
        $rejected = $this->repository->record(null, AuditAction::TRIBUTE_REJECTED, $tribute);
        $this->repository->record(null, AuditAction::TRIBUTE_APPROVED, $other);

        $this->assertSame([$rejected->id, $approved->id], $this->repository->forSubject($tribute)->pluck('id')->all());
        $this->assertCount(2, $this->repository->withAction(AuditAction::TRIBUTE_APPROVED));
        $this->assertCount(1, $this->repository->withAction(AuditAction::TRIBUTE_APPROVED, 1));
        $this->assertCount(0, $this->repository->withAction(AuditAction::USER_DELETED));
    }
}
