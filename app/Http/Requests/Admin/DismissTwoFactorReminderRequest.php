<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dismisses the dashboard card recommending two-factor authentication. The
 * request carries no input; it always applies to the signed-in user's own
 * account (UserPolicy::dismissTwoFactorReminder).
 */
class DismissTwoFactorReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('dismissTwoFactorReminder', $user);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
