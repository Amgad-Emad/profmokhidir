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

    #[Test]
    public function neighbours_follow_listing_order_and_skip_drafts(): void
    {
        $first = Commentary::factory()->published()->create(['sort_order' => 1, 'date' => '2022-01-01']);
        $second = Commentary::factory()->published()->create(['sort_order' => 2, 'date' => '2021-06-01']);
        Commentary::factory()->draft()->create(['sort_order' => 3, 'date' => '2021-03-01']);
        $third = Commentary::factory()->published()->create(['sort_order' => 4, 'date' => '2020-01-01']);
        $draft = Commentary::factory()->draft()->create(['sort_order' => 5]);

        $middle = $this->repository->neighbours($second);
        $this->assertTrue($middle['previous']->is($first));
        $this->assertTrue($middle['next']->is($third));

        $this->assertNull($this->repository->neighbours($first)['previous']);
        $this->assertTrue($this->repository->neighbours($first)['next']->is($second));

        $this->assertTrue($this->repository->neighbours($third)['previous']->is($second));
        $this->assertNull($this->repository->neighbours($third)['next']);

        $this->assertSame(['previous' => null, 'next' => null], $this->repository->neighbours($draft));
    }

    #[Test]
    public function related_commentaries_share_a_tag_regardless_of_case_most_shared_first_then_newest(): void
    {
        $current = Commentary::factory()->published()->tagged(['Education', 'Policy'])->create(['title' => 'Current']);
        Commentary::factory()->published()->tagged(['education'])->create(['title' => 'One tag, newer', 'date' => '2020-01-01']);
        Commentary::factory()->published()->tagged(['Policy', 'EDUCATION', 'Sudan'])->create(['title' => 'Two tags', 'date' => '1999-01-01']);
        Commentary::factory()->published()->tagged(['Policy'])->create(['title' => 'One tag, older', 'date' => '2010-01-01']);
        Commentary::factory()->published()->tagged(['Sudan'])->create(['title' => 'No shared tag']);
        Commentary::factory()->published()->create(['title' => 'Untagged', 'tags' => null]);
        Commentary::factory()->draft()->tagged(['Education'])->create(['title' => 'Draft']);

        $this->assertSame(['Two tags', 'One tag, newer', 'One tag, older'], $this->repository->publishedRelated($current, 5)->pluck('title')->all());
        $this->assertSame(['Two tags'], $this->repository->publishedRelated($current, 1)->pluck('title')->all());
        $this->assertTrue($this->repository->publishedRelated(Commentary::factory()->published()->create(['tags' => []]), 3)->isEmpty());
    }
}
