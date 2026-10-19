<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Models\Commentary;
use App\Services\CommentaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommentaryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CommentaryService::class);
    }

    #[Test]
    public function normalise_tags_trims_splits_and_deduplicates_case_insensitively(): void
    {
        $this->assertSame(['Policy', 'sudan', 'research'], $this->service->normaliseTags(' Policy, sudan ,, policy,research '));
        $this->assertSame(['a', 'b'], $this->service->normaliseTags(['a', ' A ', 'b', '']));
        $this->assertSame([], $this->service->normaliseTags(null));
    }

    #[Test]
    public function create_slugs_and_normalises_tags(): void
    {
        $first = $this->service->create(['title' => 'A Letter', 'tags' => 'education, Mentorship']);
        $second = $this->service->create(['title' => 'A Letter']);

        $this->assertSame('a-letter', $first->slug);
        $this->assertSame('a-letter-2', $second->slug);
        $this->assertSame(['education', 'Mentorship'], $first->tags);
    }

    #[Test]
    public function paginate_published_filters_by_tag(): void
    {
        Commentary::factory()->published()->tagged(['policy'])->create();
        Commentary::factory()->published()->tagged(['policy', 'sudan'])->create();
        Commentary::factory()->published()->tagged(['research'])->create();
        Commentary::factory()->draft()->tagged(['policy'])->create();

        $this->assertSame(3, $this->service->paginatePublished()->total());
        $this->assertSame(2, $this->service->paginatePublished('policy')->total());
        $this->assertSame(1, $this->service->paginatePublished('sudan')->total());
    }

    #[Test]
    public function tags_are_cached_and_flushed_on_create(): void
    {
        Commentary::factory()->published()->tagged(['sudan', 'policy'])->create();
        Commentary::factory()->draft()->tagged(['hidden'])->create();

        $this->assertSame(['policy', 'sudan'], $this->service->tags());
        $this->assertTrue(Cache::has(CacheKeys::COMMENTARIES.'.tags'));

        $this->service->create(['title' => 'New', 'tags' => ['agriculture'], 'status' => 'published']);

        $this->assertFalse(Cache::has(CacheKeys::COMMENTARIES.'.tags'));
        $this->assertSame(['agriculture', 'policy', 'sudan'], $this->service->tags());
    }

    #[Test]
    public function find_published_by_slug_ignores_drafts(): void
    {
        $published = Commentary::factory()->published()->create(['slug' => 'on-teaching']);
        Commentary::factory()->draft()->create(['slug' => 'draft']);

        $this->assertTrue($this->service->findPublishedBySlug('on-teaching')->is($published));
        $this->assertNull($this->service->findPublishedBySlug('draft'));
    }
}
