<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The optional note an administrator attaches to a community decision
 * (approve, reject, suspend, reinstate, remove). It is emailed to the applicant.
 */
class CommunityReviewRequest extends FormRequest
{
    public const int NOTE_MAX_LENGTH = 1000;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permissions::MANAGE_COMMUNITY);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:'.self::NOTE_MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.max' => 'Shorten the note to :max characters or fewer.',
        ];
    }
}
