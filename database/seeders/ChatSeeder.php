<?php

namespace Database\Seeders;

use App\Constants\CommunityStatus;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ChatCorrespondenceService;
use App\Services\ChatService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ChatService $chat, ChatCorrespondenceService $correspondence, UserRepositoryInterface $users): void
    {
        // The one Scientific Community room (created on first use).
        $channel = $chat->room();

        if ($channel->messages()->exists()) {
            return;
        }

        $members = $users->withCommunityStatus(CommunityStatus::Approved)->values();
        $admin = $users->findByEmail('admin@example.com');

        if ($members->isEmpty() || $admin === null) {
            return;
        }

        $messages = [
            [$admin, '[Placeholder] Welcome to the Scientific Community. Please keep discussion respectful and on topic.'],
            [$members[0], '[Placeholder] Thank you. I studied under Prof. Khidir in the 1990s and would love to hear from others.'],
            [$members[1 % $members->count()], '[Placeholder] مرحباً بالجميع — glad to be here 🌱'],
            [$members[0], '[Placeholder] Has anyone kept copies of the AGR 301 problem sets?'],
        ];

        $sent = [];

        foreach ($messages as [$author, $body]) {
            $sent[] = $chat->send($channel, $author, $body, (string) Str::uuid());
        }

        // A reply with light formatting and maths, and the welcome note pinned by the administrator.
        $sent[] = $chat->send(
            $channel,
            $members[1 % $members->count()],
            '[Placeholder] I have the **1994** set. The yield model was $y = a + bx$, if memory serves. See https://example.org/agr-301',
            (string) Str::uuid(),
            (int) $sent[3]->getKey(),
        );
        $correspondence->pin($sent[0], $admin);

        // One open report so the moderation queue has something to show.
        $reported = $sent[3] ?? null;
        $reporter = $members->first(fn ($member): bool => (int) $member->getKey() !== (int) $reported?->user_id);

        if ($reported !== null && $reporter !== null) {
            $chat->report($reported, $reporter, '[Placeholder] Example report so the moderation queue is not empty.');
        }
    }
}
