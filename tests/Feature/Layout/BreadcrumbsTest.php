<?php

namespace Tests\Feature\Layout;

use App\Constants\Breadcrumbs;
use App\Constants\Search;
use App\Models\Commentary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_home_page_has_no_trail(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('aria-label="'.Breadcrumbs::ARIA_LABEL.'"', $html);
        $this->assertStringNotContainsString(Breadcrumbs::SCHEMA_LIST, $html);
    }

    #[Test]
    public function a_section_page_shows_home_and_the_section_in_small_caps_with_structured_data(): void
    {
        $html = $this->get(route('awards'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<main id="main" tabindex="-1" class="pt-20">\s*<div class="breadcrumbs-band">\s*<div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">\s*<nav class="breadcrumbs" aria-label="Breadcrumb">/', $html);
        $this->assertStringContainsString('<a href="'.route('home').'" class="breadcrumbs__link link-draw smallcaps">'.Breadcrumbs::HOME_LABEL.'</a>', $html);
        $this->assertStringContainsString('<span class="breadcrumbs__separator" aria-hidden="true">'.Breadcrumbs::SEPARATOR.'</span>', $html);
        $this->assertStringContainsString('<a href="'.route('awards').'" class="breadcrumbs__link smallcaps" aria-current="page">Awards</a>', $html);

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $jsonLd);
        $schema = json_decode($jsonLd[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(Breadcrumbs::SCHEMA_LIST, $schema['@type']);
        $this->assertSame([[1, 'Home', route('home')], [2, 'Awards', route('awards')]], array_map(
            fn (array $item): array => [$item['position'], $item['name'], $item['item']],
            $schema['itemListElement'],
        ));
    }

    #[Test]
    public function a_detail_page_adds_its_title_and_joins_the_breadcrumb_list_to_the_page_schema(): void
    {
        Commentary::factory()->published()->create(['title' => 'On teaching', 'slug' => 'on-teaching']);

        $html = $this->get(route('commentary.show', 'on-teaching'))->assertOk()->getContent();

        preg_match_all('/class="breadcrumbs__link[^"]*"(?: aria-current="page")?>([^<]+)<\/a>/', $html, $crumbs);
        $this->assertSame(['Home', 'Commentary', 'On teaching'], $crumbs[1]);
        $this->assertStringContainsString('class="breadcrumbs__link smallcaps" aria-current="page">On teaching</a>', $html);

        // The publications page already prints its own schema; the trail joins it in one graph.
        $publications = $this->get(route('publications'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($publications, '<script type="application/ld+json">'));
        $this->assertStringContainsString(Breadcrumbs::SCHEMA_LIST, $publications);
    }

    #[Test]
    public function standalone_and_nested_pages_use_the_title_they_pass(): void
    {
        $this->get(route(Search::ROUTE))->assertOk()
            ->assertSee('class="breadcrumbs__link smallcaps" aria-current="page">'.Search::TITLE.'</a>', false);

        preg_match_all('/class="breadcrumbs__link[^"]*"(?: aria-current="page")?>([^<]+)<\/a>/', $this->get(route('tributes.create'))->assertOk()->getContent(), $crumbs);
        $this->assertSame(['Home', 'Tributes'], array_slice($crumbs[1], 0, 2));
        $this->assertCount(3, $crumbs[1]);
    }
}
