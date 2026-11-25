<?php

namespace Tests\Feature\Admin;

use App\Constants\Disks;
use App\Constants\Locales;
use App\Constants\Pages;
use App\Livewire\Admin\Pages\Edit;
use App\Models\User;
use App\Services\PageContentService;
use App\Services\PageService;
use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin › Pages in two languages: each block's Arabic tab, what Arabic pages
 * show when it is filled or left empty, and the "Arabic missing" marks.
 */
class PagesBilingualTest extends TestCase
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
    public function the_arabic_words_are_published_beside_the_english_and_shown_on_arabic_pages(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->assertSee('data-arabic-panel', false)
            ->set('form.hero_label', 'In memory of')
            ->set('arabic.hero_label', 'في ذكرى')
            ->call('publish')
            ->assertHasNoErrors();

        $this->get('/')->assertSee('In memory of')->assertDontSee('في ذكرى');
        $this->get('/ar')->assertSee('في ذكرى')->assertDontSee('In memory of');
    }

    #[Test]
    public function an_empty_arabic_tab_shows_the_built_in_copy_in_arabic_and_changed_english_until_translated(): void
    {
        $this->get('/ar/awards')->assertSee(__('Awards and recognitions will be listed here once they are published.', locale: Locales::AR));

        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::AWARDS])
            ->set('form.empty', 'Honours will be listed soon.')
            ->call('publish');

        $this->get('/ar/awards')->assertSee('Honours will be listed soon.');
        $this->assertTrue(app(PageService::class)->needsArabic(app(PageService::class)->findByKey(Pages::AWARDS)));
        $this->actingAs($this->editor)->get(route(Pages::INDEX_ROUTE))->assertSee('data-needs-arabic', false);
    }

    #[Test]
    public function the_about_editor_offers_no_placeholders_and_its_empty_arabic_shows_nothing(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::ABOUT])
            ->assertDontSee('placeholder="'.e(__('About Prof. Mokhidir', locale: Locales::AR)).'"', false)
            ->assertDontSee('placeholder="About Prof. Mokhidir"', false)
            ->set('form.expertise_heading', '')
            ->call('publish')
            ->assertHasNoErrors()
            ->assertSet('form.expertise_heading', '');
    }

    #[Test]
    public function an_empty_arabic_home_field_shows_nothing_and_no_placeholder(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.hero_label', 'In memory of')
            ->set('form.button_story.label', 'Read his story')
            ->call('publish')
            ->assertHasNoErrors()
            // The Arabic tab offers no fallback wording as a placeholder.
            ->assertDontSee('placeholder="'.e(__('In honour of', locale: Locales::AR)).'"', false)
            ->assertDontSee('placeholder="In memory of"', false)
            ->assertDontSee('placeholder="'.e(__('Read his story', locale: Locales::AR)).'"', false)
            ->assertSee(__('Optional. Left empty, nothing shows on Arabic pages.'));

        // Neither the English words nor a translation of them stand in for the missing Arabic.
        $this->get('/ar')->assertOk()
            ->assertDontSee('In memory of')
            ->assertDontSee(__('In honour of', locale: Locales::AR))
            ->assertDontSee('class="btn btn--primary"', false);
        $this->get('/')->assertSee('In memory of');
    }

    #[Test]
    public function list_items_keep_their_destinations_and_order_with_arabic_words(): void
    {
        $component = Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::NAVIGATION]);
        $english = $component->get('form.chapters');

        $component->set('arabic.chapters.0.label', 'حياته')->call('publish')->assertHasNoErrors();

        app()->setLocale(Locales::AR);
        $chapters = app(PageContentService::class)->items(Pages::NAVIGATION, 'chapters');
        $this->assertSame('حياته', $chapters[0]['label']);
        $this->assertSame(array_column($english, 'key'), array_column($chapters, 'key'));
    }

    #[Test]
    public function the_arabic_tab_has_the_english_limits_and_is_kept_in_drafts_and_revisions(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('arabic.hero_label', str_repeat('ع', 200))
            ->call('save')
            ->assertHasErrors('arabic.hero_label');

        $component = Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('arabic.hero_label', 'مسودة')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('مسودة', $component->get('arabic.hero_label'));
        $this->get('/ar')->assertDontSee('مسودة');

        $fresh = Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME]);
        $this->assertSame('مسودة', $fresh->get('arabic.hero_label'));
    }

    #[Test]
    public function an_arabic_editor_still_edits_english_in_the_english_tab(): void
    {
        $editor = User::factory()->editor()->arabic()->create();

        Livewire::actingAs($editor)->test(Edit::class, ['page' => Pages::HOME])
            ->assertSet('form.hero_label', 'In honour of')
            ->assertSet('arabic.hero_label', '');
    }
}
