<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

/**
 * The single profile form (About page).
 */
class ProfileRequest extends ContentRequest
{
    public const int MAX_EXPERTISE = 20;

    public const int MAX_KEY_FACTS = 12;

    /** Fits the unsigned smallint column. */
    public const int MAX_STUDENTS_SUPERVISED = 65535;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'title' => ['nullable', 'string', 'max:200'],
            'affiliation' => ['nullable', 'string', 'max:200'],
            'short_bio' => ['nullable', 'string', 'max:600'],
            'full_bio' => ['nullable', 'string', 'max:60000'],
            'expertise' => ['nullable', 'array', 'max:'.self::MAX_EXPERTISE],
            'expertise.*' => ['nullable', 'string', 'max:100'],
            'key_facts' => ['nullable', 'array', 'max:'.self::MAX_KEY_FACTS],
            'key_facts.*.label' => ['nullable', 'string', 'max:60'],
            'key_facts.*.value' => ['nullable', 'string', 'max:200'],
            'students_supervised' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_STUDENTS_SUPERVISED],
            'contact_email_public' => ['nullable', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-.\s]*$/'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'contact_note' => ['nullable', 'string', 'max:500'],
            'status' => self::statusRule(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'short_bio' => __('short biography'),
            'full_bio' => __('full biography'),
            'contact_email_public' => __('public contact email'),
            'contact_phone' => __('public contact phone'),
            'contact_address' => __('postal address'),
            'key_facts.*.label' => __('key fact label'),
            'key_facts.*.value' => __('key fact value'),
            'expertise.*' => __('expertise entry'),
            'students_supervised' => __('number of students supervised'),
        ];
    }
}
