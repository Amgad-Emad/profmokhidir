<?php

namespace Database\Factories;

use App\Constants\ChatAttachmentKind;
use App\Models\ChatMessage;
use App\Models\ChatUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Rows of the chat's temporary upload area. The factory writes the row only;
 * tests that need the file stage it through ChatAttachmentService.
 *
 * @extends Factory<ChatUpload>
 */
class ChatUploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory()->member(),
            'kind' => ChatAttachmentKind::Document->value,
            'original_name' => '[Placeholder] '.fake()->words(3, true).'.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(20_000, 2_000_000),
            'message_id' => null,
            'claimed_at' => null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ChatAttachmentKind::Image->value,
            'original_name' => '[Placeholder] '.fake()->words(2, true).'.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ChatAttachmentKind::Document->value,
        ]);
    }

    /**
     * Still waiting in the temporary area (not sent with a message).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['message_id' => null, 'claimed_at' => null]);
    }

    /**
     * Sent with the given message.
     */
    public function claimed(ChatMessage $message): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $message->user_id,
            'message_id' => $message->getKey(),
            'claimed_at' => now(),
        ]);
    }

    /**
     * Uploaded long enough ago for the scheduler to prune it.
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => ['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
    }
}
