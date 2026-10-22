<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Constants\Uploads;
use App\Livewire\Admin\Media\Library;
use App\Models\Album;
use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use App\Services\AlbumService;
use App\Services\ProfileService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function the_library_lists_every_image_with_its_usage_and_filters_incomplete_ones(): void
    {
        $album = Album::factory()->create(['title' => 'Field days']);
        $profile = Profile::factory()->create(['name' => 'Prof']);
        $ready = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(40, 30), [
            MediaProperties::ALT_TEXT => 'Harvest', MediaProperties::CAPTION => 'Harvest time', MediaProperties::PLACE => 'Gezira', MediaProperties::YEAR => 1980,
        ]);
        $portrait = $this->app->make(ProfileService::class)->setPortrait($profile, PlaceholderImage::grey(40, 50));

        Livewire::actingAs($this->editor)->test(Library::class)
            ->assertSee('1 image need details')
            ->assertSee('Album · Field days')
            ->assertSee('Profile · Prof')
            ->assertSee('Harvest time')
            ->assertSee(route('admin.media.albums.edit', ['album' => $album->getKey()]), false)
            ->assertSee(route('admin.content.profile'), false)
            ->set('incomplete', true)
            ->assertDontSee('Harvest time')
            ->assertSee('Profile · Prof')
            ->set('incomplete', false)
            ->set('search', 'gezira')
            ->assertSee('Harvest time')
            ->assertDontSee('Profile · Prof')
            ->set('search', '')
            ->set('collection', 'portrait')
            ->assertDontSee('Harvest time')
            ->assertSee('Profile · Prof');
    }

    #[Test]
    public function details_are_edited_in_a_dialog_and_required_when_the_owner_is_published(): void
    {
        $album = Album::factory()->published()->create();
        $photo = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(40, 30));

        Livewire::actingAs($this->editor)->test(Library::class)
            ->assertDontSee('data-details-dialog', false)
            ->call('edit', $photo->getKey())
            ->assertSee('data-details-dialog', false)
            ->assertSee('every detail is required')
            ->set('details.alt_text', 'Students')
            ->call('saveDetails')
            ->assertHasErrors(['details.caption', 'details.place', 'details.year'])
            ->set('details.caption', 'Practical class')
            ->set('details.place', 'Shambat')
            ->set('details.year', '1981')
            ->set('details.treatment', 'portrait')
            ->call('saveDetails')
            ->assertHasNoErrors()
            ->assertSee('Image details saved.')
            ->assertDontSee('data-details-dialog', false)
            ->assertDontSee('image need details');

        $photo = $photo->fresh();
        $this->assertSame('Practical class', $photo->getCustomProperty(MediaProperties::CAPTION));
        $this->assertSame(1981, $photo->getCustomProperty(MediaProperties::YEAR));
        $this->assertSame('portrait', $photo->getCustomProperty(MediaProperties::TREATMENT));
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::MEDIA_UPDATED)->count());
    }

    #[Test]
    public function uploads_go_into_a_chosen_album_and_are_validated(): void
    {
        $album = Album::factory()->create(['title' => 'Archive']);

        Livewire::actingAs($this->editor)->test(Library::class)
            ->set('uploads', [UploadedFile::fake()->image('a.jpg', 40, 30)])
            ->assertHasErrors(['uploadAlbumId'])
            ->assertSee('Choose the album the images belong to.');

        $this->assertCount(0, $album->fresh()->photos());

        Livewire::actingAs($this->editor)->test(Library::class)
            ->set('uploadAlbumId', $album->getKey())
            ->set('uploads', [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertHasErrors(['uploads.0'])
            ->assertSee('Choose an image file: JPEG, PNG or WebP.')
            ->set('uploads', [UploadedFile::fake()->image('a.jpg', 40, 30), UploadedFile::fake()->image('b.webp', 40, 30)])
            ->assertHasNoErrors()
            ->assertSee('2 images uploaded');

        $this->assertCount(2, $album->fresh()->photos());
        $this->assertSame(Uploads::IMAGE_MAX_KILOBYTES, 10240);
    }

    #[Test]
    public function deleting_shows_where_the_image_is_used_and_is_audited(): void
    {
        $album = Album::factory()->create(['title' => 'Field days']);
        $photo = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(40, 30));

        Livewire::actingAs($this->editor)->test(Library::class)
            ->call('confirm', 'delete', $photo->getKey())
            ->assertSee('Delete this image?')
            ->assertSee('is used by')
            ->assertSee('Album: Field days')
            ->call('delete')
            ->assertSee('Image deleted.')
            ->assertSee('No images have been uploaded yet.');

        $this->assertNull(Media::query()->find($photo->getKey()));
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::MEDIA_DELETED)->count());
    }

    #[Test]
    public function members_cannot_open_the_library(): void
    {
        Livewire::actingAs(User::factory()->member()->create())->test(Library::class)->assertForbidden();
        $this->actingAs(User::factory()->member()->create())->get(route('admin.media.library'))->assertForbidden();
    }
}
