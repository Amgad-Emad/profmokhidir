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
            'epigraph' => null,
            'epigraph_attribution' => null,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function withEpigraph(string $epigraph = '[Placeholder] Knowledge is the light we pass on.', ?string $attribution = 'Prof. Mohamed Osman Khidir'): static
    {
        return $this->state(fn (array $attributes) => ['epigraph' => $epigraph, 'epigraph_attribution' => $attribution]);
    }

    /**
     * A long-form body with two headings (a table of contents) and a footnote marker with its note.
     */
    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'body' => '<p>[Placeholder] '.fake()->paragraph(8).'[^1]</p>'
                .'<h2>[Placeholder] The first part</h2><p>'.fake()->paragraph(10).'</p>'
                .'<h2>[Placeholder] The second part</h2><p>'.fake()->paragraph(10).'</p>'
                .'<p>[^1]: [Placeholder] A note on the opening paragraph.</p>',
        ]);
    }

    /**
     * @param  list<string>  $tags
     */
    public function tagged(array $tags): static
    {
        return $this->state(fn (array $attributes) => ['tags' => $tags]);
    }
}
