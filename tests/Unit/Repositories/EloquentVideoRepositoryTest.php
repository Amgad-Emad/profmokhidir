<?php

namespace Tests\Unit\Repositories;

use App\Constants\VideoProvider;
use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Repositories\Eloquent\EloquentVideoRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentVideoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private VideoRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(VideoRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentVideoRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_by_slug_and_checks_slug_existence(): void
    {
        $draft = Video::factory()->draft()->create(['slug' => 'inaugural-lecture']);
        $published = Video::factory()->published()->create(['slug' => 'keynote']);

        $this->assertTrue($draft->is($this->repository->findBySlug('inaugural-lecture')));
        $this->assertTrue($this->repository->findBySlug('inaugural-lecture')->relationLoaded('media'));
        $this->assertNull($this->repository->findPublishedBySlug('inaugural-lecture'));
        $this->assertTrue($published->is($this->repository->findPublishedBySlug('keynote')));

        $this->assertTrue($this->repository->slugExists('keynote'));
        $this->assertFalse($this->repository->slugExists('keynote', $published->id));
        $this->assertFalse($this->repository->slugExists('missing'));
    }

    #[Test]
    public function published_videos_eager_load_media_and_order_by_sort_order_then_date_desc(): void
    {
        $older = Video::factory()->published()->sortOrder(1)->create(['date' => '2001-01-01']);
        $newer = Video::factory()->published()->sortOrder(1)->create(['date' => '2015-06-01']);
        $last = Video::factory()->published()->sortOrder(3)->create(['date' => '2020-01-01']);
        Video::factory()->draft()->create();

        $published = $this->repository->published();

        $this->assertSame([$newer->id, $older->id, $last->id], $published->pluck('id')->all());
        $this->assertTrue($published->every(fn (Video $video): bool => $video->relationLoaded('media')));
    }

    #[Test]
    public function search_filters_by_provider(): void
    {
        Video::factory()->provider(VideoProvider::YouTube)->create(['title' => 'Lecture one']);
        Video::factory()->provider(VideoProvider::Vimeo)->create(['title' => 'Lecture two']);

        $this->assertSame(2, $this->repository->search('Lecture')->total());
        $this->assertSame(1, $this->repository->search('Lecture', ['provider' => VideoProvider::Vimeo])->total());
    }
}
