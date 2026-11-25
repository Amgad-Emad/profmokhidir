<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminUx;
use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\Pages;
use App\Constants\Preview;
use App\Livewire\Admin\Pages\Edit;
use App\Livewire\Admin\Profile\EditProfile;
use App\Models\Profile;
use App\Models\User;
use App\Services\PageService;
use App\Services\PreviewService;
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
    public function what_is_emptied_in_the_about_editor_leaves_the_page_in_that_language(): void
    {
        Profile::factory()->published()->create([
            'name' => ['en' => 'Mohamed Osman Khidir', 'ar' => 'محمد عثمان خضر'],
            'expertise' => ['en' => ['Agronomy'], 'ar' => ['الزراعة']],
            'key_facts' => ['en' => [['label' => 'Born', 'value' => '1940']], 'ar' => [['label' => 'الميلاد', 'value' => '1940']]],
            'contact_address' => ['en' => 'Khartoum', 'ar' => 'الخرطوم'],
        ]);

        $this->get('/ar/about')->assertOk()->assertSee('الزراعة')->assertSee('الميلاد')->assertSee('الخرطوم');

        Livewire::actingAs($this->editor)->test(EditProfile::class, ['embedded' => true])
            ->set('form.translations.ar.expertise', [])
            ->set('form.translations.ar.key_facts', [])
            ->set('form.translations.ar.contact_address', '')
            ->dispatch(AdminUx::EVENT_PAGE_SAVED)
            ->assertHasNoErrors();

        // Nothing in Arabic, and the English is not shown in its place.
        $body = $this->body('/ar/about');

        foreach (['الزراعة', 'Agronomy', 'الميلاد', 'Born', 'الخرطوم', 'Khartoum', 'data-contact-address'] as $gone) {
            $this->assertStringNotContainsString($gone, $body);
        }

        $this->get('/about')->assertOk()->assertSee('Agronomy')->assertSee('Born')->assertSee('Khartoum');
    }

    #[Test]
    public function the_about_page_contact_details_come_from_its_editor_only(): void
    {
        Profile::factory()->published()->create(['contact_email_public' => null, 'contact_phone' => null, 'contact_address' => null]);

        $this->get('/about')->assertOk()->assertDontSee('data-contact-phone', false)->assertDontSee('data-contact-address', false);

        Livewire::actingAs($this->editor)->test(EditProfile::class, ['embedded' => true])
            ->set('form.contact_email_public', 'family@example.com')
            ->set('form.contact_phone', '+249 12 345 6789')
            ->set('form.contact_address', "Faculty of Agriculture\nKhartoum")
            ->dispatch(AdminUx::EVENT_PAGE_SAVED)
            ->assertHasNoErrors();

        $this->get('/about')->assertOk()
            ->assertSee('mailto:family@example.com', false)
            ->assertSee('href="tel:+249123456789"', false)
            ->assertSee('Faculty of Agriculture');
    }

    /**
     * The About page's own body, without the site header and footer.
     */
    private function body(string $url): string
    {
        $html = $this->get($url)->assertOk()->getContent();
        $start = strpos($html, 'id="about-body"');
        $end = strpos($html, '<footer', (int) $start);

        $this->assertNotFalse($start);

        return substr($html, (int) $start, $end === false ? null : $end - $start);
    }

    #[Test]
    public function the_preview_names_the_editor_section_of_each_region_and_visitors_never_see_it(): void
    {
        Profile::factory()->published()->withContact()->withPortrait()->create([
            'name' => 'Mohamed Osman Khidir',
            'full_bio' => '<p>A biography.</p>',
            'expertise' => ['Agronomy'],
            'key_facts' => [['label' => 'Born', 'value' => '1940']],
        ]);
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::ABOUT])
            ->set('form.title', 'About him')
            ->set('form.expertise_heading', 'Expertise')
            ->call('publish');

        $page = app(PageService::class)->findByKey(Pages::ABOUT);
        $preview = $this->actingAs($this->editor)->get(app(PreviewService::class)->pageUrl($page))->assertOk();

        foreach (['opening', 'portrait', 'identity', 'biography', 'expertise', 'around-the-listing', 'contact'] as $section) {
            $preview->assertSee(Preview::EDIT_SECTION_ATTRIBUTE.'="'.$section.'"', false);
        }

        // The words show as they are typed: each text names the field it comes from.
        foreach (['page:title', 'page:expertise_heading', 'profile:name', 'profile:key_facts.0.label', 'profile:key_facts.0.value', 'profile:contact_email_public', 'profile:contact_note'] as $field) {
            $preview->assertSee(Preview::EDIT_FIELD_ATTRIBUTE.'="'.$field.'"', false);
        }

        // Each one is a section of the About editor.
        $editor = $this->get(route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => Pages::ABOUT]))->assertOk()->assertSee('data-preview-copy', false);
        foreach (['opening', 'portrait', 'identity', 'biography', 'expertise', 'around-the-listing', 'contact'] as $section) {
            $editor->assertSee('id="section-'.$section.'"', false);
        }

        $this->get('/about')->assertOk()->assertDontSee(Preview::EDIT_SECTION_ATTRIBUTE, false)->assertDontSee(Preview::EDIT_FIELD_ATTRIBUTE, false);
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
