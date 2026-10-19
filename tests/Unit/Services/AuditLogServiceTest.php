<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AuditLogService::class);
    }

    #[Test]
    public function it_rejects_unknown_actions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->record(null, 'made.up');
    }

    #[Test]
    public function it_records_actor_subject_and_meta(): void
    {
        $actor = User::factory()->create();
        $award = Award::factory()->create();

        $log = $this->service->record($actor, AuditAction::CONTENT_DELETED, $award, ['label' => 'x']);

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame(AuditAction::CONTENT_DELETED, $log->action);
        $this->assertSame($award->getMorphClass(), $log->subject_type);
        $this->assertSame($award->id, $log->subject_id);
        $this->assertSame(['label' => 'x'], $log->meta);
        $this->assertNotNull($log->created_at);
        $this->assertTrue($log->subject->is($award));
    }

    #[Test]
    public function it_records_without_actor_or_subject(): void
    {
        $log = $this->service->record(null, AuditAction::ADMIN_LOGIN);

        $this->assertNull($log->user_id);
        $this->assertNull($log->subject_type);
        $this->assertNull($log->meta);
    }

    #[Test]
    public function it_lists_recent_entries_for_subjects_and_by_action(): void
    {
        $actor = User::factory()->create();
        $award = Award::factory()->create();

        $this->service->record($actor, AuditAction::CONTENT_PUBLISHED, $award);
        $this->service->record($actor, AuditAction::CONTENT_UNPUBLISHED, $award);
        $this->service->record($actor, AuditAction::ADMIN_LOGIN);

        $this->assertSame(
            [AuditAction::ADMIN_LOGIN, AuditAction::CONTENT_UNPUBLISHED],
            $this->service->recent(2)->pluck('action')->all(),
        );
        $this->assertCount(2, $this->service->forSubject($award));
        $this->assertCount(1, $this->service->withAction(AuditAction::ADMIN_LOGIN));
    }
}
