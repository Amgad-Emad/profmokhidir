<?php

namespace Database\Factories;

use App\Constants\RevisionKind;
use App\Models\Publication;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Revision>
 */
class RevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'revisionable_type' => Publication::class,
            'revisionable_id' => Publication::factory(),
            'user_id' => User::factory()->editor(),
            'kind' => RevisionKind::Save,
            'data' => ['title' => '[Placeholder] '.fake()->sentence(4)],
            'created_at' => now()->subMinutes(fake()->numberBetween(1, 600)),
        ];
    }

    /**
     * A revision of the given record.
     */
    public function of(Model $record): static
    {
        return $this->state(fn (array $attributes) => [
            'revisionable_type' => $record->getMorphClass(),
            'revisionable_id' => $record->getKey(),
        ]);
    }

    public function autosave(): static
    {
        return $this->state(fn (array $attributes) => ['kind' => RevisionKind::Autosave]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['kind' => RevisionKind::Publish]);
    }
}
