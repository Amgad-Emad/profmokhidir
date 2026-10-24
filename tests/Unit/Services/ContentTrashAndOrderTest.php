<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\User;
use App\Services\AwardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shared content service behaviour, exercised through awards: audited
 * creation and status changes, trash and restore, moving and reordering.
 */
class ContentTrashAndOrderTest extends TestCase
{
    use RefreshDatabase;

    private AwardService $service;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AwardService::class);
        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function creating_is_audited_and_a_published_creation_is_audited_twice(): void
    {
        $draft = $this->service->create(['name' => 'Medal'], $this->editor);
        $published = $this->service->create(['name' => 'Prize', 'status' => 'published'], $this->editor);

        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_CREATED)->where('subject_id', $draft->getKey())->count());
        $this->assertSame(0, AuditLog::query()->where('action', AuditAction::CONTENT_PUBLISHED)->where('subject_id', $draft->getKey())->count());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_PUBLISHED)->where('subject_id', $published->getKey())->count());
        $this->assertSame($this->editor->getKey(), AuditLog::query()->latest('id')->first()->user_id);
        $this->assertSame('Prize', AuditLog::query()->latest('id')->first()->meta['label']);
    }

    #[Test]
    public function changing_status_through_update_is_audited_like_publish_and_unpublish(): void
    {
        $award = Award::factory()->draft()->create();

        $award = $this->service->update($award, ['status' => 'published'], $this->editor);
        $this->assertTrue($award->isPublished());
        $this->assertNotNull($award->published_at);
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_PUBLISHED)->count());

        // A plain edit that keeps the status is not a status change.
        $this->service->update($award, ['name' => 'Renamed', 'status' => 'published'], $this->editor);
        $this->assertSame(1, AuditLog::query()->count());

        $award = $this->service->update($award->fresh(), ['status' => 'draft'], $this->editor);
        $this->assertTrue($award->isDraft());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_UNPUBLISHED)->count());
        $this->assertSame(2, AuditLog::query()->count());
    }

    #[Test]
    public function deleting_moves_to_the_trash_and_restoring_brings_it_back_with_audit_entries(): void
    {
        $award = Award::factory()->published()->create(['name' => 'Gold']);

        $this->assertTrue($this->service->delete($award, $this->editor));
        $this->assertSoftDeleted($award);
        $this->assertNull($this->service->find($award->getKey()));
        $this->assertSame(0, $this->service->countPublished());
        $this->assertSame(1, $this->service->countTrashed());
        $this->assertSame([$award->getKey()], $this->service->trashed()->pluck('id')->all());
        $this->assertSame([$award->getKey()], $this->service->trashed('Gol')->pluck('id')->all());
        $this->assertSame([], $this->service->trashed('Silver')->pluck('id')->all());

        $restored = $this->service->restore($award->getKey(), $this->editor);

        $this->assertNull($restored->deleted_at);
        $this->assertSame(0, $this->service->countTrashed());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_DELETED)->count());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_RESTORED)->where('subject_id', $award->getKey())->count());
    }

    #[Test]
    public function move_shifts_a_record_within_the_display_order(): void
    {
        [$a, $b, $c] = [
            Award::factory()->sortOrder(1)->create(['name' => 'A']),
            Award::factory()->sortOrder(2)->create(['name' => 'B']),
            Award::factory()->sortOrder(3)->create(['name' => 'C']),
        ];

        $this->service->move($c, -1);
        $this->assertSame(['A', 'C', 'B'], $this->orderedNames());

        $this->service->move($a, 1);
        $this->assertSame(['C', 'A', 'B'], $this->orderedNames());

        $this->service->move($a, -10);
        $this->assertSame(['A', 'C', 'B'], $this->orderedNames());

        $this->service->move($a, 0);
        $this->assertSame(['A', 'C', 'B'], $this->orderedNames());
    }

    #[Test]
    public function reorder_within_changes_only_the_positions_of_the_given_subset(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $index => $name) {
            Award::factory()->sortOrder($index + 1)->create(['name' => $name]);
        }

        $ids = Award::query()->orderBy('sort_order')->pluck('id', 'name');

        // Reverse the middle page (B, C, D); A and E stay put.
        $this->service->reorderWithin([$ids['D'], $ids['C'], $ids['B']]);

        $this->assertSame(['A', 'D', 'C', 'B', 'E'], $this->orderedNames());
        $this->assertSame([1, 2, 3, 4, 5], Award::query()->orderBy('sort_order')->pluck('sort_order')->all());

        // Unknown ids are ignored.
        $this->service->reorderWithin([$ids['E'], 999999, $ids['A']]);
        $this->assertSame(['E', 'D', 'C', 'B', 'A'], $this->orderedNames());
    }

    #[Test]
    public function search_sorts_by_a_sortable_column_and_lists_distinct_filter_values(): void
    {
        Award::factory()->create(['name' => 'Beta', 'year' => 2001]);
        Award::factory()->create(['name' => 'Alpha', 'year' => 1999]);
        Award::factory()->create(['name' => 'Gamma', 'year' => 2001]);

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->service->search(null, [], 20, 'name')->pluck('name')->all());
        $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->service->search(null, [], 20, 'name', 'desc')->pluck('name')->all());
        $this->assertSame([2001, 1999], $this->service->distinct('year'));
        $this->assertContains('name', $this->service->sortableColumns());
        $this->assertContains('status', $this->service->sortableColumns());
        $this->assertSame(['status', 'year'], $this->service->filterColumns());
    }

    #[Test]
    public function new_records_are_appended_after_trashed_ones_too(): void
    {
        $trashed = Award::factory()->sortOrder(7)->create();
        $trashed->delete();

        $award = $this->service->create(['name' => 'New']);

        $this->assertSame(8, $award->sort_order);
    }

    #[Test]
    public function publishing_still_records_the_status_change_with_the_actor(): void
    {
        $award = Award::factory()->draft()->create();

        $this->service->publish($award, $this->editor);
        $this->service->unpublish($award->fresh(), $this->editor);

        $this->assertSame([AuditAction::CONTENT_PUBLISHED, AuditAction::CONTENT_UNPUBLISHED], AuditLog::query()->orderBy('id')->pluck('action')->all());
        $this->assertSame(ContentStatus::Draft, $award->fresh()->status);
    }

    /**
     * @return list<string>
     */
    private function orderedNames(): array
    {
        return Award::query()->ordered()->pluck('name')->all();
    }

    #[Test]
    public function force_delete_purges_a_trashed_record_with_its_revisions_and_is_audited(): void
    {
        $award = Award::factory()->create(['name' => 'Old prize']);
        \App\Models\Revision::factory()->count(2)->of($award)->create();
        $this->service->delete($award, $this->editor);

        $this->service->forceDelete($award->getKey(), $this->editor);

        $this->assertNull(Award::withTrashed()->find($award->getKey()));
        $this->assertSame(0, \App\Models\Revision::query()->where('revisionable_id', $award->getKey())->count());
        $log = AuditLog::query()->where('action', AuditAction::CONTENT_PURGED)->firstOrFail();
        $this->assertSame($award->getKey(), (int) $log->subject_id);
        $this->assertSame('Old prize', $log->meta['label']);
    }

    #[Test]
    public function force_delete_refuses_a_record_that_is_not_in_the_trash(): void
    {
        $award = Award::factory()->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            $this->service->forceDelete($award->getKey(), $this->editor);
        } finally {
            $this->assertNotNull(Award::query()->find($award->getKey()));
            $this->assertSame(0, AuditLog::query()->where('action', AuditAction::CONTENT_PURGED)->count());
        }
    }
}
