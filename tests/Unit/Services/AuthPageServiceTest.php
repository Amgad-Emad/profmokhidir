<?php

namespace Tests\Unit\Services;

use App\Constants\AuthScreens;
use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Constants\Taglines;
use App\Models\Album;
use App\Models\Profile;
use App\Services\AlbumService;
use App\Services\AuthPageService;
use App\Services\ProfileService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The evergreen panel of the sign-in screens: the published portrait, or a
 * featured archive photograph when there is none, cached and cleared with
 * the profile and the albums.
 */
class AuthPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(AuthPageService::class);
    }

    #[Test]
    public function the_layout_carries_the_screen_copy_the_epigraph_and_the_way_back(): void
    {
        $layout = $this->service->layout(AuthScreens::LOGIN);

        $this->assertSame(AuthScreens::screen(AuthScreens::LOGIN), $layout['screen']);
        $this->assertSame(Taglines::ABOUT_EPIGRAPH, $layout['epigraph']);
        $this->assertSame(AuthScreens::PANEL_LABEL, $layout['label']);
        $this->assertSame(['label' => AuthScreens::BACK_LABEL, 'url' => route('home')], $layout['back']);
        $this->assertNull($layout['portrait']);
        $this->assertNull($layout['caption']);
    }

    #[Test]
    public function an_unknown_screen_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->layout('register');
    }

    #[Test]
    public function the_published_portrait_comes_first_with_its_place_and_year(): void
    {
        $profile = Profile::factory()->published()->withPortrait()->create();

        $layout = $this->service->layout(AuthScreens::FORGOT_PASSWORD);

        $this->assertSame($profile->fresh()->portrait()->id, $layout['portrait']->id);
        $this->assertSame('Khartoum, 1998', $layout['caption']);
    }

    #[Test]
    public function a_draft_profile_falls_back_to_a_featured_archive_photo_of_a_published_album(): void
    {
        Profile::factory()->draft()->withPortrait()->create();

        $albums = $this->app->make(AlbumService::class);
        $album = Album::factory()->published()->create();
        $plain = $albums->addPhoto($album, PlaceholderImage::grey(64, 48), [MediaProperties::ALT_TEXT => 'Plain', MediaProperties::PLACE => 'Wad Madani', MediaProperties::YEAR => 1972]);
        $featured = $albums->addPhoto($album, PlaceholderImage::grey(64, 48), [MediaProperties::ALT_TEXT => 'Featured', MediaProperties::YEAR => 1980]);
        $albums->updatePhotoArchive($album, $featured, 1980, true);

        $this->assertSame($featured->id, $this->service->portrait()?->id);

        $albums->updatePhotoArchive($album, $featured, 1980, false);
        $this->service->flushCache();

        $this->assertSame($plain->id, $this->service->portrait()?->id, 'Without a featured print the first archive photo is used');
        $this->assertSame('Wad Madani, 1972', $this->service->layout(AuthScreens::LOGIN)['caption']);
    }

    #[Test]
    public function draft_albums_and_portrait_treated_photos_are_never_used(): void
    {
        $albums = $this->app->make(AlbumService::class);

        $draft = Album::factory()->draft()->create();
        $albums->addPhoto($draft, PlaceholderImage::grey(64, 48), [MediaProperties::ALT_TEXT => 'Draft']);

        $published = Album::factory()->published()->create();
        $albums->addPhoto($published, PlaceholderImage::grey(64, 48), [MediaProperties::ALT_TEXT => 'Portrait print', MediaProperties::TREATMENT => MediaTreatment::Portrait->value]);

        $this->assertNull($this->service->portrait());
    }

    #[Test]
    public function the_portrait_is_cached_and_cleared_when_the_profile_or_an_album_changes(): void
    {
        $profile = Profile::factory()->published()->withPortrait()->create();

        $this->service->portrait();
        $this->assertTrue(Cache::has(CacheKeys::AUTH_PORTRAIT));

        $this->app->make(ProfileService::class)->save(['name' => 'Mohamed Osman Khidir']);
        $this->assertFalse(Cache::has(CacheKeys::AUTH_PORTRAIT), 'Saving the profile clears the sign-in portrait');

        $this->service->portrait();
        $this->assertTrue(Cache::has(CacheKeys::AUTH_PORTRAIT));

        $this->app->make(AlbumService::class)->flushCache();
        $this->assertFalse(Cache::has(CacheKeys::AUTH_PORTRAIT), 'Album changes clear it too');

        $this->assertSame($profile->fresh()->portrait()->id, $this->service->portrait()->id);
    }
}
