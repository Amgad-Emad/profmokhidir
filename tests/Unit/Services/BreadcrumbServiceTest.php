<?php

namespace Tests\Unit\Services;

use App\Constants\Breadcrumbs;
use App\Services\BreadcrumbService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BreadcrumbServiceTest extends TestCase
{
    private BreadcrumbService $breadcrumbs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->breadcrumbs = $this->app->make(BreadcrumbService::class);
    }

    #[Test]
    public function the_home_page_and_unnamed_routes_have_no_trail(): void
    {
        $this->assertSame([], $this->breadcrumbs->trail(null));
        $this->assertSame([], $this->breadcrumbs->trail('home'));
        $this->assertNull($this->breadcrumbs->schema([]));
    }

    #[Test]
    public function a_section_index_is_home_then_the_section_as_the_current_page(): void
    {
        $this->assertSame([
            ['label' => Breadcrumbs::HOME_LABEL, 'url' => route('home'), 'current' => false],
            ['label' => 'About', 'url' => route('about'), 'current' => true],
        ], $this->breadcrumbs->trail('about', 'About Prof. Mokhidir'));
    }

    #[Test]
    public function a_nested_route_adds_the_page_title_after_its_section(): void
    {
        $trail = $this->breadcrumbs->trail('commentary.show', 'On teaching');

        $this->assertSame(['Home', 'Commentary', 'On teaching'], array_column($trail, 'label'));
        $this->assertSame([false, false, true], array_column($trail, 'current'));
        $this->assertSame(route('commentary'), $trail[1]['url']);
    }

    #[Test]
    public function a_nested_route_without_a_title_falls_back_to_its_last_segment(): void
    {
        $this->assertSame(['Home', 'Tributes', 'Preview'], array_column($this->breadcrumbs->trail('tributes.preview.show'), 'label'));
        $this->assertSame(['Home', 'Tributes', 'Thank you'], array_column($this->breadcrumbs->trail('tributes.thank-you'), 'label'));
    }

    #[Test]
    public function standalone_pages_sit_directly_under_home(): void
    {
        $this->assertSame(['Home', 'Search'], array_column($this->breadcrumbs->trail('search'), 'label'));
        $this->assertSame(['Home', 'Account'], array_column($this->breadcrumbs->trail('account.show'), 'label'));
        $this->assertSame(['Home', 'Search'], array_column($this->breadcrumbs->trail('search', 'Search'), 'label'));
    }

    #[Test]
    public function an_unknown_route_shows_home_and_the_title_when_one_is_given(): void
    {
        $this->assertSame(['Home', 'Style guide'], array_column($this->breadcrumbs->trail('styleguide', 'Style guide'), 'label'));
        $this->assertSame(['Home'], array_column($this->breadcrumbs->trail('styleguide'), 'label'));
    }

    #[Test]
    public function the_schema_is_a_breadcrumb_list_with_positions(): void
    {
        $schema = $this->breadcrumbs->schema($this->breadcrumbs->trail('commentary.show', 'On teaching'));

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame(Breadcrumbs::SCHEMA_LIST, $schema['@type']);
        $this->assertSame([1, 2, 3], array_column($schema['itemListElement'], 'position'));
        $this->assertSame(['Home', 'Commentary', 'On teaching'], array_column($schema['itemListElement'], 'name'));
        $this->assertSame(route('commentary'), $schema['itemListElement'][1]['item']);
        $this->assertSame(Breadcrumbs::SCHEMA_ITEM, $schema['itemListElement'][0]['@type']);
    }
}
