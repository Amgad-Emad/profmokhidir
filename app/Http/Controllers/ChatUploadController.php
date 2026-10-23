<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ChatRateLimitException;
use App\Http\Requests\ChatUploadRequest;
use App\Services\ChatAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * The chat's temporary upload area: the composer stages each file here (with
 * a progress bar) before the message is sent, and the × on a preview
 * discards it. Both routes sit behind the community-member middleware.
 */
class ChatUploadController extends Controller
{
    public function store(ChatUploadRequest $request, ChatAttachmentService $attachments): JsonResponse
    {
        try {
            $upload = $attachments->stage($request->user(), $request->file('file'));
        } catch (ChatRateLimitException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'retry_after' => $exception->retryAfter], 429);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['upload' => $attachments->presentUpload($upload)], 201);
    }

    public function destroy(Request $request, string $upload, ChatAttachmentService $attachments): JsonResponse
    {
        $attachments->discard($request->user(), $upload);

        return response()->json(['ok' => true]);
    }
}
