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
 * receives tribute and community notifications, and the mail sender.
 */
class SiteSettings extends Component
{
    use AuthorizesRequests, NotifiesEditors;

    public const string FIELD_PREFIX = 'form.';

    /** @var array<string, string|null> */
    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Setting::class);
        $this->form = $this->settings()->all();
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

        $this->form = $this->settings()->update($values, auth()->user());
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
