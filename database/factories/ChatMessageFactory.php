<?php

namespace Database\Factories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
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
            'client_uuid' => (string) Str::uuid(),
            'body' => '[Placeholder] '.fake()->sentence(12),
            'version' => 1,
            'edited_at' => null,
        ];
    }

    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => 2,
            'edited_at' => now(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()]);
    }
}
