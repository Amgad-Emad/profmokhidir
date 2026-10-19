<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\User;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlbumService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(AlbumService::class);
    }

    #[Test]
    public function photos_are_appended_in_order_with_the_archive_treatment(): void
    {
        $album = Album::factory()->create();

        $one = $this->service->addPhoto($album, PlaceholderImage::grey(64, 64), [MediaProperties::CAPTION => 'One']);
        $two = $this->service->addPhoto($album, PlaceholderImage::grey(64, 64), [MediaProperties::CAPTION => 'Two']);

        $this->assertSame([$one->id, $two->id], $album->fresh()->photos()->pluck('id')->all());
        $this->assertSame(MediaTreatment::Archive->value, $one->getCustomProperty(MediaProperties::TREATMENT));
        $this->assertTrue($album->fresh()->cover()->is($one), 'First photo is the cover by default');
    }

    #[Test]
    public function photos_can_be_reordered(): void
    {
        $album = Album::factory()->withPhotos(3, 64, 64)->create();
        $ids = $album->photos()->pluck('id')->all();

        $this->service->reorderPhotos(array_reverse($ids));

        $this->assertSame(array_reverse($ids), $album->fresh()->photos()->pluck('id')->all());
    }

    #[Test]
    public function set_cover_flags_exactly_one_photo(): void
    {
        $album = Album::factory()->withPhotos(3, 64, 64)->create();
        $photos = $album->photos();

        $this->service->setCover($album, $photos[2]);
        $this->assertTrue($album->fresh()->cover()->is($photos[2]));

        $this->service->setCover($album->fresh(), $photos[1]);
        $fresh = $album->fresh();
        $this->assertTrue($fresh->cover()->is($photos[1]));
        $this->assertSame(1, $fresh->photos()->filter(fn ($m) => $m->getCustomProperty(MediaProperties::IS_COVER))->count());
    }

    #[Test]
    public function remove_photo_is_audited(): void
    {
        $actor = User::factory()->create();
        $album = Album::factory()->withPhotos(2, 64, 64)->create();
        $photo = $album->photos()->first();

        $this->service->removePhoto($photo, $actor);

        $this->assertSame(1, $album->fresh()->photos()->count());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $actor->id, 'action' => AuditAction::MEDIA_DELETED]);
    }

    #[Test]
    public function slugs_are_unique_and_published_lookup_ignores_drafts(): void
    {
        $first = $this->service->create(['title' => 'In the Field', 'status' => 'published']);
        $second = $this->service->create(['title' => 'In the Field']);

        $this->assertSame('in-the-field', $first->slug);
        $this->assertSame('in-the-field-2', $second->slug);
        $this->assertTrue($this->service->findPublishedBySlug('in-the-field')->is($first));
        $this->assertNull($this->service->findPublishedBySlug('in-the-field-2'));
    }
}
