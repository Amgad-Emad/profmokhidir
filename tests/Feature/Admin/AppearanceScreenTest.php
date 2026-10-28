<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\Appearance as AppearanceSettings;
use App\Constants\AuditAction;
use App\Constants\ThemePresets;
use App\Livewire\Admin\Settings\Appearance;
use App\Models\AuditLog;
use App\Models\ColourTheme;
use App\Models\User;
use App\Services\ColourThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin › Appearance: who may open it, the template cards, editing a theme's
 * colours (validation, reset, the contrast guard's Fix), saving with and
 * without failures, publishing (blocked on failing body text, links or
 * buttons), duplicate, rename, delete, export, import, history and
 * "Restore brand colours".
 */
class AppearanceScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function themes(): ColourThemeService
    {
        return app(ColourThemeService::class);
    }

    private function custom(array $attributes = []): ColourTheme
    {
        $this->themes()->installPresets();

        return ColourTheme::factory()->create($attributes);
    }

    #[Test]
    public function the_screen_is_in_the_settings_group_for_admins_only(): void
    {
        $item = AdminNavigation::get(AdminNavigation::APPEARANCE);
        $this->assertSame(AdminNavigation::GROUP_SETTINGS, $item['group']);

        $this->actingAs($this->admin)->get(route($item['route']))
            ->assertOk()
            ->assertSee(__('Appearance'))
            ->assertSee(__(AppearanceSettings::BRAND_NOTE))
            ->assertSee(__(AppearanceSettings::ALTERNATIVES_LABEL))
            ->assertSee(__(AppearanceSettings::RECOMMENDED_LABEL))
            ->assertSee('data-restore-brand', false);

        $this->actingAs(User::factory()->editor()->create())->get(route($item['route']))->assertForbidden();
        $this->actingAs(User::factory()->member()->create())->get(route($item['route']))->assertForbidden();

        auth()->logout();
        $this->get(route($item['route']))->assertRedirect(route('login'));
    }

    #[Test]
    public function every_template_is_shown_as_a_card_with_light_and_dark_miniatures(): void
    {
        $html = Livewire::actingAs($this->admin)->test(Appearance::class)->html();

        foreach (ThemePresets::all() as $slug => $preset) {
            $this->assertStringContainsString('data-preset="'.$slug.'"', $html);
            $this->assertStringContainsString('data-apply="'.$slug.'"', $html);
            $this->assertStringContainsString('data-duplicate="'.$slug.'"', $html);
            $this->assertStringContainsString(e(__($preset['name'])), $html);
        }

        $this->assertSame(count(ThemePresets::all()) * 2, substr_count($html, 'class="theme-mini"'));
    }

    #[Test]
    public function a_template_is_read_only_until_duplicated(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Appearance::class);
        $brand = $this->themes()->brand();

        $component->assertSet('themeId', $brand->id)
            ->assertSee(__('Templates are read-only. Duplicate this one to change its colours; the copy keeps a link to it, so any colour can be reset to the template.'))
            ->call('setColour', 'surface', '#ffffff')
            ->assertForbidden();

        $component = Livewire::actingAs($this->admin)->test(Appearance::class)->call('duplicate', $brand->id);
        $copy = ColourTheme::query()->where('is_preset', false)->sole();

        $component->assertSet('themeId', $copy->id)->assertSet('name', $copy->name);
        $this->assertSame(ThemePresets::BRAND, $copy->based_on);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::THEME_CREATED)->exists());
    }

    #[Test]
    public function a_colour_must_be_a_hex_value_of_a_known_token(): void
    {
        $theme = $this->custom();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'surface', 'red')
            ->assertHasErrors('colour.surface')
            ->call('setColour', 'not-a-token', '#ffffff')
            ->assertHasErrors('colour.not-a-token')
            ->call('setColour', 'surface', '#FAF7EE')
            ->assertHasNoErrors('colour.surface')
            ->assertSet('light.surface', '#faf7ee')
            ->assertSet('dirty', true);
    }

    #[Test]
    public function the_mark_ring_takes_only_a_brass_like_tone_of_the_palette(): void
    {
        $theme = $this->custom();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'mark-ring', '#ff0000')
            ->assertHasErrors('colour.mark-ring')
            ->call('setColour', 'mark-ring', '#a5813f')
            ->assertHasNoErrors('colour.mark-ring')
            ->assertSet('light.mark-ring', '#a5813f');
    }

    #[Test]
    public function saving_a_draft_with_failing_pairs_asks_for_save_anyway(): void
    {
        $theme = $this->custom();

        $component = Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'accent-text', '#d8cfb8')
            ->call('saveDraft')
            ->assertSet('confirming', Appearance::CONFIRM_SAVE)
            ->assertSee(__('Save with contrast failures?'));

        $this->assertNull($theme->refresh()->draft_light);

        $component->call('saveDraft', true)->assertSet('confirming', null)->assertSet('dirty', false);
        $this->assertSame('#d8cfb8', $theme->refresh()->draft_light['accent-text']);
        $this->assertFalse($theme->is_active);
    }

    #[Test]
    public function publishing_is_blocked_while_body_text_links_or_buttons_fail(): void
    {
        $theme = $this->custom();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'ink-body', '#e9e2cf')
            ->assertSee('data-blocking', false)
            ->call('publish', true)
            ->assertDispatched(\App\Constants\AdminUx::EVENT_TOAST);

        $this->assertFalse($theme->refresh()->is_active);
        $this->assertNull(ColourTheme::query()->where('is_active', true)->first());
    }

    #[Test]
    public function fix_sets_the_nearest_passing_shade(): void
    {
        $theme = $this->custom();

        $component = Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'ink-body', '#e9e2cf')
            ->assertSee('data-fix="light.body"', false)
            ->call('fix', 'light', 'body');

        $this->assertNotSame('#e9e2cf', $component->get('light')['ink-body']);
        $component->assertDontSee('data-fix="light.body"', false);
    }

    #[Test]
    public function publishing_puts_the_draft_live_and_records_who_changed_what(): void
    {
        $theme = $this->custom();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'surface', '#f7f2e6')
            ->call('publish');

        $theme->refresh();
        $this->assertTrue($theme->is_active);
        $this->assertSame('#f7f2e6', $theme->light['surface']);
        $this->assertNull($theme->draft_light);

        $log = AuditLog::query()->where('action', AuditAction::THEME_PUBLISHED)->sole();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(['#f4efe3', '#f7f2e6'], $log->meta['changes']['light']['surface']);
    }

    #[Test]
    public function apply_puts_a_template_live_and_restore_brand_colours_brings_the_brand_back(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Appearance::class);
        $burgundy = ColourTheme::query()->where('slug', ThemePresets::BURGUNDY)->sole();

        $component->call('apply', $burgundy->id);
        $this->assertTrue($burgundy->refresh()->is_active);

        $component->call('restoreBrand');
        $this->assertFalse($burgundy->refresh()->is_active);
        $this->assertTrue($this->themes()->brand()->is_active);
    }

    #[Test]
    public function reset_to_template_returns_a_token_to_its_template_or_to_derived(): void
    {
        $theme = $this->custom();

        $component = Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'link', '#14213d')
            ->assertSee('data-reset-token="link"', false)
            ->call('resetToken', 'link');

        $this->assertArrayNotHasKey('link', $component->get('light'));

        $component->call('setColour', 'link', '#14213d')->call('setColour', 'card-surface', '#eeeeee')->call('resetAdvanced');
        $this->assertSame(['surface', 'surface-raised', 'primary', 'accent-line', 'ink', 'danger'], array_keys(array_intersect_key(ThemePresets::get(ThemePresets::OXFORD)['light'], $component->get('light'))));
        $this->assertArrayNotHasKey('card-surface', $component->get('light'));
    }

    #[Test]
    public function a_custom_theme_can_be_renamed_and_deleted_but_a_template_cannot(): void
    {
        $theme = $this->custom(['is_active' => true]);

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->set('name', '')
            ->call('rename')
            ->assertHasErrors('name')
            ->set('name', 'Library evening')
            ->call('rename')
            ->assertHasNoErrors();

        $this->assertSame('Library evening', $theme->refresh()->name);

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('askDelete')
            ->assertSet('confirming', Appearance::CONFIRM_DELETE)
            ->assertSee('data-delete-theme-dialog', false)
            ->call('delete');

        $this->assertModelMissing($theme);
        $this->assertTrue($this->themes()->brand()->is_active, 'deleting the live theme brings the brand back');

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $this->themes()->brand()->id)
            ->call('askDelete')
            ->assertForbidden();
    }

    #[Test]
    public function a_theme_exports_as_json_and_imports_as_a_new_theme(): void
    {
        $theme = $this->custom(['name' => 'Night reading']);

        $component = Livewire::actingAs($this->admin)->test(Appearance::class)->call('select', $theme->id)->call('export');
        $component->assertFileDownloaded($theme->slug.'.json');

        $data = $this->themes()->export($theme, $this->admin);
        $file = UploadedFile::fake()->createWithContent('night.json', json_encode($data));

        Livewire::actingAs($this->admin)->test(Appearance::class)->set('importFile', $file)->assertHasNoErrors();

        $imported = ColourTheme::query()->where('name', 'Night reading')->whereKeyNot($theme->id)->sole();
        $this->assertEquals($this->themes()->resolve($theme), $this->themes()->resolve($imported));
    }

    #[Test]
    public function an_import_with_unknown_keys_or_invalid_colours_is_refused(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.json', json_encode([
            'format' => AppearanceSettings::EXPORT_FORMAT, 'version' => AppearanceSettings::EXPORT_VERSION, 'name' => 'Bad',
            'light' => ['surface' => '#ffffff', 'sparkle' => '#ff00ff'], 'dark' => ['surface' => 'blue'],
        ]));

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->set('importFile', $file)
            ->assertHasErrors('importFile')
            ->assertSee('sparkle');

        $this->assertFalse(ColourTheme::query()->where('name', 'Bad')->exists());

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('broken.json', '{not json'))
            ->assertHasErrors('importFile');
    }

    #[Test]
    public function an_earlier_version_can_be_loaded_back_from_the_history(): void
    {
        $theme = $this->custom();
        $this->themes()->saveDraft($theme, [...$theme->light, 'surface' => '#f1ead9'], $theme->dark, $this->admin, acceptFailures: true);
        $first = $this->themes()->history($theme)->first();
        $this->themes()->saveDraft($theme, [...$theme->light, 'surface' => '#efe6d2'], $theme->dark, $this->admin, acceptFailures: true);

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('toggleHistory')
            ->assertSee('data-restore-revision="'.$first->id.'"', false)
            ->call('restoreRevision', $first->id)
            ->assertSet('light.surface', '#f1ead9');

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::THEME_REVISION_RESTORED)->exists());
    }

    #[Test]
    public function the_live_preview_carries_the_unsaved_colours_and_the_chosen_page(): void
    {
        $theme = $this->custom();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $theme->id)
            ->call('setColour', 'surface', '#faf3e1')
            ->call('setMode', 'dark')
            ->call('setPreviewPage', 'about')
            ->assertSee('data-mode="dark"', false)
            ->assertSee('data-url="'.url('/about').'"', false)
            ->assertSee('--surface:light-dark(#faf3e1,', false);
    }
}
