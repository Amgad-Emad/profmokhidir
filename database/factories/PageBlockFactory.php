<?php

namespace Database\Factories;

use App\Constants\PageBlocks;
use App\Constants\Pages;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageBlock>
 */
class PageBlockFactory extends Factory
{
    /**
     * A short text block of the home page holding placeholder copy.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $definition = PageBlocks::get(Pages::HOME, 'hero_label');

        return [
            'page_id' => Page::factory()->page(Pages::HOME),
            'key' => $definition->key,
            'type' => $definition->type,
            'value' => '[Placeholder] '.fake()->words(2, true),
            'draft_value' => null,
            'sort_order' => 1,
        ];
    }

    /**
     * One declared block of the given page, holding its default copy.
     */
    public function block(Page $page, string $key): static
    {
        $definition = PageBlocks::get($page->key, $key);

        return $this->state(fn (array $attributes) => [
            'page_id' => $page->getKey(),
            'key' => $key,
            'type' => $definition->type,
            'value' => $definition->default,
            'sort_order' => (int) array_search($key, array_keys(PageBlocks::for($page->key)), true) + 1,
        ]);
    }

    /**
     * An unpublished change waiting beside the live value.
     */
    public function draft(mixed $value): static
    {
        return $this->state(fn (array $attributes) => ['draft_value' => $value]);
    }

    /**
     * A live value other than the default.
     */
    public function value(mixed $value): static
    {
        return $this->state(fn (array $attributes) => ['value' => $value]);
    }
}
