<?php

namespace Database\Seeders;

use App\Constants\CommunityStatus;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ChatAttachmentService;
use App\Services\ChatService;
use App\Support\ChatSampleFiles;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * One message in the room with an image and a document attached, staged and
 * sent through the same services the composer uses, so local development has
 * attachments to look at. Runs once (skipped when the room already has files).
 */
class ChatUploadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ChatService $chat, ChatAttachmentService $attachments, UserRepositoryInterface $users): void
    {
        $member = $users->withCommunityStatus(CommunityStatus::Approved)->first();

        if ($member === null || ! $attachments->enabled()) {
            return;
        }

        $room = $chat->room();

        if ($room->messages()->whereHas('media')->exists()) {
            return;
        }

        $image = $attachments->stage($member, UploadedFile::fake()->image('agr-301-field-trip.jpg', 1200, 800));
        $document = $attachments->stage($member, new UploadedFile(ChatSampleFiles::pdf(), 'agr-301-problem-set.pdf', 'application/pdf', null, true));

        $chat->send($room, $member, '[Placeholder] The AGR 301 problem set and a photograph from the field trip.', (string) Str::uuid(), null, [
            ['id' => $image->uuid, 'alt' => '[Placeholder] Students measuring a field plot'],
            ['id' => $document->uuid, 'alt' => ''],
        ]);
    }
}

