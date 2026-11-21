<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Livewire\Admin\Albums\Form as AlbumForm;
use App\Livewire\Admin\Awards\Form as AwardForm;
use App\Livewire\Admin\Media\Library;
use App\Livewire\Admin\Videos\Form as VideoForm;
use App\Models\Album;
use App\Models\Award;
use App\Models\User;
use App\Models\Video;
use App\Services\AlbumService;
use App\Services\AwardService;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every image in the admin (award certificates, album photos, posters,
 * portraits, other-information images, page images in the library) can have
 * its file replaced while keeping its details, order and where it is used.
 */
class MediaReplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function an_award_certificate_is_replaced_in_place_and_keeps_its_details(): void
    {
        $editor = User::factory()->editor()->create();
        $award = Award::factory()->published()->create();
        $image = app(AwardService::class)->addImage($award, UploadedFile::fake()->image('old.jpg', 40, 30), [
            MediaProperties::ALT_TEXT => 'The framed certificate',
            MediaProperties::CAPTION => 'Certificate',
        ]);
        Cache::put(CacheKeys::AWARDS, 'stale');

        Livewire::actingAs($editor)->test(AwardForm::class, ['award' => $award])
            ->assertSee('Replace image')
            ->set("replacements.{$image->id}", UploadedFile::fake()->image('scan.jpg', 120, 90))
            ->assertHasNoErrors()
            ->assertSet('replacements', [])
            ->assertSee('scan.jpg');

        $replaced = $award->fresh()->getMedia(MediaCollections::AWARD_IMAGES)->sole();
        $this->assertSame($image->id, $replaced->id);
        $this->assertSame('scan.jpg', $replaced->file_name);
        $this->assertSame('The framed certificate', $replaced->getCustomProperty(MediaProperties::ALT_TEXT));
        $this->assertSame(120, $replaced->getCustomProperty(MediaProperties::WIDTH));
        $this->assertFalse(Cache::has(CacheKeys::AWARDS));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::MEDIA_REPLACED, 'user_id' => $editor->id]);
    }

    #[Test]
    public function a_replaced_album_photo_keeps_its_place_and_the_cover(): void
    {
        $album = Album::factory()->create();
        $first = app(AlbumService::class)->addPhoto($album, UploadedFile::fake()->image('one.jpg', 40, 30));
        $second = app(AlbumService::class)->addPhoto($album, UploadedFile::fake()->image('two.jpg', 40, 30));

        Livewire::actingAs(User::factory()->editor()->create())->test(AlbumForm::class, ['album' => $album])
            ->call('setCover', $first->id)
            ->set("replacements.{$first->id}", UploadedFile::fake()->image('one-better.jpg', 40, 30))
            ->assertHasNoErrors();

        $photos = $album->fresh()->getMedia(MediaCollections::PHOTOS);
        $this->assertSame([$first->id, $second->id], $photos->pluck('id')->all());
        $this->assertSame('one-better.jpg', $photos->first()->file_name);
        $this->assertTrue((bool) $photos->first()->getCustomProperty(MediaProperties::IS_COVER));
    }

    #[Test]
    public function a_video_poster_offers_replace_instead_of_a_second_upload(): void
    {
        $video = Video::factory()->create();
        $poster = app(MediaService::class)->attach($video, UploadedFile::fake()->image('poster.jpg', 40, 30), MediaCollections::THUMBNAIL, [MediaProperties::ALT_TEXT => 'The lecture hall']);

        Livewire::actingAs(User::factory()->editor()->create())->test(VideoForm::class, ['video' => $video])
            ->assertDontSee('Replace the image')
            ->assertSee('Replace image')
            ->set("replacements.{$poster->id}", UploadedFile::fake()->image('poster-2.jpg', 40, 30))
            ->assertHasNoErrors();

        $this->assertSame('The lecture hall', $video->fresh()->getFirstMedia(MediaCollections::THUMBNAIL)->getCustomProperty(MediaProperties::ALT_TEXT));
        $this->assertSame('poster-2.jpg', $video->fresh()->getFirstMedia(MediaCollections::THUMBNAIL)->file_name);
    }

    #[Test]
    public function a_replacement_must_be_an_image_and_the_old_file_stays_when_it_is_not(): void
    {
        $award = Award::factory()->create();
        $image = app(AwardService::class)->addImage($award, UploadedFile::fake()->image('old.jpg', 40, 30));

        Livewire::actingAs(User::factory()->editor()->create())->test(AwardForm::class, ['award' => $award])
            ->set("replacements.{$image->id}", UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
            ->assertHasErrors("replacements.{$image->id}");

        $this->assertSame('old.jpg', $image->fresh()->file_name);
    }

    #[Test]
    public function the_media_library_replaces_the_file_of_the_image_being_edited(): void
    {
        $album = Album::factory()->create();
        $photo = app(AlbumService::class)->addPhoto($album, UploadedFile::fake()->image('old.jpg', 40, 30), [MediaProperties::CAPTION => 'Graduation']);

        Livewire::actingAs(User::factory()->editor()->create())->test(Library::class)
            ->call('edit', $photo->id)
            ->assertSee('Replace the file')
            ->set('replacement', UploadedFile::fake()->image('graduation.webp', 60, 40))
            ->assertHasNoErrors()
            ->assertSet('replacement', null);

        $photo->refresh();
        $this->assertSame('graduation.webp', $photo->file_name);
        $this->assertSame('Graduation', $photo->getCustomProperty(MediaProperties::CAPTION));
    }

    #[Test]
    public function members_cannot_replace_files(): void
    {
        $award = Award::factory()->create();
        $image = app(AwardService::class)->addImage($award, UploadedFile::fake()->image('old.jpg', 40, 30));

        Livewire::actingAs(User::factory()->member()->create())->test(AwardForm::class, ['award' => $award])
            ->assertForbidden();

        $this->assertSame('old.jpg', $image->fresh()->file_name);
    }
}
