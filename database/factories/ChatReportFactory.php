<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatReport>
 */
class ChatReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => ChatMessage::factory(),
            'reporter_id' => User::factory()->member(),
            'reason' => '[Placeholder] '.fake()->sentence(8),
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => ['resolved_by' => null, 'resolved_at' => null]);
    }

    public function resolved(?User $by = null): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_by' => $by?->id ?? User::factory()->admin(),
            'resolved_at' => now(),
        ]);
    }
}
