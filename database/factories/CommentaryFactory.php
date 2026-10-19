<?php

namespace Database\Factories;

use App\Models\Commentary;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Commentary>
 */
class CommentaryFactory extends Factory
{
    use HasPublishingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = '[Placeholder] '.rtrim(fake()->sentence(6), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'date' => fake()->dateTimeBetween('-10 years', '-1 week')->format('Y-m-d'),
            'summary' => '[Placeholder] '.fake()->sentence(18),
            'body' => '<p>[Placeholder] '.fake()->paragraph(6).'</p><blockquote>'.fake()->sentence(10).'</blockquote><p>'.fake()->paragraph(4).'</p>',
            'external_url' => fake()->boolean(30) ? fake()->url() : null,
            'tags' => fake()->randomElements(['agriculture', 'education', 'policy', 'sudan', 'research', 'mentorship'], fake()->numberBetween(1, 3)),
            'byline' => 'Prof. Mohamed Osman Khidir',
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    /**
     * @param  list<string>  $tags
     */
    public function tagged(array $tags): static
    {
        return $this->state(fn (array $attributes) => ['tags' => $tags]);
    }
}
