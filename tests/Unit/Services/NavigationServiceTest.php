<?php

namespace Tests\Unit\Services;

use App\Constants\Sections;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Eloquent\ConstantSectionRepository;
use App\Services\NavigationService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NavigationServiceTest extends TestCase
{
    private NavigationService $navigation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->navigation = $this->app->make(NavigationService::class);
    }

    #[Test]
    public function primary_items_follow_the_brief_order_and_exclude_other_information(): void
    {
        $labels = array_column($this->navigation->primary(), 'label');

        $this->assertSame([
            'About', 'CV & Achievements', 'Publications', 'Awards', 'Videos',
            'Pictures', 'Teaching', 'Commentary', 'Community', 'Tributes',
        ], $labels);
    }

    #[Test]
    public function all_items_include_other_information_before_tributes(): void
    {
        $keys = array_column($this->navigation->all(), 'key');

        $this->assertCount(11, $keys);
        $this->assertSame(Sections::OTHER, $keys[9]);
        $this->assertSame(Sections::TRIBUTES, $keys[10]);
        $this->assertSame([Sections::OTHER], array_column($this->navigation->footerOnly(), 'key'));
    }

    #[Test]
    public function items_carry_resolved_urls_and_no_item_is_active_without_a_route(): void
    {
        foreach ($this->navigation->all() as $item) {
            $this->assertSame(route($item['route']), $item['url']);
            $this->assertFalse($item['active']);
        }

        $this->assertSame(url('/other-information'), $this->navigation->section(Sections::OTHER)['url']);
    }

    #[Test]
    public function the_current_route_marks_exactly_one_item_active(): void
    {
        $items = $this->navigation->primary('awards');
        $active = array_values(array_filter($items, fn (array $item): bool => $item['active']));

        $this->assertCount(1, $active);
        $this->assertSame(Sections::AWARDS, $active[0]['key']);
    }

    #[Test]
    public function nested_routes_keep_their_section_active(): void
    {
        $this->assertTrue($this->navigation->isActive('publications', 'publications'));
        $this->assertTrue($this->navigation->isActive('publications', 'publications.show'));
        $this->assertFalse($this->navigation->isActive('publications', 'publications-archive'));
        $this->assertFalse($this->navigation->isActive('cv', 'home'));
        $this->assertFalse($this->navigation->isActive('cv', null));
    }

    #[Test]
    public function section_returns_one_item_and_rejects_unknown_keys(): void
    {
        $item = $this->navigation->section(Sections::TEACHING, 'teaching');

        $this->assertSame('Teaching Materials', $item['title']);
        $this->assertSame('Teaching', $item['label']);
        $this->assertTrue($item['active']);

        $this->expectException(InvalidArgumentException::class);
        $this->navigation->section('not-a-section');
    }

    #[Test]
    public function it_reads_sections_through_the_repository_interface(): void
    {
        $repository = $this->createMock(SectionRepositoryInterface::class);
        $repository->expects($this->once())->method('withPlacement')->with(Sections::PLACEMENT_PRIMARY)->willReturn([
            'awards' => (new ConstantSectionRepository)->find(Sections::AWARDS),
        ]);

        $items = (new NavigationService($repository))->primary();

        $this->assertCount(1, $items);
        $this->assertSame('Awards', $items[0]['label']);
    }
}
