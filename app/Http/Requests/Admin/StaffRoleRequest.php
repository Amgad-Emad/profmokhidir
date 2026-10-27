<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Permissions;
use App\Constants\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Changing someone's staff role: administrator, editor or none (no dashboard
 * access; any community role is kept).
 */
class StaffRoleRequest extends FormRequest
{
    public const string NONE = 'none';

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
            'role' => ['required', 'string', Rule::in(self::options())],
        ];
    }

    /**
     * @return list<string>
     */
    public static function options(): array
    {
        return [...Roles::staff(), self::NONE];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (Roles::staff() as $role) {
            $labels[$role] = Roles::labels()[$role];
        }

        $labels[self::NONE] = __('No dashboard access');

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => __('Choose administrator, editor or no dashboard access.'),
        ];
    }
}
