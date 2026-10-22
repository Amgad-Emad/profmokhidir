<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\Theme;
use App\Constants\ThemeSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A theme choice from the control or the account page. Anyone may store one
 * (guests keep it in a cookie).
 */
class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ThemeSettings::FIELD => ['required', 'string', Rule::enum(Theme::class)],
        ];
    }
}
