<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\User;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use App\Repositories\Eloquent\EloquentChatReportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentChatReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ChatReportRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ChatReportRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentChatReportRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_a_report_by_message_and_reporter(): void
    {
        $report = ChatReport::factory()->create();
        $other = User::factory()->member()->create();

        $this->assertTrue($report->is($this->repository->findFor($report->message_id, $report->reporter_id)));
        $this->assertNull($this->repository->findFor($report->message_id, $other->id));
    }

    #[Test]
    public function open_lists_unresolved_reports_with_their_relations(): void
    {
        $open = ChatReport::factory()->open()->count(2)->create();
        ChatReport::factory()->resolved()->create();

        $reports = $this->repository->open();

        $this->assertSame($open->pluck('id')->all(), $reports->pluck('id')->all());
        $this->assertSame(2, $this->repository->countOpen());
        $this->assertTrue($reports->first()->relationLoaded('message'));
        $this->assertTrue($reports->first()->relationLoaded('reporter'));
        $this->assertTrue($reports->first()->message->relationLoaded('user'));
    }

    #[Test]
    public function mark_resolved_records_who_and_when(): void
    {
        $moderator = User::factory()->admin()->create();
        $report = ChatReport::factory()->open()->create();

        $resolved = $this->repository->markResolved($report, $moderator->id);

        $this->assertTrue($resolved->isResolved());
        $this->assertSame($moderator->id, $resolved->resolved_by);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertSame(0, $this->repository->countOpen());
    }

    #[Test]
    public function reports_paginate_by_state_and_load_deleted_messages_with_their_authors(): void
    {
        $message = ChatMessage::factory()->create(['body' => 'reported']);
        $open = ChatReport::factory()->for($message, 'message')->create();
        $resolved = ChatReport::factory()->resolved()->create();
        $message->delete();

        $openPage = $this->repository->paginateByState(true);
        $resolvedPage = $this->repository->paginateByState(false);

        $this->assertSame([$open->id], $openPage->getCollection()->pluck('id')->all());
        $this->assertSame([$resolved->id], $resolvedPage->getCollection()->pluck('id')->all());
        $this->assertTrue($openPage->first()->message->trashed());
        $this->assertTrue($openPage->first()->message->relationLoaded('user'));
        $this->assertTrue($openPage->first()->relationLoaded('reporter'));

        $found = $this->repository->findWithRelations($open->id);
        $this->assertSame('reported', $found->message->body);
        $this->assertNull($this->repository->findWithRelations(999));
    }

    #[Test]
    public function every_open_report_about_a_message_can_be_resolved_at_once(): void
    {
        $message = ChatMessage::factory()->create();
        $first = ChatReport::factory()->for($message, 'message')->create();
        $second = ChatReport::factory()->for($message, 'message')->create();
        $unrelated = ChatReport::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertSame(2, $this->repository->markResolvedForMessage($message->id, $admin->id));
        $this->assertSame(0, $this->repository->markResolvedForMessage($message->id, $admin->id));
        $this->assertTrue($first->fresh()->isResolved() && $second->fresh()->isResolved());
        $this->assertSame($admin->id, $first->fresh()->resolved_by);
        $this->assertFalse($unrelated->fresh()->isResolved());
    }
}
