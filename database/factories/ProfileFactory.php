<?php

namespace Database\Factories;

use App\Constants\MediaProperties;
use App\Models\Profile;
use App\Services\ProfileService;
use App\Support\PlaceholderImage;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    use HasPublishingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Mohamed Osman Khidir',
            'title' => 'Professor of Agronomy',
            'affiliation' => '[Placeholder] University of Khartoum',
            'short_bio' => '[Placeholder] '.fake()->paragraph(3),
            'full_bio' => '<p>[Placeholder] '.fake()->paragraph(6).'</p><p>'.fake()->paragraph(5).'</p>',
            'expertise' => ['Agronomy', 'Soil fertility', 'Crop physiology'],
            'key_facts' => [
                ['label' => 'Born', 'value' => '[Placeholder] 1940'],
                ['label' => 'Field', 'value' => 'Agronomy'],
            ],
            'contact_email_public' => null,
            'contact_note' => null,
            'sort_order' => 1,
        ];
    }

    public function withContact(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_email_public' => 'office@example.com',
            'contact_note' => '[Placeholder] Correspondence is handled by the family office.',
        ]);
    }

    /**
     * Attach a grey placeholder portrait through the service.
     */
    public function withPortrait(): static
    {
        return $this->afterCreating(function (Profile $profile): void {
            app(ProfileService::class)->setPortrait($profile, PlaceholderImage::grey(800, 1000, 'Portrait'), [
                MediaProperties::ALT_TEXT => 'Placeholder portrait of Professor Mohamed Osman Khidir',
                MediaProperties::CAPTION => '[Placeholder] Portrait',
                MediaProperties::PLACE => 'Khartoum',
                MediaProperties::YEAR => 1998,
            ]);
        });
    }
}
