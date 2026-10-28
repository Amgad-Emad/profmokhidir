<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Appearance;
use App\Constants\Permissions;
use App\Constants\ThemeTokens;
use App\Support\Colour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A colour theme's name and one colour change on Admin › Appearance (the
 * Livewire screen validates with these rules).
 */
class ThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::MANAGE_APPEARANCE) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.Appearance::NAME_MAX],
        ];
    }

    /**
     * One token and its colour (#rgb, #rrggbb or #rrggbbaa).
     *
     * @return array<string, mixed>
     */
    public static function colourRules(): array
    {
        return [
            'token' => ['required', 'string', Rule::in(ThemeTokens::keys())],
            'colour' => ['required', 'string', 'regex:'.Colour::PATTERN],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Give the theme a name.'),
            'name.max' => __('Keep the name under :max characters.', ['max' => Appearance::NAME_MAX]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function colourMessages(): array
    {
        return [
            'colour.regex' => __('Enter a hex colour such as #13221C.'),
            'token.in' => __('That colour is not part of the theme.'),
        ];
    }
}
