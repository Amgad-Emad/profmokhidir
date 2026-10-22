<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\ChatSettings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Rules for reporting someone else's chat message.
 */
class ChatReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canAccessChat();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:'.ChatSettings::REPORT_REASON_MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Say briefly why you are reporting this message.',
            'reason.max' => 'Shorten the reason to :max characters or fewer.',
        ];
    }
}
