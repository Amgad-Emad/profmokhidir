<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminUx;
use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\Pages;
use App\Livewire\Admin\Pages\Edit;
use App\Livewire\Admin\Profile\EditProfile;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin › Pages › About holds everything the About page shows: its own words and
 * the profile (name, biography, expertise tags, key facts, portrait, contact),
 * saved and published with the page's save bar.
 */
class AboutPageProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->seed(PageContentSeeder::class);
        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function the_about_editor_holds_the_profile_sections_in_place_of_a_link_to_them(): void
    {
        Profile::factory()->draft()->create(['name' => 'Mohamed Osman Khidir', 'expertise' => ['en' => ['Agronomy'], 'ar' => ['الزراعة']]]);

        $html = $this->actingAs($this->editor)
            ->get(route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => Pages::ABOUT]))
            ->assertOk()
            ->assertSee('data-profile-embedded', false)
            ->assertSee('data-expertise="en"', false)
            ->assertSee('data-expertise="ar"', false)
            ->assertSee('data-key-facts="en"', false)
            ->assertSee('id="section-portrait"', false)
            ->assertSee('id="section-contact"', false)
            ->assertDontSee('data-page-records=', false)
            ->getContent();

        // The profile follows the page's opening and comes before the headings around the listing.
        $this->assertLessThan(strpos($html, 'id="section-identity"'), strpos($html, 'id="section-opening"'));
        $this->assertLessThan(strpos($html, 'id="section-around-the-listing"'), strpos($html, 'id="section-contact"'));
    }

    #[Test]
    public function other_pages_do_not_embed_the_profile(): void
    {
        $this->actingAs($this->editor)
            ->get(route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => Pages::HOME]))
            ->assertOk()
            ->assertDontSee('data-profile-embedded', false);
    }

    #[Test]
    public function saving_the_about_page_asks_the_profile_to_save_and_publishing_it_to_publish(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::ABOUT])
            ->call('save')
            ->assertDispatchedTo(EditProfile::class, AdminUx::EVENT_PAGE_SAVED)
            ->call('publish')
            ->assertDispatchedTo(EditProfile::class, AdminUx::EVENT_PAGE_PUBLISHED);

        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->call('save')
            ->assertNotDispatched(AdminUx::EVENT_PAGE_SAVED);
    }

    #[Test]
    public function the_embedded_profile_saves_its_tags_in_both_languages_with_the_page(): void
    {
        $profile = Profile::factory()->draft()->create(['name' => 'Mohamed Osman Khidir']);

        Livewire::actingAs($this->editor)->test(EditProfile::class, ['embedded' => true])
            ->set('form.expertise', ['Agronomy', ' Soil science '])
            ->set('form.translations.ar.expertise', ['الزراعة'])
            ->dispatch(AdminUx::EVENT_PAGE_SAVED)
            ->assertHasNoErrors();

        $profile->refresh();
        $this->assertSame(['Agronomy', 'Soil science'], $profile->getTranslation('expertise', 'en', false));
        $this->assertSame(['الزراعة'], $profile->getTranslation('expertise', 'ar', false));
        $this->assertSame(ContentStatus::Draft, $profile->status);
    }

    #[Test]
    public function the_embedded_profile_ignores_page_saves_until_it_is_started_and_never_autosaves(): void
    {
        Livewire::actingAs($this->editor)->test(EditProfile::class, ['embedded' => true])
            ->dispatch(AdminUx::EVENT_PAGE_SAVED)
            ->assertHasNoErrors()
            ->set('form.name', 'Mohamed Osman Khidir')
            ->call('autosave');

        $this->assertSame(0, Profile::query()->count());
    }

    #[Test]
    public function the_profile_screen_alone_ignores_the_page_events(): void
    {
        $profile = Profile::factory()->draft()->create(['name' => 'Before']);

        Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->set('form.name', 'After')
            ->dispatch(AdminUx::EVENT_PAGE_SAVED);

        $this->assertSame('Before', $profile->refresh()->name);
    }
}
