<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\TributeStatus;
use App\Models\AuditLog;
use App\Models\Tribute;
use App\Models\User;
use App\Services\TributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TributeService $service;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(TributeService::class);
        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function editing_fixes_the_text_and_keeps_the_original_in_the_audit_log(): void
    {
        $tribute = Tribute::factory()->pending()->create(['name' => 'Amna', 'location' => 'Khartoum', 'message' => 'He taught us wel.']);

        $edited = $this->service->edit($tribute, ['name' => 'Amna ', 'location' => '', 'message' => 'He taught us well.'], $this->editor);

        $this->assertSame('Amna', $edited->name);
        $this->assertNull($edited->location);
        $this->assertSame('He taught us well.', $edited->message);

        $log = AuditLog::query()->where('action', AuditAction::TRIBUTE_EDITED)->first();
        $this->assertNotNull($log);
        $this->assertSame($this->editor->getKey(), $log->user_id);
        $this->assertEquals(['location' => 'Khartoum', 'message' => 'He taught us wel.'], $log->meta['original']);
        $this->assertSame(['location', 'message'], $log->meta['changed']);
    }

    #[Test]
    public function editing_without_changes_records_nothing(): void
    {
        $tribute = Tribute::factory()->pending()->create(['name' => 'Amna', 'message' => 'Same']);

        $this->service->edit($tribute, ['name' => 'Amna', 'message' => 'Same'], $this->editor);

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function bulk_approval_and_rejection_skip_records_already_in_that_state(): void
    {
        $pending = Tribute::factory()->count(3)->pending()->create();
        $approved = Tribute::factory()->approved()->create();

        $count = $this->service->approveMany([...$pending->modelKeys(), $approved->getKey(), 999999], $this->editor);

        $this->assertSame(3, $count);
        $this->assertSame(4, $this->service->countByStatus(TributeStatus::Approved));
        $this->assertSame(3, AuditLog::query()->where('action', AuditAction::TRIBUTE_APPROVED)->count());

        $rejected = $this->service->rejectMany([$pending[0]->getKey(), $pending[0]->getKey()], $this->editor);

        $this->assertSame(1, $rejected);
        $this->assertSame(1, $this->service->countByStatus(TributeStatus::Rejected));
        $this->assertNull($pending[0]->fresh()->published_at);
    }
}
