<?php

namespace Tests\Feature\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function staff_read_the_audit_log_and_nobody_changes_it(): void
    {
        $this->assertInstanceOf(AuditLogPolicy::class, Gate::getPolicyFor(AuditLog::class));

        $log = AuditLog::factory()->create();

        foreach ([User::factory()->admin()->create(), User::factory()->editor()->create()] as $staff) {
            $this->assertTrue($staff->can('viewAny', AuditLog::class));
            $this->assertTrue($staff->can('view', $log));
            $this->assertFalse($staff->can('create', AuditLog::class));
            $this->assertFalse($staff->can('update', $log));
            $this->assertFalse($staff->can('delete', $log));
        }

        $member = User::factory()->member()->create();
        $this->assertFalse($member->can('viewAny', AuditLog::class));
        $this->assertFalse($member->can('view', $log));
    }
}
