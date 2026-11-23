<?php

namespace Tests\Feature\Admin;

use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Livewire\Admin\Profile\EditProfile;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileFormTest extends TestCase
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
    public function the_first_save_creates_the_profile_with_clean_lists_and_sanitised_biography(): void
    {
        Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->assertSet('recordId', null)
            ->call('save')
            ->assertHasErrors(['form.name'])
            ->set('form.name', 'Mohamed Osman Khidir')
            ->set('form.full_bio', '<p onclick="x">Bio</p><script>bad()</script>')
            ->set('form.expertise', ['Agronomy', '   ', 'Soil science'])
            ->call('addKeyFact')->call('addKeyFact')
            ->set('form.key_facts.0.label', 'Born')
            ->set('form.key_facts.0.value', '1940')
            ->set('form.contact_email_public', 'Office@Example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Profile saved.')
            ->assertSet('form.expertise', ['Agronomy', 'Soil science'])
            ->assertSet('form.key_facts', [['label' => 'Born', 'value' => '1940']]);

        $profile = Profile::query()->sole();
        $this->assertSame('<p>Bio</p>', $profile->full_bio);
        $this->assertSame(['Agronomy', 'Soil science'], $profile->expertise);
        $this->assertSame('office@example.com', $profile->contact_email_public);
        $this->assertSame(1, Profile::query()->count());
    }

    #[Test]
    public function later_saves_update_the_same_row_and_the_portrait_is_managed_in_place(): void
    {
        $profile = Profile::factory()->draft()->create(['name' => 'Before']);

        $component = Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->assertSet('recordId', $profile->getKey())
            ->assertSet('form.name', 'Before')
            ->set('form.name', 'After')
            ->call('save')
            ->assertHasNoErrors()
            ->set('newImages', UploadedFile::fake()->image('portrait.jpg', 60, 80))
            ->assertHasNoErrors()
            ->assertSee('Image uploaded. Add its details below.');

        $this->assertSame('After', $profile->fresh()->name);
        $this->assertSame(1, Profile::query()->count());

        $portrait = $profile->fresh()->portrait();
        $this->assertNotNull($portrait);
        $this->assertSame(MediaTreatment::Portrait->value, $portrait->getCustomProperty(MediaProperties::TREATMENT));

        // The portrait details are optional, and the form's own save keeps any typed in.
        $id = $portrait->getKey();
        $component->set('form.status', ContentStatus::Published->value)
            ->set("images.$id.alt_text", 'Portrait of Professor Khidir')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('data-view-on-site', false)
            ->assertSee(route('about'), false);

        $this->assertTrue($profile->fresh()->isPublished());
        $this->assertSame('Portrait of Professor Khidir', $profile->fresh()->portrait()->getCustomProperty(MediaProperties::ALT_TEXT));
    }

    #[Test]
    public function the_portrait_details_are_optional(): void
    {
        $profile = Profile::factory()->draft()->create();

        $component = Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->set('newImages', UploadedFile::fake()->image('portrait.jpg', 60, 80))
            ->assertHasNoErrors()
            ->assertViewHas('detailsRequired', false);

        $id = $profile->fresh()->portrait()->getKey();

        $component->call('saveImage', $id)
            ->assertHasNoErrors();

        $component->set("images.$id.alt_text", 'Portrait of Professor Khidir')
            ->set("images.$id.caption", 'Portrait')
            ->set("images.$id.place", 'Khartoum')
            ->set("images.$id.year", '1990')
            ->call('saveImage', $id)
            ->assertHasNoErrors();

        $this->assertSame('Khartoum', $profile->fresh()->portrait()->getCustomProperty(MediaProperties::PLACE));
    }

    #[Test]
    public function members_cannot_edit_the_profile(): void
    {
        Livewire::actingAs(User::factory()->member()->create())->test(EditProfile::class)->assertForbidden();
    }
}
