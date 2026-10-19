<?php

namespace Tests\Feature\Database;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use Database\Seeders\AuditLogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_staff_sign_ins_and_placeholder_activity_once(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, UserSeeder::class, AuditLogSeeder::class]);

        $this->assertSame(2, AuditLog::query()->where('action', AuditAction::ADMIN_LOGIN)->count());
        $this->assertSame(4, AuditLog::query()->where('action', AuditAction::CONTENT_PUBLISHED)->count());
        $this->assertSame(2, AuditLog::query()->where('action', AuditAction::TRIBUTE_APPROVED)->count());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ADMIN_LOGIN)->get()->every(fn (AuditLog $log): bool => $log->user_id !== null && $log->subject_id === $log->user_id));

        $count = AuditLog::query()->count();
        $this->seed(AuditLogSeeder::class);
        $this->assertSame($count, AuditLog::query()->count());
    }

    #[Test]
    public function it_does_nothing_without_the_seeded_staff_accounts(): void
    {
        $this->seed(AuditLogSeeder::class);

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
