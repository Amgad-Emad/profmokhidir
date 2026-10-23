<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ChatAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a chat attachment from the private disk. The link is signed and
 * expires; the route also requires a signed-in, verified community member,
 * and ChatAttachmentService checks access and that the message is live.
 */
class ChatAttachmentController extends Controller
{
    public function __invoke(Request $request, int $media, string $variant, ChatAttachmentService $attachments): StreamedResponse
    {
        $file = $attachments->download($request->user(), $media, $variant);
        $disk = Storage::disk($file['disk']);

        abort_unless($disk->exists($file['path']), 404);

        $headers = [
            'Content-Type' => $file['mime'],
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return $file['inline']
            ? $disk->response($file['path'], $file['name'], $headers)
            : $disk->download($file['path'], $file['name'], $headers);
    }
}
