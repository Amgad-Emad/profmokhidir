<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Livewire\Admin\Albums\Form as AlbumForm;
use App\Models\Album;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumPhotosTest extends TestCase
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
    public function photos_are_validated_and_uploaded_with_the_archive_treatment(): void
    {
        $album = Album::factory()->draft()->create();

        Livewire::actingAs($this->editor)->test(AlbumForm::class, ['album' => $album])
            ->assertSee('No images yet.')
            ->set('newImages', [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->assertHasErrors(['newImages.0'])
            ->assertSee('Choose an image file: JPEG, PNG or WebP.')
            ->set('newImages', [UploadedFile::fake()->image('one.jpg', 40, 30), UploadedFile::fake()->image('two.png', 40, 30)])
            ->assertHasNoErrors()
            ->assertSee('2 images uploaded')
            ->assertSee('Needs details');

        $photos = $album->fresh()->photos();
        $this->assertCount(2, $photos);
        $this->assertSame(MediaTreatment::Archive->value, $photos->first()->getCustomProperty(MediaProperties::TREATMENT));
        $this->assertSame(40, $photos->first()->getCustomProperty(MediaProperties::WIDTH));
    }

    #[Test]
    public function details_are_optional_on_a_draft_and_required_once_the_album_is_published(): void
    {
        $albums = $this->app->make(AlbumService::class);
        $album = Album::factory()->draft()->create();
        $photo = $albums->addPhoto($album, PlaceholderImage::grey(40, 30));
        $id = $photo->getKey();

        $component = Livewire::actingAs($this->editor)->test(AlbumForm::class, ['album' => $album])
            ->set("images.$id.alt_text", 'Students in the field')
            ->call('saveImage', $id)
            ->assertHasNoErrors()
            ->assertSee('Image details saved.');

        $this->assertSame('Students in the field', $photo->fresh()->getCustomProperty(MediaProperties::ALT_TEXT));

        // Publishing is refused while the photo lacks caption, place and year.
        $component->set('form.status', ContentStatus::Published->value)
            ->call('save')
            ->assertHasErrors(['form.status'])
            ->assertSee('still lacks a caption, a place, a year');

        $this->assertTrue($album->fresh()->isDraft());

        $component->set("images.$id.caption", 'Field practical')
            ->set("images.$id.place", 'Shambat')
            ->set("images.$id.year", '1979')
            ->call('saveImage', $id)
            ->assertHasNoErrors()
            ->assertSee('Ready')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Album saved.');

        $this->assertTrue($album->fresh()->isPublished());

        // Now every detail is required and the treatment must be valid.
        $component->set("images.$id.caption", '')
            ->set("images.$id.treatment", 'sepia')
            ->call('saveImage', $id)
            ->assertHasErrors(["images.$id.caption", "images.$id.treatment"])
            ->assertSee('Add a caption before publishing.');
    }

    #[Test]
    public function photos_can_be_reordered_given_a_cover_and_removed_after_confirmation(): void
    {
        $albums = $this->app->make(AlbumService::class);
        $album = Album::factory()->draft()->create();
        $first = $albums->addPhoto($album, PlaceholderImage::grey(40, 30));
        $second = $albums->addPhoto($album, PlaceholderImage::grey(40, 30));
        $third = $albums->addPhoto($album, PlaceholderImage::grey(40, 30));

        $component = Livewire::actingAs($this->editor)->test(AlbumForm::class, ['album' => $album])
            ->call('movePhotoUp', $third->getKey())
            ->assertSee('Photo order saved.');

        $this->assertSame([$first->getKey(), $third->getKey(), $second->getKey()], $album->fresh()->photos()->modelKeys());

        $component->call('reorderPhotos', [$second->getKey(), $first->getKey(), $third->getKey()]);
        $this->assertSame([$second->getKey(), $first->getKey(), $third->getKey()], $album->fresh()->photos()->modelKeys());

        $component->call('setCover', $third->getKey())
            ->assertSee('Cover photo set.');
        $this->assertTrue($album->fresh()->cover()->is($third));

        $component->call('confirmRemoveImage', $first->getKey())
            ->assertSee('Remove this image?')
            ->call('cancelRemoveImage')
            ->assertDontSee('Remove this image?')
            ->call('confirmRemoveImage', $first->getKey())
            ->call('removeImage')
            ->assertSee('Image removed.');

        $this->assertCount(2, $album->fresh()->photos());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::MEDIA_DELETED)->count());
    }

    #[Test]
    public function photos_cannot_be_added_before_the_album_exists(): void
    {
        Livewire::actingAs($this->editor)->test(AlbumForm::class)
            ->assertSee('Save first, then add images.')
            ->assertDontSee('id="new-images"', false);
    }
}
