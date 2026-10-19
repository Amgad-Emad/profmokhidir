<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\ChatSettings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Rules for sending or editing a chat message. The Livewire component applies
 * them to its action arguments before anything reaches ChatService, which
 * enforces the same limits again.
 */
class ChatMessageRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:'.ChatSettings::BODY_MAX_LENGTH],
            'client_uuid' => ['required', 'uuid'],
            'version' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Write a message first.',
            'body.max' => 'Messages can be at most :max characters.',
            'client_uuid.required' => 'The message is missing its identifier; please reload the page.',
            'client_uuid.uuid' => 'The message identifier is invalid; please reload the page.',
        ];
    }
}
