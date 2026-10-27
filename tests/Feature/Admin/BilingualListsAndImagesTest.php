<?php

namespace Tests\Feature\Admin;

use App\Constants\Disks;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Livewire\Admin\Albums\Form as AlbumForm;
use App\Livewire\Admin\Commentaries\Index as CommentaryIndex;
use App\Livewire\Admin\Media\Library;
use App\Models\Album;
use App\Models\Commentary;
use App\Models\Media;
use App\Models\User;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The lists show which records still need Arabic and filter them; image
 * details are written in English and Arabic.
 */
class BilingualListsAndImagesTest extends TestCase
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
    public function rows_missing_arabic_are_marked_and_can_be_filtered(): void
    {
        Commentary::factory()->create(['title' => ['en' => 'Translated', 'ar' => 'مترجمة'], 'summary' => null, 'epigraph' => null, 'body' => null]);
        Commentary::factory()->create(['title' => 'Only English', 'summary' => null, 'epigraph' => null, 'body' => null]);

        $component = Livewire::actingAs($this->editor)->test(CommentaryIndex::class)
            ->assertSee('Translated')
            ->assertSee('Only English')
            ->assertSee('data-needs-arabic', false)
            ->assertSee('Not yet in Arabic: Title')
            ->assertSee('Needs Arabic translation');

        $this->assertSame(1, substr_count($component->html(), 'data-needs-arabic'));

        $component->call('setFilter', ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION, '1')
            ->assertSet('filters', [ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION => '1'])
            ->assertSee('Only English')
            ->assertDontSee('Translated');
    }

    #[Test]
    public function the_list_reads_in_arabic_for_an_arabic_editor(): void
    {
        Commentary::factory()->create(['title' => 'Only English']);

        $this->actingAs(User::factory()->editor()->arabic()->create())
            ->get(route('admin.content.commentary'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee(__('Needs Arabic translation', [], Locales::AR))
            ->assertSee(__('Arabic missing', [], Locales::AR));
    }

    #[Test]
    public function image_details_save_arabic_alt_text_and_publishing_still_needs_the_english(): void
    {
        $album = Album::factory()->draft()->create();
        App::setLocale(Locales::AR);

        $component = Livewire::actingAs($this->editor)->test(AlbumForm::class, ['album' => $album])
            ->set('newImages', [UploadedFile::fake()->image('one.jpg', 40, 30)]);

        $id = array_key_first($component->get('images'));

        $component->assertSet("images.$id.translations.ar.alt_text", null)
            ->set("images.$id.translations.ar.".MediaProperties::ALT_TEXT, ' طلاب في الحقل ')
            ->set("images.$id.translations.ar.".MediaProperties::CAPTION, 'تدريب ميداني')
            ->call('saveImage', $id)
            ->assertHasNoErrors();

        /** @var Media $media */
        $media = Media::query()->findOrFail($id);

        $this->assertSame('طلاب في الحقل', $media->getCustomProperty(MediaProperties::translationPath('ar', MediaProperties::ALT_TEXT)));
        $this->assertSame('طلاب في الحقل', $media->getTranslatedProperty(MediaProperties::ALT_TEXT, 'ar'));
        $this->assertNull($media->getTranslatedProperty(MediaProperties::ALT_TEXT, 'en'));

        // Arabic alone does not make the image publishable, even for an Arabic editor.
        $this->assertContains(MediaProperties::ALT_TEXT, app(MediaService::class)->missingForPublishing($media));

        // Clearing the Arabic forgets it.
        $component->set("images.$id.translations.ar.".MediaProperties::ALT_TEXT, '')
            ->call('saveImage', $id);

        $this->assertNull($media->refresh()->getTranslatedProperty(MediaProperties::ALT_TEXT, 'ar'));
        $this->assertSame('تدريب ميداني', $media->getTranslatedProperty(MediaProperties::CAPTION, 'ar'));
    }

    #[Test]
    public function the_media_library_edits_arabic_details_and_loads_the_english_as_english(): void
    {
        $album = Album::factory()->withPhotos(1)->create();
        $media = Media::query()->sole();
        app(MediaService::class)->updateProperties($media, [
            MediaProperties::ALT_TEXT => 'Students in the field',
            MediaProperties::translationPath('ar', MediaProperties::ALT_TEXT) => 'طلاب في الحقل',
        ]);
        App::setLocale(Locales::AR);

        Livewire::actingAs($this->editor)->test(Library::class)
            ->call('edit', $media->getKey())
            ->assertSet('details.'.MediaProperties::ALT_TEXT, 'Students in the field')
            ->assertSet('details.translations.ar.'.MediaProperties::ALT_TEXT, 'طلاب في الحقل')
            ->set('details.translations.ar.'.MediaProperties::PLACE, 'الخرطوم')
            ->call('saveDetails')
            ->assertHasNoErrors();

        $this->assertSame('الخرطوم', $media->refresh()->getTranslatedProperty(MediaProperties::PLACE, 'ar'));
        $this->assertSame('Students in the field', $media->getTranslatedProperty(MediaProperties::ALT_TEXT, 'en'));
        $this->assertNotNull($album);
    }
}
