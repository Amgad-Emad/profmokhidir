<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\ContentTypes;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\User;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Services\MediaService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaLibraryServiceTest extends TestCase
{
    use RefreshDatabase;

    private MediaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(MediaService::class);
    }

    #[Test]
    public function usage_names_the_owning_record_and_links_to_its_admin_screen(): void
    {
        $album = Album::factory()->create(['title' => 'Field days']);
        $photo = $this->service->attach($album, PlaceholderImage::grey(32, 32), MediaCollections::PHOTOS);

        $usage = $this->service->usage($photo);

        $this->assertSame(ContentTypes::ALBUM, $usage['type']);
        $this->assertSame('Album', $usage['type_label']);
        $this->assertSame('Field days', $usage['label']);
        $this->assertSame(MediaCollections::PHOTOS, $usage['collection']);
        $this->assertSame(route(ContentTypes::editRoute(ContentTypes::ALBUM), ['album' => $album->getKey()]), $usage['url']);
        $this->assertFalse($usage['trashed']);

        $profile = Profile::factory()->create(['name' => 'Prof']);
        $portrait = $this->service->attach($profile, PlaceholderImage::grey(32, 40), MediaCollections::PORTRAIT);
        $this->assertSame(route(ContentTypes::indexRoute(ContentTypes::PROFILE)), $this->service->usage($portrait)['url']);

        $album->delete();
        $usage = $this->service->usage($photo->fresh());
        $this->assertTrue($usage['trashed']);
        $this->assertNull($usage['url']);
    }

    #[Test]
    public function the_library_lists_only_images_with_search_and_filters(): void
    {
        $album = Album::factory()->create();
        $ready = $this->service->attach($album, PlaceholderImage::grey(32, 32), MediaCollections::PHOTOS, [
            MediaProperties::ALT_TEXT => 'Harvest in Gezira', MediaProperties::CAPTION => 'Harvest', MediaProperties::PLACE => 'Gezira', MediaProperties::YEAR => 1980, MediaProperties::TREATMENT => MediaTreatment::Archive,
        ]);
        $incomplete = $this->service->attach($album, PlaceholderImage::grey(32, 32), MediaCollections::PHOTOS, [MediaProperties::TREATMENT => MediaTreatment::Portrait]);
        $this->service->attach(Publication::factory()->create(), UploadedFile::fake()->create('paper.pdf', 2, 'application/pdf'), MediaCollections::FILE);

        $this->assertSame(2, $this->service->library()->total());
        $this->assertSame([$ready->getKey()], $this->service->library('gezira')->pluck('id')->all());
        $this->assertSame([$incomplete->getKey()], $this->service->library(null, [MediaRepositoryInterface::FILTER_INCOMPLETE => true])->pluck('id')->all());
        $this->assertSame([$incomplete->getKey()], $this->service->library(null, [MediaRepositoryInterface::FILTER_TREATMENT => 'portrait'])->pluck('id')->all());
        $this->assertSame(2, $this->service->library(null, [MediaRepositoryInterface::FILTER_COLLECTION => MediaCollections::PHOTOS])->total());
        $this->assertSame(0, $this->service->library(null, [MediaRepositoryInterface::FILTER_COLLECTION => MediaCollections::PORTRAIT])->total());
        $this->assertSame(1, $this->service->countIncompleteImages());
        $this->assertTrue($this->service->isReadyForPublishing($ready));
        $this->assertTrue($this->service->isReadyForPublishing($incomplete), 'Missing details never block publishing.');
    }

    #[Test]
    public function uploads_and_detail_changes_are_audited_when_an_actor_is_given(): void
    {
        $editor = User::factory()->editor()->create();
        $album = Album::factory()->create();

        $photo = $this->service->attach($album, PlaceholderImage::grey(32, 32), MediaCollections::PHOTOS, [], null, false, $editor);
        $this->service->updateProperties($photo, [MediaProperties::CAPTION => 'Caption'], $editor);
        $this->service->attach($album, PlaceholderImage::grey(32, 32), MediaCollections::PHOTOS);

        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::MEDIA_UPLOADED)->count());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::MEDIA_UPDATED)->count());
        $this->assertSame($photo->getKey(), AuditLog::query()->where('action', AuditAction::MEDIA_UPLOADED)->first()->meta['media_id']);
    }
}
