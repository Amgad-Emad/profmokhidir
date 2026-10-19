<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\ContentStatus;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\User;
use App\Services\AwardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Exercises AbstractContentService through its thinnest subclass.
 */
class AwardServiceTest extends TestCase
{
    use RefreshDatabase;

    private AwardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AwardService::class);
    }

    #[Test]
    public function create_defaults_to_draft_with_the_next_sort_order(): void
    {
        Award::factory()->create(['sort_order' => 7]);

        $award = $this->service->create(['name' => 'Medal', 'year' => 2001]);

        $this->assertSame(ContentStatus::Draft, $award->status);
        $this->assertNull($award->published_at);
        $this->assertSame(8, $award->sort_order);
    }

    #[Test]
    public function create_with_a_published_status_string_sets_published_at(): void
    {
        $award = $this->service->create(['name' => 'Medal', 'status' => 'published']);

        $this->assertSame(ContentStatus::Published, $award->status);
        $this->assertNotNull($award->published_at);
    }

    #[Test]
    public function update_to_published_sets_published_at_once_and_keeps_it(): void
    {
        $award = $this->service->create(['name' => 'Medal']);

        $award = $this->service->update($award, ['status' => ContentStatus::Published]);
        $first = $award->published_at;
        $this->assertNotNull($first);

        $this->travel(1)->days();
        $award = $this->service->update($award, ['name' => 'Renamed', 'status' => 'published']);

        $this->assertSame('Renamed', $award->name);
        $this->assertTrue($first->equalTo($award->published_at));
    }

    #[Test]
    public function publish_and_unpublish_toggle_status_and_are_audited(): void
    {
        $actor = User::factory()->create();
        $award = Award::factory()->draft()->create();

        $award = $this->service->publish($award, $actor);
        $this->assertTrue($award->isPublished());
        $this->assertNotNull($award->published_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => AuditAction::CONTENT_PUBLISHED,
            'subject_type' => $award->getMorphClass(),
            'subject_id' => $award->id,
        ]);

        $award = $this->service->unpublish($award, $actor);
        $this->assertTrue($award->isDraft());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CONTENT_UNPUBLISHED, 'subject_id' => $award->id]);
    }

    #[Test]
    public function delete_is_audited_with_a_label(): void
    {
        $actor = User::factory()->create();
        $award = Award::factory()->create(['name' => 'Gone']);

        $this->assertTrue($this->service->delete($award, $actor));

        $this->assertSoftDeleted('awards', ['id' => $award->id]);
        $log = AuditLog::query()->where('action', AuditAction::CONTENT_DELETED)->where('subject_id', $award->id)->firstOrFail();
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame(['label' => 'Gone'], $log->meta);
    }

    #[Test]
    public function reorder_writes_sequential_sort_orders(): void
    {
        $a = Award::factory()->create(['sort_order' => 1]);
        $b = Award::factory()->create(['sort_order' => 2]);
        $c = Award::factory()->create(['sort_order' => 3]);

        $this->service->reorder([$c->id, $a->id, $b->id]);

        $this->assertSame(1, $c->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(3, $b->fresh()->sort_order);
    }

    #[Test]
    public function published_is_cached_and_flushed_on_every_write(): void
    {
        Award::factory()->published()->create();
        Award::factory()->draft()->create();

        $this->assertCount(1, $this->service->published());
        $this->assertTrue(Cache::has(CacheKeys::AWARDS));

        $created = $this->service->create(['name' => 'New']);
        $this->assertFalse(Cache::has(CacheKeys::AWARDS));

        $this->service->published();
        $this->service->update($created, ['name' => 'Renamed']);
        $this->assertFalse(Cache::has(CacheKeys::AWARDS));

        $this->service->published();
        $this->service->publish($created);
        $this->assertFalse(Cache::has(CacheKeys::AWARDS));
        $this->assertCount(2, $this->service->published());

        $this->service->reorder([$created->id]);
        $this->assertFalse(Cache::has(CacheKeys::AWARDS));

        $this->service->published();
        $this->service->delete($created);
        $this->assertFalse(Cache::has(CacheKeys::AWARDS));
        $this->assertCount(1, $this->service->published());
    }

    #[Test]
    public function search_find_and_counts_delegate_to_the_repository(): void
    {
        $match = Award::factory()->published()->create(['name' => 'Golden Plough']);
        Award::factory()->draft()->create(['name' => 'Silver Spade']);

        $this->assertSame([$match->id], $this->service->search('plough')->pluck('id')->all());
        $this->assertSame(1, $this->service->search(null, ['status' => ContentStatus::Draft])->total());
        $this->assertSame(1, $this->service->countPublished());
        $this->assertTrue($this->service->find($match->id)->is($match));
        $this->assertNull($this->service->find(999));
        $this->assertTrue($this->service->findOrFail($match->id)->is($match));
    }
}
