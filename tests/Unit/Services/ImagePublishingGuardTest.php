<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Models\Album;
use App\Models\Profile;
use App\Services\AlbumService;
use App\Services\ProfileService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImagePublishingGuardTest extends TestCase
{
    use RefreshDatabase;

    private AlbumService $albums;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->albums = $this->app->make(AlbumService::class);
    }

    #[Test]
    public function an_album_publishes_while_a_photo_lacks_details(): void
    {
        $album = Album::factory()->draft()->create();
        $this->albums->addPhoto($album, PlaceholderImage::grey(64, 64), $this->completeDetails());
        $this->albums->addPhoto($album, PlaceholderImage::grey(64, 64), [MediaProperties::ALT_TEXT => 'Only alt']);
        $this->albums->addPhoto($album, PlaceholderImage::grey(64, 64));

        $this->assertSame([], $this->albums->incompleteImages($album));
        $this->assertTrue($this->albums->publish($album)->isPublished());
    }

    #[Test]
    public function an_album_with_complete_photos_publishes_and_drafts_are_never_blocked(): void
    {
        $album = Album::factory()->draft()->create();
        $this->albums->addPhoto($album, PlaceholderImage::grey(64, 64), $this->completeDetails());

        $this->assertTrue($this->albums->publish($album)->isPublished());

        $incompleteDraft = Album::factory()->draft()->create();
        $this->albums->addPhoto($incompleteDraft, PlaceholderImage::grey(64, 64));
        $this->assertSame('Renamed', $this->albums->update($incompleteDraft, ['title' => 'Renamed'])->title);

        // An already published album may be edited freely.
        $published = Album::factory()->published()->create();
        $this->albums->addPhoto($published, PlaceholderImage::grey(64, 64));
        $this->assertSame('Edited', $this->albums->update($published, ['title' => 'Edited', 'status' => 'published'])->title);
    }

    #[Test]
    public function the_profile_publishes_while_the_portrait_lacks_details(): void
    {
        Storage::fake(Disks::PUBLIC);
        $profiles = $this->app->make(ProfileService::class);
        $profile = Profile::factory()->draft()->create();
        $profiles->setPortrait($profile, PlaceholderImage::grey(64, 80), [MediaProperties::ALT_TEXT => 'Portrait']);

        $this->assertTrue($profiles->save(['status' => 'published'])->isPublished());
    }

    /**
     * @return array<string, mixed>
     */
    private function completeDetails(): array
    {
        return [
            MediaProperties::ALT_TEXT => 'Professor Khidir in the field',
            MediaProperties::CAPTION => 'Field day',
            MediaProperties::PLACE => 'Gezira',
            MediaProperties::YEAR => 1985,
        ];
    }
}
