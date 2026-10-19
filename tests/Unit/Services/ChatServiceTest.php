<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Exceptions\ChatMessageConflictException;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $service;

    private ChatChannel $channel;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ChatService::class);
        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create();
    }

    #[Test]
    public function create_channel_slugs_and_deduplicates(): void
    {
        $first = $this->service->createChannel('Soil Science', '  Talk about soils ');
        $second = $this->service->createChannel('Soil Science');

        $this->assertSame('soil-science', $first->slug);
        $this->assertSame('soil-science-2', $second->slug);
        $this->assertSame('Talk about soils', $first->description);
        $this->assertTrue($this->service->findChannel('general')->is($this->channel));
        $this->assertSame(['General', 'Soil Science', 'Soil Science'], $this->service->channels()->pluck('name')->all());
    }

    #[Test]
    public function send_requires_an_approved_member(): void
    {
        try {
            $this->service->send($this->channel, User::factory()->applicant()->create(), 'hi', (string) Str::uuid());
            $this->fail('Applicants must not send');
        } catch (AuthorizationException) {
        }

        $this->expectException(AuthorizationException::class);
        $this->service->send($this->channel, User::factory()->create(), 'hi', (string) Str::uuid());
    }

    #[Test]
    public function send_rejects_blank_bodies_and_invalid_uuids(): void
    {
        try {
            $this->service->send($this->channel, $this->member, "  \n ", (string) Str::uuid());
            $this->fail('Blank body must be rejected');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $this->service->send($this->channel, $this->member, 'hello', 'not-a-uuid');
    }

    #[Test]
    public function send_creates_a_message_and_updates_channel_and_read_cursor(): void
    {
        $message = $this->service->send($this->channel, $this->member, '  Hello  ', (string) Str::uuid());

        $this->assertSame('Hello', $message->body);
        $this->assertSame(1, $message->version);
        $this->assertSame($message->id, $this->channel->fresh()->last_message_id);
        $this->assertDatabaseHas('chat_reads', [
            'channel_id' => $this->channel->id,
            'user_id' => $this->member->id,
            'last_read_message_id' => $message->id,
        ]);
        $this->assertSame(0, $this->service->unreadCount($this->channel, $this->member));
    }

    #[Test]
    public function send_is_idempotent_per_client_uuid(): void
    {
        $uuid = (string) Str::uuid();

        $first = $this->service->send($this->channel, $this->member, 'Once', $uuid);
        $again = $this->service->send($this->channel, $this->member, 'Twice', $uuid);

        $this->assertSame($first->id, $again->id);
        $this->assertSame('Once', $again->body);
        $this->assertSame(1, ChatMessage::query()->count());
    }

    #[Test]
    public function emoji_and_arabic_bodies_round_trip(): void
    {
        $body = 'مرحباً بالجميع 🌱 — ahlan';

        $message = $this->service->send($this->channel, $this->member, $body, (string) Str::uuid());

        $this->assertSame($body, $message->fresh()->body);
    }

    #[Test]
    public function edit_is_author_only_and_uses_optimistic_locking(): void
    {
        $message = $this->service->send($this->channel, $this->member, 'Original', (string) Str::uuid());

        $edited = $this->service->edit($message, $this->member, 'Changed', 1);
        $this->assertSame('Changed', $edited->body);
        $this->assertSame(2, $edited->version);
        $this->assertNotNull($edited->edited_at);

        try {
            $this->service->edit($edited, $this->member, 'Stale', 1);
            $this->fail('Stale version must conflict');
        } catch (ChatMessageConflictException) {
        }
        $this->assertSame('Changed', $edited->fresh()->body);

        $this->expectException(AuthorizationException::class);
        $this->service->edit($edited, User::factory()->member()->create(), 'Hijack', 2);
    }

    #[Test]
    public function delete_by_the_author_soft_deletes_repoints_the_channel_and_is_audited(): void
    {
        $first = $this->service->send($this->channel, $this->member, 'First', (string) Str::uuid());
        $second = $this->service->send($this->channel, $this->member, 'Second', (string) Str::uuid());
        $this->assertSame($second->id, $this->channel->fresh()->last_message_id);

        $this->assertTrue($this->service->delete($second, $this->member));

        $this->assertSoftDeleted('chat_messages', ['id' => $second->id]);
        $this->assertSame($first->id, $this->channel->fresh()->last_message_id);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->member->id,
            'action' => AuditAction::CHAT_MESSAGE_DELETED,
            'subject_id' => $second->id,
        ]);
    }

    #[Test]
    public function delete_is_allowed_for_admins_but_not_other_members(): void
    {
        $message = $this->service->send($this->channel, $this->member, 'Hi', (string) Str::uuid());

        try {
            $this->service->delete($message, User::factory()->member()->create());
            $this->fail('Other members must not delete');
        } catch (AuthorizationException) {
        }
        $this->assertNull($message->fresh()->deleted_at);

        $admin = User::factory()->admin()->create();
        $this->assertTrue($this->service->canModerate($admin));
        $this->assertTrue($this->service->delete($message, $admin));
        $this->assertSoftDeleted('chat_messages', ['id' => $message->id]);
        $this->assertNull($this->channel->fresh()->last_message_id);
    }

    #[Test]
    public function history_and_since_page_through_messages_in_id_order(): void
    {
        $ids = [];
        foreach (range(1, 5) as $i) {
            $ids[] = $this->service->send($this->channel, $this->member, "m{$i}", (string) Str::uuid())->id;
        }

        $this->assertSame(array_slice($ids, 3), $this->service->history($this->channel, $this->member, 2)->pluck('id')->all());
        $this->assertSame(array_slice($ids, 0, 2), $this->service->history($this->channel, $this->member, 2, $ids[2])->pluck('id')->all());
        $this->assertSame(array_slice($ids, 3), $this->service->since($this->channel, $this->member, $ids[2])->pluck('id')->all());

        $this->expectException(AuthorizationException::class);
        $this->service->history($this->channel, User::factory()->applicant()->create());
    }

    #[Test]
    public function mark_read_and_unread_count(): void
    {
        $other = User::factory()->member()->create();
        $first = $this->service->send($this->channel, $this->member, 'a', (string) Str::uuid());
        $this->service->send($this->channel, $this->member, 'b', (string) Str::uuid());
        $this->service->send($this->channel, $this->member, 'c', (string) Str::uuid());

        $this->assertSame(3, $this->service->unreadCount($this->channel, $other));

        $this->service->markRead($this->channel, $other, $first->id);
        $this->assertSame(2, $this->service->unreadCount($this->channel, $other));

        $this->service->markRead($this->channel, $other);
        $this->assertSame(0, $this->service->unreadCount($this->channel, $other));

        $this->service->markRead($this->channel, $other, $first->id); // never rewinds
        $this->assertSame(0, $this->service->unreadCount($this->channel, $other));
    }

    #[Test]
    public function reports_are_idempotent_and_resolved_only_by_moderators(): void
    {
        $reporter = User::factory()->member()->create();
        $message = $this->service->send($this->channel, $this->member, 'Spam', (string) Str::uuid());

        $report = $this->service->report($message, $reporter, ' Spam ');
        $again = $this->service->report($message, $reporter, 'Again');

        $this->assertSame($report->id, $again->id);
        $this->assertSame('Spam', $report->reason);
        $this->assertSame(1, $this->service->countOpenReports());
        $this->assertTrue($this->service->openReports()->first()->is($report));

        try {
            $this->service->resolveReport($report, $reporter);
            $this->fail('Members must not resolve reports');
        } catch (AuthorizationException) {
        }

        $admin = User::factory()->admin()->create();
        $resolved = $this->service->resolveReport($report, $admin);

        $this->assertTrue($resolved->isResolved());
        $this->assertSame($admin->id, $resolved->resolved_by);
        $this->assertSame(0, $this->service->countOpenReports());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => AuditAction::CHAT_REPORT_RESOLVED, 'subject_id' => $report->id]);
    }

    #[Test]
    public function reporting_requires_chat_access(): void
    {
        $message = $this->service->send($this->channel, $this->member, 'x', (string) Str::uuid());

        $this->expectException(AuthorizationException::class);
        $this->service->report($message, User::factory()->applicant()->create());
    }

    #[Test]
    public function reporting_is_audited_and_own_messages_cannot_be_reported(): void
    {
        $message = $this->service->send($this->channel, $this->member, 'x', (string) Str::uuid());
        $reporter = User::factory()->member()->create();

        $report = $this->service->report($message, $reporter, str_repeat('r', 600));

        $this->assertSame(500, mb_strlen($report->reason));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $reporter->id, 'action' => AuditAction::CHAT_MESSAGE_REPORTED, 'subject_id' => $message->id]);

        $this->expectException(AuthorizationException::class);
        $this->service->report($message, $this->member, 'me');
    }

    #[Test]
    public function moderators_delete_a_reported_message_and_every_report_about_it_is_resolved(): void
    {
        $message = $this->service->send($this->channel, $this->member, 'spam', (string) Str::uuid());
        $first = $this->service->report($message, User::factory()->member()->create(), 'spam');
        $second = $this->service->report($message, User::factory()->member()->create(), 'spam again');
        $admin = User::factory()->admin()->create();

        $resolved = $this->service->deleteReportedMessage($first, $admin);

        $this->assertTrue($resolved->isResolved());
        $this->assertTrue($second->fresh()->isResolved());
        $this->assertSoftDeleted('chat_messages', ['id' => $message->id]);
        $this->assertSame(0, $this->service->countOpenReports());
        $this->assertSame(1, $this->service->paginateReports(false)->total() >= 1 ? 1 : 0);
    }

    #[Test]
    public function moderators_suspend_the_author_from_a_report(): void
    {
        Notification::fake();
        $message = $this->service->send($this->channel, $this->member, 'rude', (string) Str::uuid());
        $report = $this->service->report($message, User::factory()->member()->create(), 'rude');
        $admin = User::factory()->admin()->create();

        $resolved = $this->service->suspendReportedAuthor($report, $admin, 'Be kind.');

        $this->assertTrue($resolved->isResolved());
        $this->assertSame(CommunityStatus::Suspended, $this->member->fresh()->community_status);
        $this->assertSame('Be kind.', $this->member->fresh()->review_note);
        $this->assertFalse($this->member->fresh()->canAccessChat());

        // Suspending an already suspended author just resolves the report.
        $again = $this->service->report($message, User::factory()->member()->create(), 'still rude');
        $this->assertTrue($this->service->suspendReportedAuthor($again, $admin)->isResolved());
    }

    #[Test]
    public function only_moderators_act_on_reports(): void
    {
        $message = $this->service->send($this->channel, $this->member, 'x', (string) Str::uuid());
        $report = $this->service->report($message, User::factory()->member()->create());

        try {
            $this->service->deleteReportedMessage($report, $this->member);
            $this->fail('Members must not act on reports.');
        } catch (AuthorizationException) {
        }

        $this->expectException(AuthorizationException::class);
        $this->service->suspendReportedAuthor($report, $this->member);
    }

    #[Test]
    public function initial_state_and_older_pages_describe_the_channel_for_the_browser(): void
    {
        $ids = [];
        foreach (range(1, 3) as $i) {
            $ids[] = $this->service->send($this->channel, $this->member, "m{$i}", (string) Str::uuid())->id;
        }
        $viewer = User::factory()->member()->create();
        $this->service->markRead($this->channel, $viewer, $ids[0]);

        $state = $this->service->initialState($this->channel, $viewer);

        $this->assertSame($ids, array_column($state['messages'], 'id'));
        $this->assertSame(end($ids), $state['cursor']);
        $this->assertSame($ids[0], $state['lastRead']);
        $this->assertFalse($state['hasOlder']);
        $this->assertSame([['id' => $this->channel->id, 'current' => true]], array_map(fn (array $c): array => ['id' => $c['id'], 'current' => $c['current']], $state['channels']));

        $older = $this->service->older($this->channel, $viewer, $ids[1]);
        $this->assertSame([$ids[0]], array_column($older['messages'], 'id'));
        $this->assertFalse($older['hasOlder']);

        $this->assertNull($this->service->findMessage(999));
        $this->assertTrue($this->service->firstChannel()->is($this->channel));
    }
}
