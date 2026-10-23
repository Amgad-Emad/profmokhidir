<?php

namespace Database\Factories;

use App\Constants\ChatSettings;
use App\Models\ChatChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatChannel>
 */
class ChatChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => '[Placeholder] '.fake()->sentence(10),
            'last_message_id' => null,
        ];
    }

    /**
     * The one Scientific Community room.
     */
    public function room(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => ChatSettings::ROOM_NAME,
            'slug' => ChatSettings::ROOM_SLUG,
            'description' => ChatSettings::ROOM_DESCRIPTION,
        ]);
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'General',
            'slug' => 'general',
            'description' => 'Open discussion for approved members of the Scientific Community.',
        ]);
    }
}
