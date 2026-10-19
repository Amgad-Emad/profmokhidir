<?php

namespace Tests\Feature\Policies;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\ChatReport;
use App\Models\User;
use App\Policies\ChatChannelPolicy;
use App\Policies\ChatMessagePolicy;
use App\Policies\ChatReadPolicy;
use App\Policies\ChatReportPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatPoliciesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_chat_policies_are_registered(): void
    {
        $this->assertInstanceOf(ChatChannelPolicy::class, Gate::getPolicyFor(ChatChannel::class));
        $this->assertInstanceOf(ChatMessagePolicy::class, Gate::getPolicyFor(ChatMessage::class));
        $this->assertInstanceOf(ChatReportPolicy::class, Gate::getPolicyFor(ChatReport::class));
        $this->assertInstanceOf(ChatReadPolicy::class, Gate::getPolicyFor(ChatRead::class));
    }

    #[Test]
    public function approved_members_and_admins_read_channels_and_post(): void
    {
        $channel = ChatChannel::factory()->create();

        foreach ([User::factory()->member()->create(), User::factory()->admin()->create()] as $user) {
            $this->assertTrue($user->can('viewAny', ChatChannel::class));
            $this->assertTrue($user->can('view', $channel));
            $this->assertTrue($user->can('viewAny', ChatMessage::class));
        }

        $this->assertTrue(User::factory()->member()->create()->can('create', ChatMessage::class));

        foreach ([User::factory()->editor()->create(), User::factory()->applicant()->create(), User::factory()->rejectedApplicant()->create(), User::factory()->suspendedMember()->create()] as $user) {
            $this->assertFalse($user->can('view', $channel));
            $this->assertFalse($user->can('create', ChatMessage::class));
            $this->assertFalse($user->can('create', ChatReport::class));
        }
    }

    #[Test]
    public function only_admins_manage_channels_and_resolve_reports(): void
    {
        $channel = ChatChannel::factory()->create();
        $report = ChatReport::factory()->create();

        $admin = User::factory()->admin()->create();
        $this->assertTrue($admin->can('create', ChatChannel::class));
        $this->assertTrue($admin->can('update', $channel));
        $this->assertTrue($admin->can('delete', $channel));
        $this->assertTrue($admin->can('viewAny', ChatReport::class));
        $this->assertTrue($admin->can('resolve', $report));
        $this->assertTrue($admin->can('viewAny', ChatRead::class));

        foreach ([User::factory()->editor()->create(), User::factory()->member()->create()] as $user) {
            $this->assertFalse($user->can('create', ChatChannel::class));
            $this->assertFalse($user->can('update', $channel));
            $this->assertFalse($user->can('viewAny', ChatReport::class));
            $this->assertFalse($user->can('resolve', $report));
            $this->assertFalse($user->can('viewAny', ChatRead::class));
        }
    }

    #[Test]
    public function authors_edit_and_delete_their_own_messages_and_moderators_delete_any(): void
    {
        $author = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $message = ChatMessage::factory()->for($author, 'user')->create();

        $this->assertTrue($author->can('update', $message));
        $this->assertTrue($author->can('delete', $message));
        $this->assertFalse($author->can('report', $message));

        $this->assertFalse($other->can('update', $message));
        $this->assertFalse($other->can('delete', $message));
        $this->assertTrue($other->can('report', $message));

        $admin = User::factory()->admin()->create();
        $this->assertFalse($admin->can('update', $message));
        $this->assertTrue($admin->can('delete', $message));
    }

    #[Test]
    public function reporters_see_their_own_reports_and_users_own_their_read_cursors(): void
    {
        $reporter = User::factory()->member()->create();
        $report = ChatReport::factory()->for($reporter, 'reporter')->create();

        $this->assertTrue($reporter->can('view', $report));
        $this->assertFalse(User::factory()->member()->create()->can('view', $report));

        $read = ChatRead::factory()->for($reporter, 'user')->create();
        $this->assertTrue($reporter->can('view', $read));
        $this->assertTrue($reporter->can('update', $read));
        $this->assertFalse(User::factory()->member()->create()->can('update', $read));
        $this->assertTrue(User::factory()->admin()->create()->can('view', $read));
    }
}
