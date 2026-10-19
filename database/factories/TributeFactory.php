<?php

namespace Database\Factories;

use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tribute>
 */
class TributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(TributeRelationship::cases()),
            'location' => fake()->city().', '.fake()->country(),
            'message' => '[Placeholder] '.fake()->paragraph(3),
            'email' => fake()->boolean(70) ? fake()->safeEmail() : null,
            'status' => TributeStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'sort_order' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TributeStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
        ]);
    }

    public function approved(?User $approver = null): static
    {
        return $this->state(function (array $attributes) use ($approver) {
            $at = now()->subDays(fake()->numberBetween(1, 300));

            return [
                'status' => TributeStatus::Approved,
                'approved_by' => $approver?->id,
                'approved_at' => $at,
                'published_at' => $at,
            ];
        });
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TributeStatus::Rejected,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
        ]);
    }

    /** Alias so the shared content helpers read naturally. */
    public function published(): static
    {
        return $this->approved();
    }

    public function relationship(TributeRelationship $relationship): static
    {
        return $this->state(fn (array $attributes) => ['relationship' => $relationship]);
    }
}
