<?php

namespace Tests\Unit\Repositories;

use App\Constants\Sections;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Eloquent\ConstantSectionRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConstantSectionRepositoryTest extends TestCase
{
    private SectionRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(SectionRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_constant_implementation(): void
    {
        $this->assertInstanceOf(ConstantSectionRepository::class, $this->repository);
    }

    #[Test]
    public function it_returns_the_eleven_sections_in_product_order(): void
    {
        $this->assertSame([
            Sections::ABOUT, Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS, Sections::VIDEOS,
            Sections::PICTURES, Sections::TEACHING, Sections::COMMENTARY, Sections::COMMUNITY,
            Sections::OTHER, Sections::TRIBUTES,
        ], array_keys($this->repository->all()));
    }

    #[Test]
    public function every_section_is_fully_described(): void
    {
        foreach ($this->repository->all() as $key => $section) {
            $this->assertSame(['label', 'title', 'path', 'route', 'placement', 'summary'], array_keys($section), $key);
            $this->assertStringStartsWith('/', $section['path'], $key);
            $this->assertContains($section['placement'], Sections::placements(), $key);
            $this->assertNotSame('', $section['label'], $key);
            $this->assertNotSame('', $section['title'], $key);
            $this->assertNotSame('', $section['summary'], $key);
        }

        $routes = array_column($this->repository->all(), 'route');
        $this->assertSame($routes, array_unique($routes), 'Route names are unique');
    }

    #[Test]
    public function it_finds_a_section_by_key(): void
    {
        $this->assertSame('CV & Achievements', $this->repository->find(Sections::CV)['label']);
        $this->assertSame('CV and Achievements', $this->repository->find(Sections::CV)['title']);
        $this->assertNull($this->repository->find('nope'));
    }

    #[Test]
    public function only_other_information_lives_in_the_footer(): void
    {
        $this->assertSame([Sections::OTHER], array_keys($this->repository->withPlacement(Sections::PLACEMENT_FOOTER)));
        $this->assertCount(10, $this->repository->withPlacement(Sections::PLACEMENT_PRIMARY));
        $this->assertArrayNotHasKey(Sections::OTHER, $this->repository->withPlacement(Sections::PLACEMENT_PRIMARY));
    }
}
