<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Constants\Appearance as AppearanceSettings;
use App\Constants\Theme;
use App\Constants\ThemeContrast;
use App\Constants\ThemeTokens;
use App\Http\Requests\Admin\ThemeImportRequest;
use App\Http\Requests\Admin\ThemeRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\ColourTheme;
use App\Services\ColourThemeService;
use App\Services\ThemePaletteService;
use App\Support\Colour;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin › Appearance (admins only, "manage appearance"). While editing is off
 * (config appearance.editing, the default) every theme can still be inspected,
 * previewed, applied, exported and deleted, but no colour can be changed and no
 * new theme made (no duplicate, import, rename, draft or version restore). The
 * suggested
 * templates as cards (Apply, Duplicate to edit), the custom themes, and the
 * editor of the selected theme: a Light | Dark switch, the Basic colours as
 * large swatches, every other token under "Advanced colours", the contrast
 * guard with a Fix for each failing pair, version history, and a live
 * preview of real pages that follows every change before it is saved
 * (resources/js/admin/appearance.js). Logic lives in ColourThemeService and
 * ThemePaletteService; this component holds the unsaved colours.
 */
class Appearance extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithFileUploads;

    public const string CONFIRM_SAVE = 'save';

    public const string CONFIRM_PUBLISH = 'publish';

    public const string CONFIRM_DELETE = 'delete';

    #[Locked]
    public ?int $themeId = null;

    public string $mode = 'light';

    /** @var array<string, string> the chosen light colours, unsaved edits included */
    public array $light = [];

    /** @var array<string, string> */
    public array $dark = [];

    public string $name = '';

    public string $previewPage = AppearanceSettings::DEFAULT_PREVIEW_PAGE;

    public bool $dirty = false;

    public ?string $confirming = null;

    public bool $showHistory = false;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $importFile = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ColourTheme::class);
        $this->themes()->installPresets();
        $this->load($this->themes()->active() ?? $this->themes()->brand());
    }

    public function select(int $id): void
    {
        $this->load($this->theme($id));
    }

    public function setMode(string $mode): void
    {
        $this->mode = Theme::tryFrom($mode)?->value ?? Theme::Light->value;
    }

    public function setPreviewPage(string $page): void
    {
        $this->previewPage = array_key_exists($page, AppearanceSettings::PREVIEW_PAGES) ? $page : AppearanceSettings::DEFAULT_PREVIEW_PAGE;
    }

    public function setColour(string $token, string $colour, ?string $mode = null): void
    {
        $this->ensureEditing();
        $this->authorize('update', $this->current());
        $mode = Theme::tryFrom($mode ?? $this->mode)?->value ?? $this->mode;
        $this->resetErrorBag('colour.'.$token);

        $validator = validator(['token' => $token, 'colour' => trim($colour)], ThemeRequest::colourRules(), ThemeRequest::colourMessages());

        if ($validator->fails()) {
            $this->addError('colour.'.$token, $validator->errors()->first());

            return;
        }

        $value = Colour::normalise(trim($colour));

        if ($token === ThemeTokens::MARK_RING && ! in_array($value, $this->palette()->markRingOptions($this->resolved()[$mode]), true)) {
            $this->addError('colour.'.$token, __('The mark ring takes only a brass-like tone of this palette.'));

            return;
        }

        $this->{$mode}[$token] = $value;
        $this->dirty = true;
    }

    /** "Reset to template": the value the theme's template sets, or nothing (the token derives again). */
    public function resetToken(string $token): void
    {
        $this->ensureEditing();
        $this->authorize('update', $this->current());
        $template = $this->themes()->templateValues($this->current())[$this->mode];

        if (array_key_exists($token, $template)) {
            $this->{$this->mode}[$token] = $template[$token];
        } else {
            unset($this->{$this->mode}[$token]);
        }

        $this->dirty = true;
    }

    /** Every advanced colour of this mode follows the Basic colours again. */
    public function resetAdvanced(): void
    {
        $this->ensureEditing();
        $this->authorize('update', $this->current());
        $this->{$this->mode} = array_intersect_key($this->{$this->mode}, array_flip(ThemeTokens::basic()));
        $this->dirty = true;
        $this->toast(__('Advanced colours now follow the basic colours.'), AdminUx::TOAST_INFO);
    }

    /** The contrast guard's Fix: the nearest shade of the foreground that passes. */
    public function fix(string $mode, string $pair): void
    {
        $this->ensureEditing();
        $this->authorize('update', $this->current());

        foreach ($this->palette()->check($this->resolved()) as $result) {
            if ($result['mode'] === $mode && $result['id'] === $pair && $result['suggestion'] !== null) {
                $this->setColour($result['token'], $result['suggestion'], $mode);
                $this->toast(__(':label now reaches :ratio:1.', ['label' => __($result['label']), 'ratio' => number_format($result['minimum'], 1)]), AdminUx::TOAST_INFO);

                return;
            }
        }
    }

    public function saveDraft(bool $anyway = false): void
    {
        $this->ensureEditing();
        $theme = $this->current();
        $this->authorize('update', $theme);

        if (! $anyway && $this->failures() !== []) {
            $this->confirming = self::CONFIRM_SAVE;

            return;
        }

        $this->themes()->saveDraft($theme, $this->light, $this->dark, auth()->user(), acceptFailures: true);
        $this->confirming = null;
        $this->dirty = false;
        $this->toast(__('Draft saved. The live site is unchanged until you publish.'));
    }

    public function publish(bool $anyway = false): void
    {
        $theme = $this->current();
        $this->authorize('publish', $theme);

        if ($this->failures(criticalOnly: true) !== []) {
            $this->confirming = null;
            $this->toast(__('Body text, links or buttons fail the contrast check. Fix the blocking pairs before publishing.'), AdminUx::TOAST_ERROR);

            return;
        }

        if (! $anyway && $this->failures() !== []) {
            $this->confirming = self::CONFIRM_PUBLISH;

            return;
        }

        if (! $theme->is_preset && $this->dirty) {
            $this->themes()->saveDraft($theme, $this->light, $this->dark, auth()->user(), acceptFailures: true);
        }

        $this->publishTheme($theme->refresh());
    }

    /** A card's "Apply": a suggested theme goes live as it is. */
    public function apply(int $id): void
    {
        $theme = $this->theme($id);
        $this->authorize('publish', $theme);
        $this->publishTheme($theme);
    }

    public function restoreBrand(): void
    {
        $brand = $this->themes()->brand();
        $this->authorize('publish', $brand);
        $this->publishTheme($brand);
    }

    public function duplicate(?int $id = null): void
    {
        $this->ensureEditing();
        $this->authorize('create', ColourTheme::class);
        $source = $id === null ? $this->current() : $this->theme($id);
        $copy = $this->themes()->duplicate($source, auth()->user());

        if ($id === null || $id === $this->themeId) {
            // Unsaved edits travel with the copy.
            $copy = $this->themes()->saveDraft($copy, $this->light, $this->dark, auth()->user(), acceptFailures: true);
        }

        $this->load($copy);
        $this->toast(__('“:name” is ready to edit.', ['name' => $copy->name]));
    }

    public function rename(): void
    {
        $this->ensureEditing();
        $theme = $this->current();
        $this->authorize('update', $theme);
        $this->validate((new ThemeRequest)->rules(), (new ThemeRequest)->messages());
        $this->themes()->rename($theme, $this->name, auth()->user());
        $this->name = $theme->name;
        $this->toast(__('Theme renamed.'));
    }

    public function askDelete(): void
    {
        $this->authorize('delete', $this->current());
        $this->confirming = self::CONFIRM_DELETE;
    }

    public function delete(): void
    {
        $theme = $this->current();
        $this->authorize('delete', $theme);
        $name = $theme->name;
        $this->themes()->delete($theme, auth()->user());
        $this->confirming = null;
        $this->load($this->themes()->active() ?? $this->themes()->brand());
        $this->toast(__('“:name” was deleted.', ['name' => $name]));
    }

    public function cancelConfirmation(): void
    {
        $this->confirming = null;
    }

    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
    }

    public function restoreRevision(int $revision): void
    {
        $this->ensureEditing();
        $theme = $this->current();
        $this->authorize('update', $theme);
        $this->themes()->restoreRevision($theme, $revision, auth()->user());
        $this->load($theme->refresh());
        $this->showHistory = false;
        $this->toast(__('The earlier version is loaded as the draft. Publish it to put it live.'));
    }

    public function export(): StreamedResponse
    {
        $theme = $this->current();
        $this->authorize('view', $theme);
        $data = $this->themes()->export($theme, auth()->user());

        return response()->streamDownload(
            static function () use ($data): void {
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            },
            $theme->slug.'.json',
            ['Content-Type' => 'application/json'],
        );
    }

    public function updatedImportFile(): void
    {
        $this->ensureEditing();
        $this->authorize('create', ColourTheme::class);
        $request = new ThemeImportRequest;
        $this->validate($request->rules(), $request->messages());

        try {
            $data = json_decode((string) file_get_contents($this->importFile->getRealPath()), true, 16, JSON_THROW_ON_ERROR);
            $theme = $this->themes()->import(is_array($data) ? $data : [], auth()->user());
        } catch (\JsonException) {
            $this->addError('importFile', __('The file is not valid JSON.'));

            return;
        } catch (ValidationException $exception) {
            $this->addError('importFile', implode(' ', $exception->validator->errors()->all()));

            return;
        } finally {
            $this->importFile = null;
        }

        $this->load($theme);
        $this->toast(__('“:name” was imported as a new theme.', ['name' => $theme->name]));
    }

    public function render(): View
    {
        $theme = $this->current();
        $resolved = $this->resolved();
        $checks = $this->palette()->check($resolved);
        $template = $this->themes()->templateValues($theme);
        $themes = $this->themes()->all();
        $presets = $themes->where('is_preset', true)->mapWithKeys(fn (ColourTheme $preset): array => [$preset->id => $this->themes()->resolve($preset, published: true)]);

        return view('livewire.admin.settings.appearance', [
            'editing' => self::editingEnabled(),
            'theme' => $theme,
            'themes' => $themes,
            'presetColours' => $presets,
            'active' => $themes->firstWhere('is_active', true),
            'editable' => self::editingEnabled() && auth()->user()->can('update', $theme),
            'resolved' => $resolved,
            'checks' => $checks,
            'failures' => array_values(array_filter($checks, static fn (array $check): bool => ! $check['passes'])),
            'template' => $template,
            'tokens' => ThemeTokens::all(),
            'groups' => ThemeTokens::groups(),
            'quickPicks' => $this->palette()->quickPicks($resolved[$this->mode]),
            'markRingOptions' => $this->palette()->markRingOptions($resolved[$this->mode]),
            'previewCss' => str_replace(':root:root{', ':root:root:root{', $this->palette()->css($resolved)),
            'previewUrls' => array_map(static fn (array $page): string => url($page['path']), AppearanceSettings::PREVIEW_PAGES),
            'previewSiteUrl' => $this->themes()->previewUrl($theme),
            'revisions' => $this->showHistory ? $this->themes()->history($theme) : collect(),
            'minimums' => ThemeContrast::minimums(),
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::APPEARANCE)['label']]);
    }

    /**
     * Whether colour themes can be changed here (config appearance.editing). Off,
     * the screen only shows the live theme.
     */
    public static function editingEnabled(): bool
    {
        return (bool) config('appearance.editing', false);
    }

    /**
     * Changing a theme's colours or name, and making a new theme (duplicate,
     * import), are refused while editing is off, whatever the browser sends.
     */
    private function ensureEditing(): void
    {
        abort_unless(self::editingEnabled(), 403);
    }

    private function publishTheme(ColourTheme $theme): void
    {
        try {
            $this->themes()->publish($theme, auth()->user());
        } catch (ValidationException $exception) {
            $this->toast($exception->validator->errors()->first(), AdminUx::TOAST_ERROR);

            return;
        }

        $this->confirming = null;
        $this->load($theme->refresh());
        $this->toast(__('The site now uses “:name”.', ['name' => $theme->name]));
    }

    private function load(ColourTheme $theme): void
    {
        $this->authorize('view', $theme);
        $working = $theme->working();
        $this->themeId = $theme->id;
        $this->light = $working['light'];
        $this->dark = $working['dark'];
        $this->name = $theme->name;
        $this->dirty = false;
        $this->confirming = null;
        $this->resetErrorBag();
    }

    /**
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    private function resolved(): array
    {
        return $this->palette()->resolve($this->light, $this->dark);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function failures(bool $criticalOnly = false): array
    {
        return $this->palette()->failures($this->resolved(), $criticalOnly);
    }

    private function current(): ColourTheme
    {
        return $this->theme((int) $this->themeId);
    }

    private function theme(int $id): ColourTheme
    {
        $theme = $this->themes()->find($id);
        abort_if($theme === null, 404);

        return $theme;
    }

    private function themes(): ColourThemeService
    {
        return app(ColourThemeService::class);
    }

    private function palette(): ThemePaletteService
    {
        return app(ThemePaletteService::class);
    }
}
