<?php

namespace Tests\Unit\Repositories;

use App\Constants\Chapters;
use App\Constants\Sections;
use App\Repositories\Contracts\ChapterRepositoryInterface;
use App\Repositories\Eloquent\ConstantChapterRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConstantChapterRepositoryTest extends TestCase
{
    private ChapterRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ChapterRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_constant_implementation(): void
    {
        $this->assertInstanceOf(ConstantChapterRepository::class, $this->repository);
    }

    #[Test]
    public function it_serves_the_five_chapters_in_order(): void
    {
        $this->assertSame(Chapters::all(), array_keys($this->repository->all()));
        $this->assertSame('Archive', $this->repository->find(Chapters::ARCHIVE)['label']);
        $this->assertNull($this->repository->find('nope'));
    }

    #[Test]
    public function it_resolves_the_chapter_of_a_section(): void
    {
        $this->assertSame(Chapters::LIFE, $this->repository->chapterOf(Sections::OTHER));
        $this->assertSame(Chapters::WORK, $this->repository->chapterOf(Sections::TEACHING));
        $this->assertSame(Chapters::TRIBUTES, $this->repository->chapterOf(Sections::TRIBUTES));
        $this->assertNull($this->repository->chapterOf('nope'));
    }

    #[Test]
    public function it_serves_descriptions_and_actions(): void
    {
        $this->assertSame(Chapters::descriptions()[Sections::VIDEOS], $this->repository->description(Sections::VIDEOS));
        $this->assertNull($this->repository->description('nope'));
        $this->assertSame('login', $this->repository->action(Chapters::ACTION_SIGN_IN)['route']);
        $this->assertNull($this->repository->action('nope'));
    }
}
