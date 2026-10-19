<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\TeachingMaterialService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeachingMaterialServiceTest extends TestCase
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
    public function members_only_materials_are_hidden_from_visitors_and_applicants(): void
    {
        TeachingMaterial::factory()->published()->create();
        TeachingMaterial::factory()->published()->membersOnly()->create();
        TeachingMaterial::factory()->draft()->create();

        $this->assertCount(1, $this->service->publishedFor(null));
        $this->assertCount(1, $this->service->publishedFor(User::factory()->applicant()->create()));
        $this->assertCount(1, $this->service->publishedFor(User::factory()->create()));
    }

    #[Test]
    public function members_and_staff_see_members_only_materials(): void
    {
        TeachingMaterial::factory()->published()->create();
        TeachingMaterial::factory()->published()->membersOnly()->create();

        $this->assertCount(2, $this->service->publishedFor(User::factory()->member()->create()));
        $this->assertCount(2, $this->service->publishedFor(User::factory()->admin()->create()));
        $this->assertCount(2, $this->service->publishedFor(User::factory()->editor()->create()));
    }

    #[Test]
    public function listings_are_cached_under_separate_keys_and_flushed_on_write(): void
    {
        TeachingMaterial::factory()->published()->membersOnly()->create();

        $this->service->publishedFor(null);
        $this->assertTrue(Cache::has(CacheKeys::TEACHING_MATERIALS));
        $this->assertFalse(Cache::has(CacheKeys::TEACHING_MATERIALS.'.members'));

        $this->service->publishedFor(User::factory()->member()->create());
        $this->assertTrue(Cache::has(CacheKeys::TEACHING_MATERIALS.'.members'));

        $this->service->create(['title' => 'New', 'type' => 'notes']);
        $this->assertFalse(Cache::has(CacheKeys::TEACHING_MATERIALS));
        $this->assertFalse(Cache::has(CacheKeys::TEACHING_MATERIALS.'.members'));
    }

    #[Test]
    public function can_download_follows_status_and_access(): void
    {
        $draft = TeachingMaterial::factory()->draft()->create();
        $public = TeachingMaterial::factory()->published()->create();
        $members = TeachingMaterial::factory()->published()->membersOnly()->create();

        $member = User::factory()->member()->create();
        $editor = User::factory()->editor()->create();

        $this->assertFalse($this->service->canDownload($draft, null));
        $this->assertFalse($this->service->canDownload($draft, $member));
        $this->assertTrue($this->service->canDownload($draft, $editor));

        $this->assertTrue($this->service->canDownload($public, null));
        $this->assertFalse($this->service->canDownload($members, null));
        $this->assertTrue($this->service->canDownload($members, $member));
        $this->assertTrue($this->service->canDownload($members, $editor));
    }

    #[Test]
    public function group_by_course_sorts_courses_and_puts_untitled_last(): void
    {
        $materials = TeachingMaterial::factory()->count(0)->make()->concat([
            TeachingMaterial::factory()->make(['course' => null]),
            TeachingMaterial::factory()->make(['course' => 'B Course']),
            TeachingMaterial::factory()->make(['course' => 'A Course']),
            TeachingMaterial::factory()->make(['course' => 'B Course']),
        ]);

        $grouped = $this->service->groupByCourse(new Collection($materials->all()));

        $this->assertSame(['A Course', 'B Course', ''], array_keys($grouped));
        $this->assertCount(2, $grouped['B Course']);
    }

    #[Test]
    public function files_land_on_the_disk_matching_the_access_level(): void
    {
        $public = TeachingMaterial::factory()->create();
        $members = TeachingMaterial::factory()->membersOnly()->create();

        $publicFile = $this->service->attachFile($public, UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'));
        $membersFile = $this->service->attachFile($members, UploadedFile::fake()->create('b.pdf', 5, 'application/pdf'));

        $this->assertSame(Disks::PUBLIC, $publicFile->disk);
        $this->assertSame(Disks::PRIVATE, $membersFile->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($membersFile->getPathRelativeToRoot());
        Storage::disk(Disks::PUBLIC)->assertMissing($membersFile->getPathRelativeToRoot());
    }

    #[Test]
    public function changing_access_moves_an_existing_file(): void
    {
        $material = TeachingMaterial::factory()->create();
        $this->service->attachFile($material, UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'));

        $material = $this->service->update($material, ['access' => 'members', 'type' => TeachingMaterialType::Slides->value]);

        $this->assertSame(TeachingAccess::Members, $material->access);
        $this->assertSame(TeachingMaterialType::Slides, $material->type);
        $file = $material->fresh()->file();
        $this->assertSame(Disks::PRIVATE, $file->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($file->getPathRelativeToRoot());

        $material = $this->service->update($material, ['access' => TeachingAccess::Public]);
        $this->assertSame(Disks::PUBLIC, $material->fresh()->file()->disk);

        $this->service->removeFile($material);
        $this->assertNull($material->fresh()->file());
    }
}
