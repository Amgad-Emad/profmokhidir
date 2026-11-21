<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Constants\AdminNavigation;
use App\Constants\Sections;
use App\Constants\SettingKeys;
use App\Http\Requests\Admin\SiteSettingsRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\Setting;
use App\Services\NavigationService;
use App\Services\SectionVisibilityService;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Site settings (admin only): public contact details, the address that
 * receives tribute and community notifications, the mail sender, and the
 * community chat upload switch and size limits, and the visitor analytics
 * switch (off until the client approves it), and the digits of Arabic pages.
 */
class SiteSettings extends Component
{
    use AuthorizesRequests, NotifiesEditors;

    public const string FIELD_PREFIX = 'form.';

    /** @var array<string, string|bool|null> */
    public array $form = [];

    /** Section key => shown on the site (false: hidden from visitors). */
    public array $shown = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Setting::class);
        $this->form = $this->settings()->all();
        $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] = $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] !== '0';
        $this->form[SettingKeys::ANALYTICS_ENABLED] = $this->form[SettingKeys::ANALYTICS_ENABLED] === '1';
        $this->loadShown();
    }

    public function save(): void
    {
        $this->authorize('update', Setting::class);

        $request = new SiteSettingsRequest;
        $rules = $request->rules();
        $sectionRules = ['shown' => $rules['shown'], 'shown.*' => $rules['shown.*']];
        unset($rules['shown'], $rules['shown.*']);

        $shown = $this->validate($sectionRules)['shown'] ?? [];

        $validated = $this->validate(
            self::prefix($rules),
            self::prefix($request->messages()),
            self::prefix($request->attributes()),
        )['form'] ?? [];

        $values = [];

        foreach (SettingKeys::all() as $key) {
            $values[$key] = $validated[$key] ?? null;
        }

        // Sections switched off are stored as the hidden list.
        $values[SettingKeys::HIDDEN_SECTIONS] = implode(',', array_keys(array_filter($shown, fn ($on): bool => ! $on))) ?: null;

        $values[SettingKeys::CHAT_UPLOADS_ENABLED] = (bool) ($validated[SettingKeys::CHAT_UPLOADS_ENABLED] ?? false);
        $values[SettingKeys::ANALYTICS_ENABLED] = (bool) ($validated[SettingKeys::ANALYTICS_ENABLED] ?? false);

        $this->form = $this->settings()->update($values, auth()->user());
        $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] = $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] !== '0';
        $this->form[SettingKeys::ANALYTICS_ENABLED] = $this->form[SettingKeys::ANALYTICS_ENABLED] === '1';
        $this->loadShown();
        $this->toast(__('Site settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.site-settings', [
            'sections' => $this->sectionLabels(),
            'labels' => SettingKeys::labels(),
            'defaults' => $this->settings()->defaults(),
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::SITE_SETTINGS)['label']]);
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, mixed>
     */
    private static function prefix(array $items): array
    {
        $prefixed = [];

        foreach ($items as $key => $value) {
            $prefixed[self::FIELD_PREFIX.$key] = $value;
        }

        return $prefixed;
    }

    private function loadShown(): void
    {
        $hidden = app(SectionVisibilityService::class)->hidden();
        $this->shown = [];

        foreach (Sections::all() as $key) {
            $this->shown[$key] = ! in_array($key, $hidden, true);
        }
    }

    /**
     * Each section's name as visitors see it in the navigation.
     *
     * @return array<string, string>
     */
    private function sectionLabels(): array
    {
        $labels = [];

        foreach (Sections::all() as $key) {
            $labels[$key] = app(NavigationService::class)->section($key)['label'];
        }

        return $labels;
    }

    private function settings(): SettingService
    {
        return app(SettingService::class);
    }
}
