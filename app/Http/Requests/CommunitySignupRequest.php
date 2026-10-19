<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\ChatSettings;
use App\Constants\CommunityStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Public application to join the Scientific Community. Visitors create an
 * account at the same time; a signed-in user who has not applied (or whose
 * membership ended) applies with their existing account. The honeypot field
 * is never validated so a bot that fills it sees an ordinary redirect.
 */
class CommunitySignupRequest extends FormRequest
{
    /** Hidden field that humans never fill in. */
    public const string HONEYPOT = 'website';

    public const int NAME_MAX_LENGTH = 120;

    public const int AFFILIATION_MAX_LENGTH = 160;

    public const int FIELD_MAX_LENGTH = 120;

    public const int REASON_MAX_LENGTH = 1500;

    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return true;
        }

        $user->unsetRelation('roles');

        return ! $user->canAccessAdmin() && $user->community_status === CommunityStatus::None;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'affiliation' => ['required', 'string', 'max:'.self::AFFILIATION_MAX_LENGTH],
            'academic_field' => ['required', 'string', 'max:'.self::FIELD_MAX_LENGTH],
            'reason_for_joining' => ['required', 'string', 'max:'.self::REASON_MAX_LENGTH],
            'conduct_agreed' => ['accepted'],
            'privacy_agreed' => ['accepted'],
        ];

        if ($this->isNewAccount()) {
            $rules = [
                'name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'password' => ['required', 'string', Password::default(), 'confirmed'],
            ] + $rules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please enter your email address.',
            'email.unique' => 'An account with this email address already exists. Sign in to continue.',
            'affiliation.required' => 'Please tell us your institution or organisation.',
            'academic_field.required' => 'Please tell us your academic field.',
            'reason_for_joining.required' => 'Please tell us briefly why you would like to join.',
            'reason_for_joining.max' => 'Your reason can be at most :max characters.',
            'conduct_agreed.accepted' => 'Please confirm that you agree to the conduct rules.',
            'privacy_agreed.accepted' => 'Please confirm that you have read the privacy note.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'academic_field' => 'academic field',
            'reason_for_joining' => 'reason for joining',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function isSpam(): bool
    {
        return filled($this->input(self::HONEYPOT));
    }

    public function isNewAccount(): bool
    {
        return ! $this->user() instanceof User;
    }

    /**
     * Values the service needs, without the agreements or the honeypot.
     *
     * @return array{name?: string, email?: string, password?: string, affiliation: string, academic_field: string, reason_for_joining: string}
     */
    public function application(): array
    {
        $validated = $this->validated();

        $data = [
            'affiliation' => (string) $validated['affiliation'],
            'academic_field' => (string) $validated['academic_field'],
            'reason_for_joining' => (string) $validated['reason_for_joining'],
        ];

        if ($this->isNewAccount()) {
            $data = [
                'name' => (string) $validated['name'],
                'email' => (string) $validated['email'],
                'password' => (string) $validated['password'],
            ] + $data;
        }

        return $data;
    }

    public static function reasonMaxLength(): int
    {
        return min(self::REASON_MAX_LENGTH, ChatSettings::BODY_MAX_LENGTH);
    }
}
