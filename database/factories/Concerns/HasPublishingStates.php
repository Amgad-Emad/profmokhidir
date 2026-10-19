<?php

declare(strict_types=1);

namespace Database\Factories\Concerns;

use App\Constants\ContentStatus;

/**
 * published() / draft() states for every content factory.
 */
trait HasPublishingStates
{
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published,
            'published_at' => $attributes['published_at'] ?? now()->subDays(fake()->numberBetween(1, 400)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => ['sort_order' => $order]);
    }
}
