<?php

namespace Tests\Unit\Repositories;

use App\Models\Commentary;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use App\Repositories\Eloquent\EloquentCommentaryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentCommentaryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CommentaryRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(CommentaryRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentCommentaryRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_by_slug_and_checks_slug_existence(): void
    {
        $draft = Commentary::factory()->draft()->create(['slug' => 'draft-note']);
        $published = Commentary::factory()->published()->create(['slug' => 'on-teaching']);

        $this->assertTrue($draft->is($this->repository->findBySlug('draft-note')));
        $this->assertNull($this->repository->findPublishedBySlug('draft-note'));
        $this->assertTrue($published->is($this->repository->findPublishedBySlug('on-teaching')));

        $this->assertTrue($this->repository->slugExists('on-teaching'));
        $this->assertFalse($this->repository->slugExists('on-teaching', $published->id));
        $this->assertFalse($this->repository->slugExists('missing'));
    }

    #[Test]
    public function it_paginates_published_commentaries_optionally_by_tag(): void
    {
        Commentary::factory()->published()->tagged(['policy', 'sudan'])->create();
        Commentary::factory()->published()->tagged(['education'])->create();
        Commentary::factory()->draft()->tagged(['policy'])->create();

        $this->assertSame(2, $this->repository->paginatePublishedTagged()->total());
        $this->assertSame(2, $this->repository->paginatePublishedTagged('')->total());
        $this->assertSame(1, $this->repository->paginatePublishedTagged('policy')->total());
        $this->assertSame(0, $this->repository->paginatePublishedTagged('missing')->total());
    }

    #[Test]
    public function published_tags_are_unique_and_sorted(): void
    {
        Commentary::factory()->published()->tagged(['sudan', 'policy'])->create();
        Commentary::factory()->published()->tagged(['education', 'policy'])->create();
        Commentary::factory()->published()->create(['tags' => null]);
        Commentary::factory()->draft()->tagged(['hidden'])->create();

        $this->assertSame(['education', 'policy', 'sudan'], $this->repository->publishedTags());
    }
}
