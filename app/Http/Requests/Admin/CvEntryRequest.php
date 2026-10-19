<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\CvSection;
use Illuminate\Validation\Rules\Enum;

class CvEntryRequest extends ContentRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'section' => ['required', new Enum(CvSection::class)],
            'title' => ['required', 'string', 'max:255'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'role_description' => ['nullable', 'string', 'max:2000'],
            'start_year' => self::yearRule(),
            'end_year' => [...self::yearRule(), 'gte:start_year'],
            'last_updated_at' => self::dateRule(),
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'section.Illuminate\Validation\Rules\Enum' => 'Choose one of the CV sections.',
            'end_year.gte' => 'The end year cannot be before the start year.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['role_description' => 'description', 'last_updated_at' => 'last updated'];
    }
}
