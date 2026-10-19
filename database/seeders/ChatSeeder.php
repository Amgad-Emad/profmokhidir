<?php

namespace Database\Seeders;

use App\Constants\CommunityStatus;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ChatService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ChatService $chat, UserRepositoryInterface $users): void
    {
        $channel = $chat->findChannel('general')
            ?? $chat->createChannel('General', 'Open discussion for approved members of the Scientific Community.');

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

        foreach ($messages as [$author, $body]) {
            $chat->send($channel, $author, $body, (string) Str::uuid());
        }
    }
}
