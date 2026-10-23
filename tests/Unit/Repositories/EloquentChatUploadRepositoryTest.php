<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatMessage;
use App\Models\ChatUpload;
use App\Models\User;
use App\Repositories\Contracts\ChatUploadRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentChatUploadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ChatUploadRepositoryInterface $repository;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ChatUploadRepositoryInterface::class);
        $this->member = User::factory()->member()->create();
    }

    #[Test]
    public function it_finds_only_the_members_own_unclaimed_upload(): void
    {
        $mine = ChatUpload::factory()->for($this->member)->create();
        $theirs = ChatUpload::factory()->create();
        $sent = ChatUpload::factory()->for($this->member)->claimed(ChatMessage::factory()->for($this->member, 'user')->create())->create();

        $this->assertTrue($mine->is($this->repository->findPendingFor($this->member->id, $mine->uuid)));
        $this->assertNull($this->repository->findPendingFor($this->member->id, $theirs->uuid));
        $this->assertNull($this->repository->findPendingFor($this->member->id, $sent->uuid));
        $this->assertSame(1, $this->repository->countPendingFor($this->member->id));
    }

    #[Test]
    public function locking_returns_the_uploads_in_the_order_asked_and_skips_the_rest(): void
    {
        [$a, $b] = ChatUpload::factory()->count(2)->for($this->member)->create()->all();
        $foreign = ChatUpload::factory()->create();

        $locked = $this->repository->lockPendingFor($this->member->id, [$b->uuid, $foreign->uuid, $a->uuid]);

        $this->assertSame([$b->id, $a->id], $locked->pluck('id')->all());
        $this->assertCount(0, $this->repository->lockPendingFor($this->member->id, []));
    }

    #[Test]
    public function marking_claimed_records_the_message_and_the_time(): void
    {
        $upload = ChatUpload::factory()->for($this->member)->create();
        $message = ChatMessage::factory()->for($this->member, 'user')->create();

        $this->repository->markClaimed($upload, $message->id, now());

        $this->assertSame($message->id, $upload->fresh()->message_id);
        $this->assertTrue($upload->fresh()->isClaimed());
    }

    #[Test]
    public function unclaimed_before_lists_only_stale_waiting_uploads(): void
    {
        $stale = ChatUpload::factory()->stale()->create();
        ChatUpload::factory()->create();
        ChatUpload::factory()->stale()->claimed(ChatMessage::factory()->create())->create();

        $this->assertSame([$stale->id], $this->repository->unclaimedBefore(now()->subDay())->pluck('id')->all());
    }
}
