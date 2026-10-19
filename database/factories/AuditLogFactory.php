<?php

namespace Database\Factories;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'action' => fake()->randomElement(AuditAction::all()),
            'subject_type' => null,
            'subject_id' => null,
            'meta' => ['note' => '[Placeholder] '.fake()->sentence(6)],
            'created_at' => now()->subMinutes(fake()->numberBetween(1, 10000)),
        ];
    }

    public function action(string $action): static
    {
        return $this->state(fn (array $attributes) => ['action' => $action]);
    }
}
