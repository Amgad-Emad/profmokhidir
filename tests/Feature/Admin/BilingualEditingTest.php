<?php

namespace Tests\Feature\Admin;

use App\Constants\ContentTypes;
use App\Constants\Locales;
use App\Livewire\Admin\Commentaries\Form as CommentaryForm;
use App\Livewire\Admin\Profile\EditProfile;
use App\Livewire\Admin\Publications\Form as PublicationForm;
use App\Models\Commentary;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The edit screens keep English and Arabic side by side: English under the
 * field names, Arabic under form.translations.ar, each read without fallback
 * whatever language the editor uses the dashboard in.
 */
class BilingualEditingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_commentary_saves_english_and_arabic_and_reloads_them_in_their_tabs_for_an_arabic_editor(): void
    {
        $editor = User::factory()->editor()->arabic()->create();
        App::setLocale(Locales::AR);

        Livewire::actingAs($editor)->test(CommentaryForm::class)
            ->assertSee('data-locale-tabs', false)
            ->set('form.title', 'On teaching')
            ->set('form.summary', 'Why teaching mattered.')
            ->set('form.body', '<p>English text</p>')
            ->set('form.translations.ar.title', 'في التدريس')
            ->set('form.translations.ar.summary', '')
            ->set('form.translations.ar.body', '<p>نص عربي</p><img src="x">')
            ->call('save')
            ->assertHasNoErrors();

        $commentary = Commentary::query()->sole();

        $this->assertSame('On teaching', $commentary->getTranslation('title', 'en', false));
        $this->assertSame('في التدريس', $commentary->getTranslation('title', 'ar', false));
        $this->assertSame('<p>نص عربي</p>', $commentary->getTranslation('body', 'ar', false), 'Arabic rich text is sanitised too.');
        $this->assertSame('', $commentary->getTranslation('summary', 'ar', false), 'Empty Arabic is not stored.');
        $this->assertSame('on-teaching', $commentary->slug, 'The address comes from the English title.');

        // Empty Arabic falls back to English for Arabic visitors.
        $this->assertSame('Why teaching mattered.', $commentary->summary);

        Livewire::actingAs($editor)->test(CommentaryForm::class, ['commentary' => $commentary])
            ->assertSet('form.title', 'On teaching')
            ->assertSet('form.summary', 'Why teaching mattered.')
            ->assertSet('form.translations.ar.title', 'في التدريس')
            ->assertSet('form.translations.ar.summary', null);
    }

    #[Test]
    public function arabic_is_optional_but_checked_against_the_english_limits(): void
    {
        $commentary = Commentary::factory()->create(['title' => 'On teaching']);

        Livewire::actingAs(User::factory()->editor()->create())->test(CommentaryForm::class, ['commentary' => $commentary])
            ->set('form.translations.ar.title', str_repeat('ب', 256))
            ->call('save')
            ->assertHasErrors(['form.translations.ar.title' => 'max'])
            ->set('form.translations.ar.title', '')
            ->call('save')
            ->assertHasNoErrors();
    }

    #[Test]
    public function a_publication_keeps_its_arabic_when_only_the_english_changes(): void
    {
        $publication = Publication::factory()->create(['title' => ['en' => 'Dryland agronomy', 'ar' => 'زراعة الأراضي الجافة'], 'year' => 1975]);

        Livewire::actingAs(User::factory()->editor()->create())->test(PublicationForm::class, ['publication' => $publication])
            ->assertSet('form.title', 'Dryland agronomy')
            ->assertSet('form.translations.ar.title', 'زراعة الأراضي الجافة')
            ->set('form.title', 'Principles of dryland agronomy')
            ->set('form.translations.ar.abstract', 'ملخص البحث')
            ->call('save')
            ->assertHasNoErrors();

        $publication->refresh();

        $this->assertEquals(['en' => 'Principles of dryland agronomy', 'ar' => 'زراعة الأراضي الجافة'], $publication->getTranslations('title'));
        $this->assertSame('ملخص البحث', $publication->getTranslation('abstract', 'ar', false));
        $this->assertSame(1975, $publication->year);
    }

    #[Test]
    public function the_profile_edits_arabic_lists_of_expertise_and_key_facts(): void
    {
        Profile::factory()->create();
        App::setLocale(Locales::AR);

        Livewire::actingAs(User::factory()->editor()->arabic()->create())->test(EditProfile::class)
            ->assertSet('form.expertise', ['Agronomy', 'Soil fertility', 'Crop physiology'])
            ->assertSet('form.translations.ar.expertise', [])
            ->set('form.translations.ar.name', 'محمد عثمان خضر')
            ->set('form.translations.ar.expertise', ['علم المحاصيل', ' ', 'خصوبة التربة'])
            ->call('addKeyFact', 'ar')
            ->set('form.translations.ar.key_facts.0.label', 'الميلاد')
            ->set('form.translations.ar.key_facts.0.value', '1940')
            ->call('addKeyFact', 'ar')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.translations.ar.key_facts', [['label' => 'الميلاد', 'value' => '1940']]);

        $profile = Profile::query()->sole();

        $this->assertSame('Mohamed Osman Khidir', $profile->getTranslation('name', 'en', false));
        $this->assertSame('محمد عثمان خضر', $profile->getTranslation('name', 'ar', false));
        $this->assertSame(['علم المحاصيل', 'خصوبة التربة'], $profile->getTranslation('expertise', 'ar', false));
        $this->assertSame(['Agronomy', 'Soil fertility', 'Crop physiology'], $profile->getTranslation('expertise', 'en', false));
        $this->assertSame([['label' => 'الميلاد', 'value' => '1940']], $profile->getTranslation('key_facts', 'ar', false));
        $this->assertCount(2, $profile->getTranslation('key_facts', 'en', false));
    }

    #[Test]
    public function revisions_keep_the_arabic_and_bring_it_back(): void
    {
        $commentary = Commentary::factory()->create(['title' => ['en' => 'On teaching', 'ar' => 'في التدريس']]);
        $editor = User::factory()->editor()->create();

        $component = Livewire::actingAs($editor)->test(CommentaryForm::class, ['commentary' => $commentary])
            ->call('save');

        $first = Revision::query()->latest('id')->firstOrFail();
        $this->assertSame('في التدريس', $first->data['translations']['ar']['title']);

        $component->set('form.translations.ar.title', 'عنوان آخر')
            ->call('save')
            ->call('restoreRevision', $first->getKey())
            ->assertSet('form.translations.ar.title', 'في التدريس');
    }

    #[Test]
    public function an_autosave_stores_the_arabic_draft(): void
    {
        $commentary = Commentary::factory()->draft()->create(['title' => 'On teaching']);

        Livewire::actingAs(User::factory()->editor()->create())->test(CommentaryForm::class, ['commentary' => $commentary])
            ->set('form.translations.ar.title', 'في التدريس')
            ->call('autosave');

        $this->assertSame('في التدريس', $commentary->refresh()->getTranslation('title', 'ar', false));
    }

    #[Test]
    public function the_edit_screen_is_in_arabic_for_an_arabic_editor_with_the_english_tab_left_to_right(): void
    {
        $commentary = Commentary::factory()->create(['title' => 'On teaching']);

        $this->actingAs(User::factory()->editor()->arabic()->create())
            ->get(route(ContentTypes::editRoute(ContentTypes::COMMENTARY), ['commentary' => $commentary]))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee(__('Edit commentary', [], 'ar'))
            ->assertSee('role="tablist"', false)
            ->assertSee('id="opening-text-panel-en" aria-labelledby="opening-text-tab-en" lang="en" dir="ltr"', false)
            ->assertSee('id="opening-text-panel-ar" aria-labelledby="opening-text-tab-ar" lang="ar" dir="rtl"', false)
            ->assertSee('العربية');
    }
}
