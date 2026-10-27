<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Locales;
use App\Constants\Permissions;
use App\Constants\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Inviting an administrator or editor: name, email and the staff role.
 */
class StaffInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::MANAGE_USERS);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::in(Roles::staff())],
            'locale' => ['nullable', 'string', Rule::in(Locales::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('An account with this email already exists. Change its role from the list instead.'),
            'role.in' => __('Choose administrator or editor.'),
        ];
    }
}
