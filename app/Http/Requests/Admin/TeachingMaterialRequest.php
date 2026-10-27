<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use Illuminate\Validation\Rules\Enum;

class TeachingMaterialRequest extends ContentRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'course_description' => ['nullable', 'string', 'max:1000'],
            'topic' => ['nullable', 'string', 'max:255'],
            'type' => ['required', new Enum(TeachingMaterialType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'version' => ['nullable', 'string', 'max:50'],
            'material_date' => self::dateRule(),
            'external_url' => self::urlRule(),
            'access' => ['required', new Enum(TeachingAccess::class)],
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'type.Illuminate\Validation\Rules\Enum' => __('Choose one of the material types.'),
            'access.Illuminate\Validation\Rules\Enum' => __('Choose who may download this material.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['material_date' => __('date'), 'external_url' => __('external link')];
    }
}
