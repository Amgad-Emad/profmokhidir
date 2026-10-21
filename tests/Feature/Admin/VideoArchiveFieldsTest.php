<?php

namespace Tests\Feature\Admin;

use App\Constants\Archive;
use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Livewire\Admin\Albums\Form as AlbumForm;
use App\Livewire\Admin\Videos\Form as VideoForm;
use App\Models\Album;
use App\Models\User;
use App\Models\Video;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The archive fields editors set on recordings and album photos: a decade
 * override and a featured flag.
 */
class VideoArchiveFieldsTest extends TestCase
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
    public function editors_file_a_recording_under_a_decade_and_feature_it(): void
    {
        Livewire::actingAs($this->editor)->test(VideoForm::class)
            ->assertSee('Decade')
            ->assertSee('<option value="1970">1970s</option>', false)
            ->assertSee('Featured recording')
            ->set('form.title', 'Inaugural lecture')
            ->set('form.provider', 'youtube')
            ->set('form.media_url', 'https://youtu.be/dQw4w9WgXcQ')
            ->set('form.date', '1983-03-12')
            ->set('form.decade', '1970')
            ->set('form.is_featured', true)
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $video = Video::query()->where('title', 'Inaugural lecture')->sole();
        $this->assertSame(1970, $video->decade);
        $this->assertTrue($video->is_featured);

        $this->get(route('videos'))->assertOk()->assertSeeInOrder(['Featured recording', 'Inaugural lecture'], false);
        $this->assertTrue(Cache::has(CacheKeys::VIDEOS));

        Livewire::actingAs($this->editor)->test(VideoForm::class, ['video' => $video])
            ->assertSet('form.decade', 1970)
            ->assertSet('form.is_featured', true)
            ->set('form.decade', '')
            ->set('form.is_featured', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($video->fresh()->decade);
        $this->assertFalse($video->fresh()->is_featured);
        $this->assertFalse(Cache::has(CacheKeys::VIDEOS));

        Livewire::actingAs($this->editor)->test(VideoForm::class, ['video' => $video])
            ->set('form.decade', '1900')
            ->call('save')
            ->assertHasErrors(['form.decade'])
            ->assertSee('Choose one of the listed decades.');
    }

    #[Test]
    public function editors_file_an_album_photo_under_a_decade_and_feature_it_on_the_wall(): void
    {
        $albums = $this->app->make(AlbumService::class);
        $album = Album::factory()->published()->create(['slug' => 'field']);
        $photo = $albums->addPhoto($album, PlaceholderImage::grey(64, 48), ['alt_text' => 'A print', 'caption' => 'A print', 'place' => 'Khartoum', 'year' => 1985]);
        $id = $photo->getKey();

        $component = Livewire::actingAs($this->editor)->test(AlbumForm::class, ['album' => $album])
            ->assertSee('In the archive')
            ->assertSet("archive.$id.".Archive::DECADE, null)
            ->assertSet("archive.$id.".Archive::FEATURED, false)
            ->set("archive.$id.".Archive::DECADE, '1970')
            ->set("archive.$id.".Archive::FEATURED, true)
            ->call('savePhotoArchive', $id)
            ->assertHasNoErrors()
            ->assertSee('Archive details saved.')
            ->assertSee('Featured');

        $this->assertSame(1970, (int) $photo->fresh()->getAttribute(Archive::DECADE));
        $this->assertTrue((bool) $photo->fresh()->getAttribute(Archive::FEATURED));

        $this->get(route('pictures.show', 'field'))->assertOk()->assertSee('class="wall__item wall__item--featured" style="--span: 8"', false);
        $this->get(route('pictures', ['view' => 'decades']))->assertOk()->assertSee('1970s')->assertDontSee('1980s');

        $component->set("archive.$id.".Archive::DECADE, '1900')
            ->call('savePhotoArchive', $id)
            ->assertHasErrors(["archive.$id.".Archive::DECADE])
            ->assertSee('Choose one of the listed decades.');

        $component->set("archive.$id.".Archive::DECADE, '')
            ->set("archive.$id.".Archive::FEATURED, false)
            ->call('savePhotoArchive', $id)
            ->assertHasNoErrors();

        $this->assertNull($photo->fresh()->getAttribute(Archive::DECADE));
        $this->assertFalse((bool) $photo->fresh()->getAttribute(Archive::FEATURED));
    }

    #[Test]
    public function members_cannot_set_archive_fields(): void
    {
        $album = Album::factory()->create();
        $photo = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(64, 48));

        Livewire::actingAs(User::factory()->create())->test(AlbumForm::class, ['album' => $album])->assertForbidden();

        $this->assertFalse((bool) $photo->fresh()->getAttribute(Archive::FEATURED));
    }
}
