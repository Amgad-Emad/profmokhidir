<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Appearance;
use App\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The JSON file of an exported colour theme (Admin › Appearance › Import).
 * The contents are checked by ColourThemeService::import (known tokens, hex colours only).
 */
class ThemeImportRequest extends FormRequest
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
            'importFile' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:'.Appearance::IMPORT_MAX_KB],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'importFile.mimetypes' => __('Choose a .json file exported from this screen.'),
            'importFile.max' => __('The file is larger than a colour theme can be (:max KB).', ['max' => Appearance::IMPORT_MAX_KB]),
        ];
    }
}
