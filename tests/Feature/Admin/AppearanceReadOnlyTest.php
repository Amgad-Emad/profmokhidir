<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\ThemePresets;
use App\Constants\ThemeTokens;
use App\Livewire\Admin\Settings\Appearance;
use App\Models\ColourTheme;
use App\Models\User;
use App\Services\ColourThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * With colour editing off (the default), Admin › Appearance still lists every
 * theme, shows all of a theme's colours and lets an admin apply one, but no
 * colour can be changed and no theme added, on the screen or on the server.
 */
class AppearanceReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        app(ColourThemeService::class)->installPresets();
    }

    private function alternative(): ColourTheme
    {
        return ColourTheme::query()->where('is_preset', true)->where('slug', '!=', ThemePresets::BRAND)->orderBy('id')->firstOrFail();
    }

    #[Test]
    public function editing_is_off_by_default(): void
    {
        $this->assertFalse(config('appearance.editing'));
        $this->assertFalse(Appearance::editingEnabled());
    }

    #[Test]
    public function every_template_and_all_the_colours_are_shown_without_edit_or_add_controls(): void
    {
        $item = AdminNavigation::get(AdminNavigation::APPEARANCE);

        $response = $this->actingAs($this->admin)->get(route($item['route']))->assertOk();

        $response->assertSee('data-appearance-templates', false)
            ->assertSee('data-restore-brand', false)
            ->assertSee('data-advanced', false)
            ->assertSee('data-read-only', false)
            ->assertSee('Themes cannot be edited or added here.')
            ->assertSee('Apply to the site');

        foreach (ColourTheme::query()->where('is_preset', true)->pluck('slug') as $slug) {
            $response->assertSee('data-preset="'.$slug.'"', false)
                ->assertSee('data-apply="'.$slug.'"', false)
                ->assertSee('data-inspect="'.$slug.'"', false);
        }

        foreach ([...ThemeTokens::basic(), ...ThemeTokens::inGroup(ThemeTokens::GROUP_TEXT)] as $token) {
            $response->assertSee('data-token="'.$token.'"', false);
        }

        foreach (['data-duplicate=', 'data-duplicate-current', 'data-import', 'Import JSON', 'data-rename', 'data-save-draft', 'data-reset-advanced', 'data-reset-token', 'data-edit-theme', 'Duplicate to edit'] as $control) {
            $response->assertDontSee($control, false);
        }

        preg_match_all('/<input type="color"[^>]*>/', $response->getContent(), $pickers);
        $this->assertNotEmpty($pickers[0]);
        foreach ($pickers[0] as $picker) {
            $this->assertStringContainsString('disabled', $picker, 'Every colour picker is disabled');
        }
    }

    #[Test]
    public function an_admin_can_inspect_and_apply_another_template_and_restore_the_brand(): void
    {
        $other = $this->alternative();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call('select', $other->id)
            ->assertSet('themeId', $other->id)
            ->call('setMode', 'dark')
            ->call('apply', $other->id)
            ->assertHasNoErrors();

        $this->assertSame($other->slug, app(ColourThemeService::class)->active()?->slug);

        Livewire::actingAs($this->admin)->test(Appearance::class)->call('restoreBrand');

        $this->assertSame(ThemePresets::BRAND, app(ColourThemeService::class)->active()?->slug);
    }

    #[Test]
    public function an_earlier_custom_theme_is_listed_for_inspection_and_can_be_applied(): void
    {
        $themes = app(ColourThemeService::class);
        $own = $themes->duplicate($themes->brand(), $this->admin);
        $themes->rename($own, 'Nile evening', $this->admin);

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->assertSee('Nile evening')
            ->assertSee('data-inspect-theme="'.$own->id.'"', false)
            ->assertDontSee('data-edit-theme', false)
            ->call('select', $own->id)
            ->assertSee('data-read-only', false)
            ->assertDontSee('data-rename', false)
            ->call('publish');

        $this->assertSame($own->id, $themes->active()?->id);
    }

    /**
     * @return array<string, array{0: string, 1: list<mixed>}>
     */
    public static function refusedActions(): array
    {
        return [
            'set a colour' => ['setColour', ['canvas', '#000000']],
            'reset a colour' => ['resetToken', ['canvas']],
            'reset the advanced colours' => ['resetAdvanced', []],
            'apply a contrast fix' => ['fix', ['light', 'body']],
            'save a draft' => ['saveDraft', []],
            'duplicate' => ['duplicate', []],
            'rename' => ['rename', []],
            'load an earlier version' => ['restoreRevision', [1]],
        ];
    }

    /**
     * @param  list<mixed>  $arguments
     */
    #[Test]
    #[DataProvider('refusedActions')]
    public function editing_and_adding_are_refused_on_the_server(string $method, array $arguments): void
    {
        $snapshot = fn (): array => ColourTheme::query()->orderBy('id')->get()->map(fn (ColourTheme $theme): array => $theme->attributesToArray())->all();
        $before = $snapshot();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->call($method, ...$arguments)
            ->assertForbidden();

        $this->assertSame($before, $snapshot(), 'No theme was added or changed');
    }

    #[Test]
    public function an_import_is_refused(): void
    {
        $before = ColourTheme::query()->count();

        Livewire::actingAs($this->admin)->test(Appearance::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('theme.json', '{}'))
            ->assertForbidden();

        $this->assertSame($before, ColourTheme::query()->count());
    }

    #[Test]
    public function editors_still_cannot_open_the_screen(): void
    {
        $item = AdminNavigation::get(AdminNavigation::APPEARANCE);

        $this->actingAs(User::factory()->editor()->create())->get(route($item['route']))->assertForbidden();
    }
}
