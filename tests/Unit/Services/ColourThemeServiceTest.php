<?php

namespace Tests\Unit\Services;

use App\Constants\Appearance;
use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\RevisionKind;
use App\Constants\Theme;
use App\Constants\ThemePresets;
use App\Constants\ThemeTokens;
use App\Models\AuditLog;
use App\Models\ColourTheme;
use App\Models\User;
use App\Services\ColourThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Colour themes: presets, drafts, publishing (blocked on failing body text,
 * links or buttons), the cached active palette, the admin-only preview,
 * revisions, deletion, the audit trail, export and import.
 */
class ColourThemeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ColourThemeService $service;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ColourThemeService::class);
        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function presets_install_once_and_compactly(): void
    {
        $this->service->installPresets();
        $this->service->installPresets();

        $this->assertSame(ThemePresets::slugs(), ColourTheme::query()->orderBy('id')->pluck('slug')->all());
        $this->assertSame(count(ThemePresets::all()), ColourTheme::query()->where('is_preset', true)->count());

        $brand = $this->service->brand();
        $this->assertLessThan(20, count($brand->light), 'only the tokens that differ from their derivation are stored');
        $this->assertSame(ThemeTokens::defaults(Theme::Light), $this->service->resolve($brand)['light']);
        $this->assertSame(ThemeTokens::defaults(Theme::Dark), $this->service->resolve($brand)['dark']);
    }

    #[Test]
    public function with_no_theme_the_site_uses_the_brand_defaults(): void
    {
        $palette = $this->service->activePalette();

        $this->assertNull($palette['id']);
        $this->assertNull($palette['css']);
        $this->assertSame(ThemeTokens::defaults(Theme::Light), $palette['light']);
    }

    #[Test]
    public function the_active_palette_is_cached_and_forgotten_when_a_theme_changes(): void
    {
        $theme = ColourTheme::factory()->create();
        $this->assertNull($this->service->activePalette()['css']);
        $this->assertTrue(Cache::has(CacheKeys::ACTIVE_THEME));

        $this->service->publish($theme, $this->admin);
        $this->assertFalse(Cache::has(CacheKeys::ACTIVE_THEME), 'publishing forgets the cached palette');
        $this->assertStringContainsString('--surface:light-dark(#f4efe3,#0f1a30)', $this->service->activePalette()['css']);

        $this->service->rename($theme, 'Renamed', $this->admin);
        $this->assertFalse(Cache::has(CacheKeys::ACTIVE_THEME));

        $this->service->activePalette();
        $this->service->delete($theme->refresh(), $this->admin);
        $this->assertFalse(Cache::has(CacheKeys::ACTIVE_THEME));
        $this->assertNull($this->service->activePalette()['css'], 'the brand took over');
    }

    #[Test]
    public function an_invalid_stored_theme_falls_back_to_the_brand(): void
    {
        ColourTheme::factory()->active()->create(['light' => ['surface' => 'not a colour', 'sparkle' => '#ffffff'], 'dark' => ['ink' => 42]]);

        $palette = $this->service->activePalette();

        $this->assertSame(ThemeTokens::defaults(Theme::Light), $palette['light']);
        $this->assertSame(ThemeTokens::defaults(Theme::Dark), $palette['dark']);
    }

    #[Test]
    public function a_draft_with_failures_needs_accepting_and_never_touches_the_live_colours(): void
    {
        $theme = ColourTheme::factory()->active()->create();
        $light = [...$theme->light, 'ink-muted' => '#d0c8b4'];

        try {
            $this->service->saveDraft($theme, $light, $theme->dark, $this->admin);
            $this->fail('A draft with failing pairs was saved without "Save anyway".');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('contrast', $exception->errors());
        }

        $this->service->saveDraft($theme, $light, $theme->dark, $this->admin, acceptFailures: true);
        $theme->refresh();

        $this->assertSame('#d0c8b4', $theme->draft_light['ink-muted']);
        $this->assertArrayNotHasKey('ink-muted', $theme->light);
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::THEME_SAVED)->count());
        $this->assertSame([null, '#d0c8b4'], AuditLog::query()->where('action', AuditAction::THEME_SAVED)->sole()->meta['changes']['light']['ink-muted']);
    }

    #[Test]
    public function publishing_is_refused_while_body_text_links_or_buttons_fail(): void
    {
        $theme = ColourTheme::factory()->failing()->create();

        $this->expectException(ValidationException::class);

        try {
            $this->service->publish($theme, $this->admin);
        } finally {
            $this->assertFalse($theme->refresh()->is_active);
            $this->assertFalse(AuditLog::query()->where('action', AuditAction::THEME_PUBLISHED)->exists());
        }
    }

    #[Test]
    public function publishing_promotes_the_draft_activates_the_theme_and_keeps_a_revision(): void
    {
        $other = ColourTheme::factory()->active()->create();
        $theme = ColourTheme::factory()->withDraft()->create();

        $this->service->publish($theme, $this->admin);

        $theme->refresh();
        $this->assertTrue($theme->is_active);
        $this->assertFalse($other->refresh()->is_active);
        $this->assertSame('#f6efe0', $theme->light['surface']);
        $this->assertNull($theme->draft_light);
        $this->assertSame(RevisionKind::Publish, $this->service->history($theme)->first()->kind);
    }

    #[Test]
    public function rollback_loads_an_earlier_revision_as_the_draft(): void
    {
        $theme = $this->service->create('Reading room', ThemePresets::get(ThemePresets::OXFORD)['light'], ThemePresets::get(ThemePresets::OXFORD)['dark'], $this->admin);
        $first = $this->service->history($theme)->first();
        $this->service->saveDraft($theme, [...$theme->light, 'surface' => '#f7f3ea'], $theme->dark, $this->admin);
        $this->service->publish($theme->refresh(), $this->admin);

        $this->service->restoreRevision($theme, $first->id, $this->admin);

        $theme->refresh();
        $this->assertSame('#f4efe3', $theme->draft_light['surface']);
        $this->assertSame('#f7f3ea', $theme->light['surface'], 'the live colours wait for Publish');
        $this->assertSame(RevisionKind::Restore, $this->service->history($theme)->first()->kind);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::THEME_REVISION_RESTORED)->exists());

        $this->expectException(ValidationException::class);
        $this->service->restoreRevision($theme, 999999, $this->admin);
    }

    #[Test]
    public function templates_cannot_be_deleted_and_deleting_the_live_theme_restores_the_brand(): void
    {
        $this->service->installPresets();
        $theme = ColourTheme::factory()->create();
        $this->service->publish($theme, $this->admin);

        $this->service->delete($theme, $this->admin);

        $this->assertModelMissing($theme);
        $this->assertTrue($this->service->brand()->is_active);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::THEME_DELETED)->exists());

        $this->expectException(ValidationException::class);
        $this->service->delete($this->service->brand(), $this->admin);
    }

    #[Test]
    public function duplicates_get_unique_slugs_and_remember_their_template(): void
    {
        $this->service->installPresets();
        $olive = ColourTheme::query()->where('slug', ThemePresets::OLIVE)->sole();

        $first = $this->service->duplicate($olive, $this->admin);
        $second = $this->service->duplicate($olive, $this->admin);

        $this->assertNotSame($first->slug, $second->slug);
        $this->assertSame(ThemePresets::OLIVE, $second->based_on);
        $this->assertSame($this->admin->id, $first->created_by);
        $this->assertEquals($this->service->resolve($olive), $this->service->resolve($first));
        $this->assertSame((array) $olive->light, $this->service->templateValues($first)['light']);
    }

    #[Test]
    public function the_draft_preview_is_shown_only_to_a_user_who_may_manage_appearance(): void
    {
        $theme = ColourTheme::factory()->withDraft()->create();
        $this->service->startPreview($theme);

        $this->assertSame($theme->id, $this->service->previewing($this->admin)?->id);
        $palette = $this->service->paletteFor($this->admin);
        $this->assertTrue($palette['preview']);
        $this->assertSame('#f6efe0', $palette['light']['surface'], 'the preview shows the draft');

        $this->assertNull($this->service->previewing(User::factory()->editor()->create()));
        $this->assertNull($this->service->previewing(null));
        $this->assertFalse($this->service->paletteFor(null)['preview']);

        $this->service->endPreview();
        $this->assertNull($this->service->previewing($this->admin));

        $this->assertStringContainsString('signature=', $this->service->previewUrl($theme));
    }

    #[Test]
    public function export_and_import_round_trip_and_import_rejects_unknown_keys_and_bad_colours(): void
    {
        $theme = ColourTheme::factory()->create(['name' => 'Archive']);
        $data = $this->service->export($theme, $this->admin);

        $this->assertSame(Appearance::EXPORT_FORMAT, $data['format']);
        $this->assertSame(ThemeTokens::keys(), array_keys($data['light']));

        $imported = $this->service->import(json_decode(json_encode($data), true), $this->admin);
        $this->assertEquals($this->service->resolve($theme), $this->service->resolve($imported));
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::THEME_IMPORTED)->exists());

        foreach ([
            [...$data, 'light' => ['surface' => '#ffffff', 'glitter' => '#000000']],
            [...$data, 'dark' => ['surface' => 'rgb(0,0,0)']],
            [...$data, 'format' => 'something-else'],
            [...$data, 'name' => ''],
            [...$data, 'light' => 'none'],
        ] as $bad) {
            try {
                $this->service->import($bad, $this->admin);
                $this->fail('Imported an invalid theme: '.json_encode($bad));
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('import', $exception->errors());
            }
        }

        $this->assertSame(2, ColourTheme::query()->count());
    }

    #[Test]
    public function every_change_is_audited_with_its_author(): void
    {
        $theme = $this->service->create('Mine', [], [], $this->admin);
        $this->service->rename($theme, 'Ours', $this->admin);
        $this->service->saveDraft($theme, ['surface' => '#f6f1e6'], [], $this->admin);
        $this->service->publish($theme->refresh(), $this->admin);
        $this->service->export($theme, $this->admin);

        $actions = AuditLog::query()->where('user_id', $this->admin->id)->orderBy('id')->pluck('action')->all();
        $this->assertSame([AuditAction::THEME_CREATED, AuditAction::THEME_RENAMED, AuditAction::THEME_SAVED, AuditAction::THEME_PUBLISHED, AuditAction::THEME_EXPORTED], $actions);
        $this->assertEquals(['from' => 'Mine', 'to' => 'Ours'], AuditLog::query()->where('action', AuditAction::THEME_RENAMED)->sole()->meta);
    }
}
