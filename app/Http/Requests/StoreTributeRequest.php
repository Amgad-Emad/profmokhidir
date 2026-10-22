<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\TributeRelationship;
use App\Models\Tribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Public tribute submission. The honeypot field is never validated so a bot
 * that fills it sees the same calm confirmation as everyone else.
 */
class StoreTributeRequest extends FormRequest
{
    /** Hidden field that humans never fill in. */
    public const string HONEYPOT = 'website';

    public const int NAME_MAX_LENGTH = 120;

    public const int LOCATION_MAX_LENGTH = 120;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'relationship' => ['required', Rule::enum(TributeRelationship::class)],
            'location' => ['nullable', 'string', 'max:'.self::LOCATION_MAX_LENGTH],
            'message' => ['required', 'string', 'max:'.Tribute::MESSAGE_MAX_LENGTH],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter your name.',
            'relationship.required' => 'Choose how you knew Professor Khidir.',
            'relationship.Illuminate\Validation\Rules\Enum' => 'Please choose one of the listed relationships.',
            'message.required' => 'Write your tribute.',
            'message.max' => 'Shorten your tribute to :max characters or fewer.',
            'email.email' => 'Enter an email address in the form name@example.com, or leave it empty.',
            'consent.accepted' => 'Tick the box to agree that your tribute may be published.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message' => 'tribute',
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input(self::HONEYPOT));
    }

    /**
     * Values the service needs, without the consent flag or the honeypot.
     *
     * @return array{name: string, relationship: string, location: ?string, message: string, email: ?string}
     */
    public function tribute(): array
    {
        $validated = $this->validated();

        return [
            'name' => (string) $validated['name'],
            'relationship' => (string) $validated['relationship'],
            'location' => $validated['location'] ?? null,
            'message' => (string) $validated['message'],
            'email' => $validated['email'] ?? null,
        ];
    }
}
