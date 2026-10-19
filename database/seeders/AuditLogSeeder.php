<?php

namespace Database\Seeders;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthenticationService;
use Illuminate\Database\Seeder;

/**
 * A handful of placeholder audit entries so the dashboard's activity feed has
 * something to show locally. Real actions append their own rows.
 */
class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(UserRepositoryInterface $users, AuditLogRepositoryInterface $logs, AuthenticationService $auth): void
    {
        // Role assignments made by UserSeeder are already logged; only seed once.
        if ($logs->withAction(AuditAction::ADMIN_LOGIN, 1)->isNotEmpty()) {
            return;
        }

        $admin = $users->findByEmail('admin@example.com');
        $editor = $users->findByEmail('editor@example.com');

        if ($admin === null || $editor === null) {
            return;
        }

        $auth->recordLogin($admin, '127.0.0.1', 'Seeder');
        $auth->recordLogin($editor, '127.0.0.1', 'Seeder');

        AuditLog::factory()->count(4)->for($editor, 'user')->create([
            'action' => AuditAction::CONTENT_PUBLISHED,
            'meta' => ['label' => '[Placeholder] Published item'],
        ]);

        AuditLog::factory()->count(2)->for($admin, 'user')->create([
            'action' => AuditAction::TRIBUTE_APPROVED,
            'meta' => ['label' => '[Placeholder] Tribute'],
        ]);
    }
}
