<?php

namespace Database\Factories;

use App\Constants\SettingKeys;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->randomElement(SettingKeys::all()),
            'value' => fake()->safeEmail(),
        ];
    }

    public function key(string $key, ?string $value = null): static
    {
        return $this->state(fn (array $attributes) => ['key' => $key, 'value' => $value]);
    }

    public function contactEmail(string $value = 'contact@example.com'): static
    {
        return $this->key(SettingKeys::CONTACT_EMAIL, $value);
    }

    public function notificationEmail(string $value = 'notices@example.com'): static
    {
        return $this->key(SettingKeys::NOTIFICATION_EMAIL, $value);
    }

    public function mailSender(string $name = 'Prof. Mohamed Osman Khidir', string $address = 'no-reply@example.com'): static
    {
        return $this->key(SettingKeys::MAIL_FROM_NAME, $name)->afterCreating(function (Setting $setting) use ($address): void {
            Setting::query()->updateOrCreate(['key' => SettingKeys::MAIL_FROM_ADDRESS], ['value' => $address]);
        });
    }
}
