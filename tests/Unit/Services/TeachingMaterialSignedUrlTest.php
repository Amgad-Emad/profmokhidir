<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\Disks;
use App\Models\AuditLog;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\TeachingMaterialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeachingMaterialSignedUrlTest extends TestCase
{
    use RefreshDatabase;

    private TeachingMaterialService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);
        $this->service = $this->app->make(TeachingMaterialService::class);
    }

    #[Test]
    public function download_urls_are_plain_for_public_files_signed_for_members_files_and_absent_without_a_file(): void
    {
        $public = TeachingMaterial::factory()->create();
        $members = TeachingMaterial::factory()->membersOnly()->create();
        $empty = TeachingMaterial::factory()->create();

        $this->service->attachFile($public, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));
        $this->service->attachFile($members, UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'));

        $this->assertSame(route('teaching.download', $public), $this->service->downloadUrl($public));
        $this->assertStringStartsWith(route('teaching.download', $members).'?expires=', (string) $this->service->downloadUrl($members));
        $this->assertStringContainsString('&signature=', (string) $this->service->downloadUrl($members));
        $this->assertNull($this->service->downloadUrl($empty));
        $this->assertSame(Disks::PRIVATE, $members->file()->disk);
    }

    #[Test]
    public function updating_with_an_actor_audits_the_status_change(): void
    {
        $material = TeachingMaterial::factory()->draft()->create();
        $editor = User::factory()->editor()->create();

        $this->service->update($material, ['status' => 'published'], $editor);

        $log = AuditLog::query()->where('action', AuditAction::CONTENT_PUBLISHED)->first();
        $this->assertNotNull($log);
        $this->assertSame($editor->getKey(), $log->user_id);
    }
}
