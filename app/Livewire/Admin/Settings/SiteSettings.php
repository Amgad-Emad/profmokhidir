<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Constants\AdminNavigation;
use App\Constants\SettingKeys;
use App\Http\Requests\Admin\SiteSettingsRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Site settings (admin only): public contact details, the address that
 * receives tribute and community notifications, the mail sender, and the
 * community chat upload switch and size limits, and the visitor analytics
 * switch (off until the client approves it).
 */
class SiteSettings extends Component
{
    use AuthorizesRequests, NotifiesEditors;

    public const string FIELD_PREFIX = 'form.';

    /** @var array<string, string|bool|null> */
    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Setting::class);
        $this->form = $this->settings()->all();
        $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] = $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] !== '0';
        $this->form[SettingKeys::ANALYTICS_ENABLED] = $this->form[SettingKeys::ANALYTICS_ENABLED] === '1';
    }

    public function save(): void
    {
        $this->authorize('update', Setting::class);

        $request = new SiteSettingsRequest;
        $validated = $this->validate(
            self::prefix($request->rules()),
            self::prefix($request->messages()),
            self::prefix($request->attributes()),
        )['form'] ?? [];

        $values = [];

        foreach (SettingKeys::all() as $key) {
            $values[$key] = $validated[$key] ?? null;
        }

        $values[SettingKeys::CHAT_UPLOADS_ENABLED] = (bool) ($validated[SettingKeys::CHAT_UPLOADS_ENABLED] ?? false);
        $values[SettingKeys::ANALYTICS_ENABLED] = (bool) ($validated[SettingKeys::ANALYTICS_ENABLED] ?? false);

        $this->form = $this->settings()->update($values, auth()->user());
        $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] = $this->form[SettingKeys::CHAT_UPLOADS_ENABLED] !== '0';
        $this->form[SettingKeys::ANALYTICS_ENABLED] = $this->form[SettingKeys::ANALYTICS_ENABLED] === '1';
        $this->toast('Site settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.site-settings', [
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

    private function settings(): SettingService
    {
        return app(SettingService::class);
    }
}
