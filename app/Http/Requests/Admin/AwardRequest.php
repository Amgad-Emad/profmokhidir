<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class AwardRequest extends ContentRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'year' => self::yearRule(),
            'description' => ['nullable', 'string', 'max:2000'],
            'source_url' => self::urlRule(),
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['source_url' => __('source link')];
    }
}
