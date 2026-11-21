<?php

namespace Tests\Feature\Pages;

use App\Constants\Sections;
use App\Livewire\Admin\Settings\SiteSettings;
use App\Models\User;
use App\Services\SectionVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HiddenSectionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_hidden_section_answers_404_to_visitors_in_every_language(): void
    {
        app(SectionVisibilityService::class)->hide([Sections::AWARDS, Sections::VIDEOS]);

        $this->get('/awards')->assertNotFound();
        $this->get('/ar/awards')->assertNotFound();
        $this->get('/videos/anything')->assertNotFound();
        $this->get('/cv')->assertOk();
    }

    #[Test]
    public function staff_still_open_it_and_are_told_it_is_hidden(): void
    {
        app(SectionVisibilityService::class)->hide([Sections::AWARDS]);

        $this->actingAs(User::factory()->editor()->create())->get('/awards')->assertOk()
            ->assertSee('data-hidden-section-banner', false);
        $this->actingAs(User::factory()->member()->create())->get('/awards')->assertNotFound();
    }

    #[Test]
    public function a_hidden_section_leaves_the_navigation_and_the_sitemap(): void
    {
        $this->get('/cv')->assertOk()->assertSee(url('/awards'), false);

        app(SectionVisibilityService::class)->hide([Sections::AWARDS]);

        $this->get('/cv')->assertOk()->assertDontSee('href="'.url('/awards').'"', false);
        $this->get(route(\App\Constants\Seo::SITEMAP_LOCALE_ROUTE, ['locale' => 'en']))->assertOk()->assertDontSee(url('/awards'));
    }

    #[Test]
    public function hidden_tributes_also_hide_the_header_tribute_button(): void
    {
        $this->get('/cv')->assertSee(url('/tributes/new'), false);

        app(SectionVisibilityService::class)->hide([Sections::TRIBUTES]);

        $this->get('/cv')->assertDontSee(url('/tributes/new'), false);
    }

    #[Test]
    public function an_admin_switches_sections_off_and_on_in_site_settings(): void
    {
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(SiteSettings::class)
            ->assertSet('shown.'.Sections::AWARDS, true)
            ->set('shown.'.Sections::AWARDS, false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([Sections::AWARDS], app(SectionVisibilityService::class)->hidden());

        $component->set('shown.'.Sections::AWARDS, true)->call('save');
        $this->assertSame([], app(SectionVisibilityService::class)->hidden());
    }
}
