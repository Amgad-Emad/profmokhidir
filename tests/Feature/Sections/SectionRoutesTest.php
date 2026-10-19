<?php

namespace Tests\Feature\Sections;

use App\Constants\Sections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every public section without its own controller yet has a placeholder page on
 * the shared layout, so the navigation works end to end. Sections with a real
 * page are covered by their own feature tests in tests/Feature/Pages.
 */
class SectionRoutesTest extends TestCase
{
    use RefreshDatabase;

    /** Sections that have their own controller and view. */
    private const array BUILT = [
        Sections::ABOUT, Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS,
        Sections::TEACHING, Sections::COMMENTARY, Sections::OTHER,
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function sections(): iterable
    {
        foreach (Sections::all() as $key) {
            yield $key => [$key];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function placeholderSections(): iterable
    {
        foreach (Sections::all() as $key) {
            if (! in_array($key, self::BUILT, true)) {
                yield $key => [$key];
            }
        }
    }

    #[Test]
    #[DataProvider('placeholderSections')]
    public function each_placeholder_section_page_renders_its_title_and_empty_state(string $key): void
    {
        $section = Sections::definitions()[$key];

        $response = $this->get($section['path'])->assertOk();

        $response
            ->assertSee('<title>'.e($section['title']).' · ', false)
            ->assertSee('<meta name="description" content="'.e($section['summary']).'">', false)
            ->assertSeeInOrder(['section-header', e($section['label']), 'rule-brass', 't-heading', e($section['title'])], false)
            ->assertSee('id="section-title"', false)
            ->assertSee('Nothing has been published in this section yet.')
            ->assertDontSee('livewire', false);

        $this->assertSame(url($section['path']), route($section['route']));
    }

    #[Test]
    #[DataProvider('sections')]
    public function the_current_section_is_marked_in_the_navigation(string $key): void
    {
        $section = Sections::definitions()[$key];
        $html = $this->get($section['path'])->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches[1], 'The current section carries aria-current="page"');
        $this->assertSame([route($section['route'])], array_values(array_unique($matches[1])), 'Only the current section is marked');
    }

    #[Test]
    public function the_home_page_links_to_the_about_section_and_marks_nothing_current(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('about').'"', false)
            ->assertDontSee('aria-current="page"', false);
    }
}
