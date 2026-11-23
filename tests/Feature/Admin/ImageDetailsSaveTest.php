<?php

namespace Tests\Feature\Admin;

use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Livewire\Admin\Awards\Form;
use App\Models\Award;
use App\Models\Media;
use App\Models\User;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Services\AwardService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageDetailsSaveTest extends TestCase
{
    use RefreshDatabase;

    private string $arCaption;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->arCaption = MediaProperties::translationPath('ar', MediaProperties::CAPTION);
    }

    #[Test]
    public function the_award_update_saves_edited_image_details_in_both_languages(): void
    {
        $award = Award::factory()->published()->create();
        $id = app(AwardService::class)->addImage($award, PlaceholderImage::grey(40, 30), [MediaProperties::CAPTION => 'Shield'])->getKey();

        Livewire::actingAs(User::factory()->editor()->create())->test(Form::class, ['award' => $award])
            ->set("images.$id.".MediaProperties::YEAR, null)
            ->set("images.$id.".MediaProperties::CAPTION, 'Anniversary shield')
            ->set("images.$id.".$this->arCaption, 'درع الذكرى')
            ->call('publish')
            ->assertHasNoErrors();

        $media = Media::query()->findOrFail($id);
        $this->assertSame('Anniversary shield', $media->getTranslatedProperty(MediaProperties::CAPTION, 'en'));
        $this->assertSame('درع الذكرى', $media->getTranslatedProperty(MediaProperties::CAPTION, 'ar'));
    }

    #[Test]
    public function an_image_stored_with_flat_translation_keys_takes_arabic_edits_and_is_repaired(): void
    {
        $award = Award::factory()->published()->create();
        $id = app(AwardService::class)->addImage($award, PlaceholderImage::grey(40, 30), [MediaProperties::CAPTION => 'Shield'])->getKey();

        // As older imports stored it: a literal "translations.ar.caption" key.
        DB::table('media')->where('id', $id)->update(['custom_properties' => json_encode([
            MediaProperties::CAPTION => 'Shield',
            $this->arCaption => 'درع قديم',
        ])]);

        $this->assertSame(1, app(MediaRepositoryInterface::class)->foldTranslationKeys());
        $stored = json_decode((string) DB::table('media')->where('id', $id)->value('custom_properties'), true);
        $this->assertArrayNotHasKey($this->arCaption, $stored);
        $this->assertSame('درع قديم', $stored[MediaProperties::TRANSLATIONS]['ar'][MediaProperties::CAPTION]);

        // Shadowed again, then edited through the form: the edit is what reads back.
        DB::table('media')->where('id', $id)->update(['custom_properties' => json_encode([
            MediaProperties::CAPTION => 'Shield',
            $this->arCaption => 'درع قديم',
        ])]);

        Livewire::actingAs(User::factory()->editor()->create())->test(Form::class, ['award' => $award])
            ->set("images.$id.".$this->arCaption, 'درع جديد')
            ->call('saveImage', $id)
            ->assertHasNoErrors();

        $this->assertSame('درع جديد', Media::query()->findOrFail($id)->getTranslatedProperty(MediaProperties::CAPTION, 'ar'));

        // Clearing it does not bring the old text back.
        Livewire::actingAs(User::factory()->editor()->create())->test(Form::class, ['award' => $award])
            ->set("images.$id.".$this->arCaption, '')
            ->call('saveImage', $id);

        $this->assertNull(Media::query()->findOrFail($id)->getTranslatedProperty(MediaProperties::CAPTION, 'ar'));
    }

    #[Test]
    public function new_uploads_store_translations_nested(): void
    {
        $award = Award::factory()->create();
        $media = app(AwardService::class)->addImage($award, PlaceholderImage::grey(40, 30), [$this->arCaption => 'درع']);

        $stored = json_decode((string) DB::table('media')->where('id', $media->getKey())->value('custom_properties'), true);
        $this->assertArrayNotHasKey($this->arCaption, $stored);
        $this->assertSame('درع', $stored[MediaProperties::TRANSLATIONS]['ar'][MediaProperties::CAPTION]);
    }
}
