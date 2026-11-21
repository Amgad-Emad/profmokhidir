<?php

namespace Tests\Feature\Admin;

use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Livewire\Admin\Awards\Form as AwardForm;
use App\Models\Award;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Editors add photographs of a certificate, medal or shield to an award in
 * Admin › Awards; the roll of honour shows them.
 */
class AwardImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function a_new_award_explains_that_images_come_after_the_first_save(): void
    {
        Livewire::actingAs(User::factory()->editor()->create())->test(AwardForm::class)
            ->assertSee('Images')
            ->assertSee('Images can be added once the award has a name and has been saved once.');
    }

    #[Test]
    public function editors_upload_describe_and_remove_award_images_and_the_roll_follows(): void
    {
        $editor = User::factory()->editor()->create();
        $award = Award::factory()->published()->create(['name' => 'Order of Merit']);

        $component = Livewire::actingAs($editor)->test(AwardForm::class, ['award' => $award])
            ->set('newImages', [UploadedFile::fake()->image('certificate.jpg', 40, 30)])
            ->assertHasNoErrors();

        $image = $award->fresh()->getMedia(MediaCollections::AWARD_IMAGES)->sole();

        Cache::put(CacheKeys::AWARDS, 'stale');
        $component
            ->set("images.{$image->id}.".MediaProperties::ALT_TEXT, 'The framed certificate of the Order of Merit')
            ->set("images.{$image->id}.".MediaProperties::CAPTION, 'Certificate of the Order of Merit')
            ->set("images.{$image->id}.".MediaProperties::PLACE, 'Khartoum')
            ->set("images.{$image->id}.".MediaProperties::YEAR, 2002)
            ->call('saveImage', $image->id)
            ->assertHasNoErrors();
        $this->assertFalse(Cache::has(CacheKeys::AWARDS), 'Saving image details clears the cached roll');

        $this->get(route('awards'))->assertOk()->assertSee('alt="The framed certificate of the Order of Merit"', false);

        $component->call('confirmRemoveImage', $image->id)->call('removeImage');

        $this->assertCount(0, $award->fresh()->getMedia(MediaCollections::AWARD_IMAGES));
        $this->get(route('awards'))->assertOk()->assertDontSee('honour__exhibit', false);
    }

    #[Test]
    public function members_cannot_open_the_award_form(): void
    {
        $award = Award::factory()->published()->create();

        Livewire::actingAs(User::factory()->member()->create())->test(AwardForm::class, ['award' => $award])
            ->assertForbidden();
    }
}
