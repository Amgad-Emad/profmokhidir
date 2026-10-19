<?php

namespace Database\Factories;

use App\Models\ChatChannel;
use App\Models\ChatRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatRead>
 */
class ChatReadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => ChatChannel::factory(),
            'user_id' => User::factory()->member(),
            'last_read_message_id' => null,
        ];
    }
}
