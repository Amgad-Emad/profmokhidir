<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ChatAttachmentFile;
use App\Services\ChatAttachmentService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One file staged for a chat message. Type is checked by content as well as
 * extension, and the size limits come from the site settings
 * (ChatAttachmentFile); ChatAttachmentService checks access, the upload
 * switch, the tray size and the rate limit again.
 */
class ChatUploadRequest extends FormRequest
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
        $attachments = app(ChatAttachmentService::class);

        return [
            'file' => ['required', 'file', new ChatAttachmentFile($attachments->imageMaxMegabytes() * 1024, $attachments->documentMaxMegabytes() * 1024)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Choose a file to attach.'),
            'file.file' => __('The file could not be uploaded. Try again.'),
        ];
    }
}
