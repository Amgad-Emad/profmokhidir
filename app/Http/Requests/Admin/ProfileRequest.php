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
            'contact_email_public' => ['nullable', 'string', 'email', 'max:255'],
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
            'short_bio' => 'short biography',
            'full_bio' => 'full biography',
            'contact_email_public' => 'public contact email',
            'key_facts.*.label' => 'key fact label',
            'key_facts.*.value' => 'key fact value',
            'expertise.*' => 'expertise entry',
        ];
    }
}
