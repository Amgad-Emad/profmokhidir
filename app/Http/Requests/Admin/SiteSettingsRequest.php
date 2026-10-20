<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Permissions;
use App\Constants\SettingKeys;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The site settings form: public contact details, the notification address
 * and the mail sender.
 */
class SiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::MANAGE_SETTINGS);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            SettingKeys::CONTACT_EMAIL => ['nullable', 'string', 'email', 'max:255'],
            SettingKeys::CONTACT_PHONE => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\s.-]+$/'],
            SettingKeys::CONTACT_ADDRESS => ['nullable', 'string', 'max:500'],
            SettingKeys::NOTIFICATION_EMAIL => ['nullable', 'string', 'email', 'max:255'],
            SettingKeys::MAIL_FROM_NAME => ['nullable', 'string', 'max:120'],
            SettingKeys::MAIL_FROM_ADDRESS => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_map('mb_strtolower', SettingKeys::labels());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            SettingKeys::CONTACT_PHONE.'.regex' => 'Use digits, spaces, brackets, dots, dashes and a leading + only.',
        ];
    }
}
