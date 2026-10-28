<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Appearance;
use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\Permissions;
use App\Constants\RevisionKind;
use App\Constants\Theme;
use App\Constants\ThemePresets;
use App\Constants\ThemeTokens;
use App\Models\ColourTheme;
use App\Models\User;
use App\Repositories\Contracts\ColourThemeRepositoryInterface;
use App\Repositories\Contracts\RevisionRepositoryInterface;
use App\Support\Colour;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The site's colour themes (Admin › Appearance): the suggested presets, custom
 * themes with an unpublished draft, publishing (blocked while body text, links
 * or buttons fail the contrast guard), the active theme's CSS for <head>
 * (cached, forgotten on every change), a signed preview of a draft for the
 * admin who opened it, version history (RevisionService), the audit trail and
 * JSON export and import. ThemePaletteService does the colour arithmetic.
 */
class ColourThemeService
{
    /** @var array<string, array{light: array<string, string>, dark: array<string, string>}> compactExactly() results, per input */
    private static array $compacted = [];

    public function __construct(
        private readonly ColourThemeRepositoryInterface $themes,
        private readonly ThemePaletteService $palette,
        private readonly RevisionService $revisions,
        private readonly RevisionRepositoryInterface $revisionStore,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Add any suggested theme that is missing (each stored compactly: the
     * tokens that do not simply follow its Basic colours).
     */
    public function installPresets(): void
    {
        foreach (ThemePresets::all() as $slug => $preset) {
            if ($this->themes->findBySlug($slug) !== null) {
                continue;
            }

            $values = $this->compactExactly($preset['light'], $preset['dark']);
            $this->themes->create(['name' => $preset['name'], 'slug' => $slug, 'is_preset' => true, 'light' => $values['light'], 'dark' => $values['dark']]);
        }
    }

    /**
     * @return Collection<int, ColourTheme>
     */
    public function all(): Collection
    {
        $this->installPresets();

        return $this->themes->all();
    }

    public function find(int $id): ?ColourTheme
    {
        return $this->themes->find($id);
    }

    public function active(): ?ColourTheme
    {
        return $this->themes->active();
    }

    public function brand(): ColourTheme
    {
        $this->installPresets();

        return $this->themes->findBySlug(ThemePresets::BRAND);
    }

    /**
     * Every token of both modes for a theme's working colours (its draft, else what is published).
     *
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public function resolve(ColourTheme $theme, bool $published = false): array
    {
        $values = $published ? ['light' => (array) $theme->light, 'dark' => (array) $theme->dark] : $theme->working();

        return $this->palette->resolve($values['light'], $values['dark']);
    }

    /**
     * What the site is drawn with, cached until a theme changes: both modes resolved,
     * and the CSS for <head> (null while the brand is active, when tokens.css already says it all).
     *
     * @return array{id: ?int, name: string, light: array<string, string>, dark: array<string, string>, css: ?string}
     */
    public function activePalette(): array
    {
        return Cache::rememberForever(CacheKeys::ACTIVE_THEME, function (): array {
            $brand = ['id' => null, 'name' => ThemePresets::get(ThemePresets::BRAND)['name'], 'light' => ThemeTokens::defaults(Theme::Light), 'dark' => ThemeTokens::defaults(Theme::Dark), 'css' => null];

            try {
                $theme = $this->themes->active();

                if ($theme === null || $theme->slug === ThemePresets::BRAND) {
                    return $theme === null ? $brand : [...$brand, 'id' => $theme->id];
                }

                if (! $this->isValid((array) $theme->light) || ! $this->isValid((array) $theme->dark)) {
                    return $brand;
                }

                $resolved = $this->resolve($theme, published: true);

                return ['id' => $theme->id, 'name' => $theme->name, ...$resolved, 'css' => $this->palette->css($resolved)];
            } catch (Throwable $exception) {
                report($exception);

                return $brand;
            }
        });
    }

    /**
     * The theme whose draft this request previews: only for a signed-in user who may manage appearance.
     */
    public function previewing(?User $user): ?ColourTheme
    {
        $id = session(Appearance::SESSION_PREVIEW);

        if (! is_int($id) || $user === null || ! $user->can(Permissions::MANAGE_APPEARANCE)) {
            return null;
        }

        return $this->themes->find($id);
    }

    /**
     * The colours this request is drawn with: the previewed draft for its admin, else the active theme.
     *
     * @return array{id: ?int, name: string, light: array<string, string>, dark: array<string, string>, css: ?string, preview: bool}
     */
    public function paletteFor(?User $user): array
    {
        $preview = $this->previewing($user);

        if ($preview === null) {
            return [...$this->activePalette(), 'preview' => false];
        }

        $resolved = $this->resolve($preview);

        return ['id' => $preview->id, 'name' => $preview->name, ...$resolved, 'css' => $this->palette->css($resolved), 'preview' => true];
    }

    public function previewUrl(ColourTheme $theme): string
    {
        return URL::temporarySignedRoute(Appearance::PREVIEW_ROUTE, now()->addMinutes(Appearance::PREVIEW_TTL_MINUTES), ['theme' => $theme->id]);
    }

    public function startPreview(ColourTheme $theme): void
    {
        session([Appearance::SESSION_PREVIEW => $theme->id]);
    }

    public function endPreview(): void
    {
        session()->forget(Appearance::SESSION_PREVIEW);
    }

    /**
     * A new custom theme from colours (a duplicate, an import or a fresh start).
     *
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     */
    public function create(string $name, array $light, array $dark, ?User $actor, ?string $basedOn = null, string $action = AuditAction::THEME_CREATED): ColourTheme
    {
        $theme = $this->themes->create([
            'name' => $this->cleanName($name),
            'slug' => $this->uniqueSlug($name),
            'is_preset' => false,
            'based_on' => $basedOn,
            'light' => $this->palette->sanitise($light),
            'dark' => $this->palette->sanitise($dark),
            'created_by' => $actor?->getKey(),
        ]);

        $this->revisions->record($theme, $this->snapshot($theme), RevisionKind::Save, $actor);
        $this->audit->record($actor, $action, $theme, ['name' => $theme->name, 'based_on' => $basedOn]);

        return $theme;
    }

    public function duplicate(ColourTheme $source, ?User $actor): ColourTheme
    {
        $values = $source->working();

        return $this->create(__(':name (copy)', ['name' => $source->name]), $values['light'], $values['dark'], $actor, $source->is_preset ? $source->slug : $source->based_on);
    }

    public function rename(ColourTheme $theme, string $name, ?User $actor): ColourTheme
    {
        $before = $theme->name;
        $this->themes->update($theme, ['name' => $this->cleanName($name)]);
        $this->audit->record($actor, AuditAction::THEME_RENAMED, $theme, ['from' => $before, 'to' => $theme->name]);
        $this->forget();

        return $theme;
    }

    /**
     * Keep edits as the theme's draft. With contrast failures the caller must
     * pass $acceptFailures (the "Save anyway" confirmation).
     *
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     *
     * @throws ValidationException when pairs fail and they were not accepted
     */
    public function saveDraft(ColourTheme $theme, array $light, array $dark, ?User $actor, bool $acceptFailures = false): ColourTheme
    {
        $light = $this->palette->sanitise($light);
        $dark = $this->palette->sanitise($dark);
        $failures = $this->palette->failures($this->palette->resolve($light, $dark));

        if ($failures !== [] && ! $acceptFailures) {
            throw ValidationException::withMessages(['contrast' => trans_choice('{1} One colour pair fails the contrast check.|[2,*] :count colour pairs fail the contrast check.', count($failures), ['count' => count($failures)])]);
        }

        $before = $theme->working();
        $this->themes->update($theme, ['draft_light' => $light, 'draft_dark' => $dark]);
        $this->revisions->record($theme, $this->snapshot($theme), RevisionKind::Save, $actor);
        $this->audit->record($actor, AuditAction::THEME_SAVED, $theme, ['changes' => $this->diff($before, $theme->working()), 'contrast_failures' => count($failures)]);

        return $theme;
    }

    /**
     * Put a theme's working colours live and make it the active theme. Refused
     * while body text, links or buttons fail the contrast guard.
     *
     * @throws ValidationException
     */
    public function publish(ColourTheme $theme, ?User $actor): ColourTheme
    {
        $values = $theme->working();
        $critical = $this->palette->failures($this->palette->resolve($values['light'], $values['dark']), criticalOnly: true);

        if ($critical !== []) {
            throw ValidationException::withMessages(['contrast' => __('Body text, links or buttons fail the contrast check, so this theme cannot be published. Fix the pairs marked as blocking first.')]);
        }

        $previous = $this->themes->active();
        $before = ['light' => (array) $theme->light, 'dark' => (array) $theme->dark];

        if (! $theme->is_preset) {
            $this->themes->update($theme, ['light' => $values['light'], 'dark' => $values['dark'], 'draft_light' => null, 'draft_dark' => null]);
        }

        $this->themes->activate($theme);
        $this->revisions->record($theme, $this->snapshot($theme), RevisionKind::Publish, $actor);
        $this->audit->record($actor, AuditAction::THEME_PUBLISHED, $theme, [
            'name' => $theme->name,
            'previous' => $previous?->name,
            'changes' => $this->diff($before, ['light' => (array) $theme->light, 'dark' => (array) $theme->dark]),
        ]);
        $this->forget();

        return $theme;
    }

    /**
     * "Restore brand colours": Evergreen & Ivory becomes the active theme again.
     */
    public function restoreBrand(?User $actor): ColourTheme
    {
        return $this->publish($this->brand(), $actor);
    }

    /**
     * Delete a custom theme; when it was active, the brand takes over.
     */
    public function delete(ColourTheme $theme, ?User $actor): void
    {
        if ($theme->is_preset) {
            throw ValidationException::withMessages(['theme' => __('Suggested themes cannot be deleted.')]);
        }

        $wasActive = $theme->is_active;
        $this->audit->record($actor, AuditAction::THEME_DELETED, $theme, ['name' => $theme->name, 'was_active' => $wasActive]);
        $this->revisionStore->forget($theme);
        $this->themes->delete($theme);

        if ($wasActive) {
            $this->themes->activate($this->brand());
        }

        $this->forget();
    }

    /**
     * @return Collection<int, \App\Models\Revision>
     */
    public function history(ColourTheme $theme): Collection
    {
        return $this->revisions->history($theme);
    }

    /**
     * Load an earlier version back as the draft (published again with Publish).
     */
    public function restoreRevision(ColourTheme $theme, int $revisionId, ?User $actor): ColourTheme
    {
        $data = $this->revisions->dataFor($theme, $revisionId);

        if ($data === null) {
            throw ValidationException::withMessages(['revision' => __('That version no longer exists.')]);
        }

        $before = $theme->working();
        $this->themes->update($theme, ['draft_light' => $this->palette->sanitise((array) ($data['light'] ?? [])), 'draft_dark' => $this->palette->sanitise((array) ($data['dark'] ?? []))]);
        $this->revisions->record($theme, $this->snapshot($theme), RevisionKind::Restore, $actor);
        $this->audit->record($actor, AuditAction::THEME_REVISION_RESTORED, $theme, ['revision' => $revisionId, 'changes' => $this->diff($before, $theme->working())]);

        return $theme;
    }

    /**
     * The theme as JSON-ready data: its working colours, every token resolved.
     *
     * @return array{format: string, version: int, name: string, light: array<string, string>, dark: array<string, string>}
     */
    public function export(ColourTheme $theme, ?User $actor): array
    {
        $this->audit->record($actor, AuditAction::THEME_EXPORTED, $theme, ['name' => $theme->name]);

        return ['format' => Appearance::EXPORT_FORMAT, 'version' => Appearance::EXPORT_VERSION, 'name' => $theme->name, ...$this->resolve($theme)];
    }

    /**
     * A custom theme from an exported file. Unknown tokens and invalid colours are refused, each named.
     *
     * @param  array<mixed>  $data
     *
     * @throws ValidationException
     */
    public function import(array $data, ?User $actor): ColourTheme
    {
        $errors = [];

        if (($data['format'] ?? null) !== Appearance::EXPORT_FORMAT || ($data['version'] ?? null) !== Appearance::EXPORT_VERSION) {
            $errors[] = __('This is not a colour theme exported from this site.');
        }

        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';

        if ($name === '') {
            $errors[] = __('The theme has no name.');
        }

        foreach (Theme::cases() as $mode) {
            $values = $data[$mode->value] ?? null;

            if (! is_array($values)) {
                $errors[] = __('The :mode colours are missing.', ['mode' => $mode->label()]);

                continue;
            }

            foreach ($values as $token => $value) {
                if (! ThemeTokens::isToken($token)) {
                    $errors[] = __('Unknown colour ":token" in :mode.', ['token' => Str::limit((string) $token, 40), 'mode' => $mode->label()]);
                } elseif (! Colour::isValid($value)) {
                    $errors[] = __('":token" in :mode is not a hex colour.', ['token' => $token, 'mode' => $mode->label()]);
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['import' => array_slice($errors, 0, 8)]);
        }

        $values = $this->compactExactly($data['light'], $data['dark']);

        return $this->create($name, $values['light'], $values['dark'], $actor, null, AuditAction::THEME_IMPORTED);
    }

    /**
     * The values a theme's "reset to template" returns a token to: what its preset sets, else nothing (derive).
     *
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public function templateValues(ColourTheme $theme): array
    {
        $slug = $theme->is_preset ? $theme->slug : $theme->based_on;
        $preset = $slug !== null ? $this->themes->findBySlug($slug) : null;

        return $preset === null ? ['light' => [], 'dark' => []] : ['light' => (array) $preset->light, 'dark' => (array) $preset->dark];
    }

    public function forget(): void
    {
        Cache::forget(CacheKeys::ACTIVE_THEME);
    }

    /**
     * Keep only the chosen colours that change the result: a token whose value is what it would
     * derive to anyway is dropped, so it follows the Basic colours after an edit.
     *
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public function compactExactly(array $light, array $dark): array
    {
        $values = ['light' => $this->palette->sanitise($light), 'dark' => $this->palette->sanitise($dark)];
        $key = md5(serialize($values));

        if (isset(self::$compacted[$key])) {
            return self::$compacted[$key];
        }

        $target = $this->palette->resolve($values['light'], $values['dark']);

        foreach (Theme::cases() as $mode) {
            foreach (array_keys($values[$mode->value]) as $token) {
                if (in_array($token, ThemeTokens::basic(), true)) {
                    continue;
                }

                $trial = $values;
                unset($trial[$mode->value][$token]);

                if ($this->palette->resolve($trial['light'], $trial['dark']) === $target) {
                    $values = $trial;
                }
            }
        }

        return self::$compacted[$key] = $values;
    }

    /**
     * Stored colours are valid when every entry is a known token with a hex colour (nothing is dropped by sanitising).
     *
     * @param  array<mixed>  $values
     */
    private function isValid(array $values): bool
    {
        return count($this->palette->sanitise($values)) === count($values);
    }

    /**
     * @return array{name: string, light: array<string, string>, dark: array<string, string>}
     */
    private function snapshot(ColourTheme $theme): array
    {
        return ['name' => $theme->name, ...$theme->working()];
    }

    /**
     * Token changes per mode, for the audit trail: mode => token => [from, to].
     *
     * @param  array{light: array<string, string>, dark: array<string, string>}  $before
     * @param  array{light: array<string, string>, dark: array<string, string>}  $after
     * @return array<string, array<string, array{0: ?string, 1: ?string}>>
     */
    private function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (Theme::cases() as $mode) {
            $old = $before[$mode->value] ?? [];
            $new = $after[$mode->value] ?? [];

            foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $token) {
                if (($old[$token] ?? null) !== ($new[$token] ?? null)) {
                    $changes[$mode->value][$token] = [$old[$token] ?? null, $new[$token] ?? null];
                }
            }
        }

        return $changes;
    }

    private function cleanName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', strip_tags($name)) ?? '');

        return Str::limit($name === '' ? __('Untitled theme') : $name, Appearance::NAME_MAX, '');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($this->cleanName($name)) ?: 'theme';
        $base = Str::limit($base, 80, '');
        $slug = $base;

        for ($i = 2; $this->themes->slugExists($slug) || ThemePresets::isPreset($slug); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
