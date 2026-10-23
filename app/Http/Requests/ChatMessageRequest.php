<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\ChatAttachments;
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
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:'.ChatSettings::BODY_MAX_LENGTH],
            'client_uuid' => ['required', 'uuid'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'reply_to_id' => ['nullable', 'integer', 'min:1'],
            'attachments' => ['sometimes', 'array', 'max:'.ChatAttachments::MAX_FILES],
            'attachments.*.id' => ['required', 'uuid'],
            'attachments.*.alt' => ['nullable', 'string', 'max:'.ChatAttachments::ALT_MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required_without' => 'Write a message or attach a file first.',
            'attachments.max' => 'Attach at most :max files to one message.',
            'attachments.*.id.uuid' => 'One of the files could not be found. Remove it and attach it again.',
            'attachments.*.alt.max' => 'Keep each image description to :max characters or fewer.',
            'body.max' => 'Shorten the message to :max characters or fewer.',
            'client_uuid.required' => 'The message is missing its identifier; please reload the page.',
            'client_uuid.uuid' => 'The message identifier is invalid; please reload the page.',
            'reply_to_id.integer' => 'The message you are replying to could not be found.',
        ];
    }
}
