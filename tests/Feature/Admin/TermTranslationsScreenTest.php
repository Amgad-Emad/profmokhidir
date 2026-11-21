<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\Locales;
use App\Constants\TermVocabulary;
use App\Livewire\Admin\Terms\Index;
use App\Models\OtherItem;
use App\Models\TermTranslation;
use App\Models\User;
use App\Services\TermTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TermTranslationsScreenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function editors_and_admins_reach_the_screen_and_members_do_not(): void
    {
        $url = route(AdminNavigation::routeFor(AdminNavigation::TERMS));
        $this->assertSame(url('/admin/content/terms'), $url);

        $this->actingAs(User::factory()->editor()->create())->get($url)->assertOk()->assertSee('Arabic names');
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
        $this->actingAs(User::factory()->member()->create())->get($url)->assertForbidden();
    }

    #[Test]
    public function the_screen_lists_terms_in_use_and_saves_their_arabic_names(): void
    {
        OtherItem::factory()->published()->create(['category' => 'Archive', 'item_type' => 'resource']);
        TermTranslation::factory()->naming(TermVocabulary::OtherType, 'resource', 'مصدر')->create();

        $component = Livewire::actingAs(User::factory()->editor()->create())->test(Index::class)
            ->assertSee('Archive')
            ->assertSee('Resource')
            ->assertSet('rows', [
                ['vocabulary' => TermVocabulary::OtherCategory->value, 'term' => 'Archive', 'label' => ''],
                ['vocabulary' => TermVocabulary::OtherType->value, 'term' => 'resource', 'label' => 'مصدر'],
            ]);

        $component->set('rows.0.label', 'الأرشيف')->set('rows.1.label', '')->call('save')->assertHasNoErrors();

        $terms = app(TermTranslationService::class);
        $this->assertSame('الأرشيف', $terms->label(TermVocabulary::OtherCategory, 'Archive', Locales::AR));
        $this->assertSame('Resource', $terms->label(TermVocabulary::OtherType, 'resource', Locales::AR));
    }

    #[Test]
    public function names_longer_than_the_limit_are_refused(): void
    {
        OtherItem::factory()->published()->create(['category' => 'Archive']);

        Livewire::actingAs(User::factory()->editor()->create())->test(Index::class)
            ->set('rows.0.label', str_repeat('ا', 256))
            ->call('save')
            ->assertHasErrors('rows.0.label');
    }
}
