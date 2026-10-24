<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\AuditAction;
use App\Constants\ChatSettings;
use App\Constants\CommunityStatus;
use App\Livewire\Admin\Community\Reports;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $author;

    private ChatMessage $message;

    private ChatReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->author = User::factory()->member()->create(['name' => 'Yusuf Abdallah']);
        $this->message = ChatMessage::factory()->for(ChatChannel::factory()->general(), 'channel')->for($this->author, 'user')->create(['body' => 'Buy my book now']);
        $this->report = ChatReport::factory()->for($this->message, 'message')->for(User::factory()->member()->create(['name' => 'Amina Hassan']), 'reporter')->create(['reason' => 'Advertising']);
    }

    #[Test]
    public function open_reports_show_the_reason_the_message_and_the_people_involved(): void
    {
        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS)))->assertOk()
            ->assertSee('Reported by Amina Hassan')
            ->assertSee('Advertising')
            ->assertSee('Message by Yusuf Abdallah')
            ->assertSee('Buy my book now')
            ->assertSee('in General')
            ->assertSee('data-resolve', false)
            ->assertSee('data-delete-message', false)
            ->assertSee('data-suspend-author', false);
    }

    #[Test]
    public function resolving_marks_the_report_and_moves_it_to_the_resolved_tab(): void
    {
        Livewire::actingAs($this->admin)->test(Reports::class)
            ->call('resolve', $this->report->id)
            ->assertSee('No reports are waiting.')
            ->call('showTab', Reports::TAB_RESOLVED)
            ->assertSee('Advertising')
            ->assertSee('Resolved');

        $this->report->refresh();
        $this->assertTrue($this->report->isResolved());
        $this->assertSame($this->admin->id, $this->report->resolved_by);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::CHAT_REPORT_RESOLVED, 'subject_id' => $this->report->id]);
    }

    #[Test]
    public function deleting_the_message_from_a_report_removes_it_for_members_and_resolves_every_report_about_it(): void
    {
        $secondReport = ChatReport::factory()->for($this->message, 'message')->create();

        Livewire::actingAs($this->admin)->test(Reports::class)
            ->call('confirm', Reports::ACTION_DELETE, $this->report->id)
            ->assertSee('Remove this message permanently?')
            ->call('deleteMessage')
            ->assertSee('No reports are waiting.');

        $this->assertSoftDeleted('chat_messages', ['id' => $this->message->id]);
        $this->assertTrue($this->report->fresh()->isResolved());
        $this->assertTrue($secondReport->fresh()->isResolved());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::CHAT_MESSAGE_DELETED, 'subject_id' => $this->message->id]);

        Livewire::actingAs($this->admin)->test(Reports::class)
            ->call('showTab', Reports::TAB_RESOLVED)
            ->assertSee(ChatSettings::REMOVED_PLACEHOLDER)
            ->assertDontSee('Buy my book now');
    }

    #[Test]
    public function suspending_the_author_from_a_report_ends_their_access_and_resolves_it(): void
    {
        Livewire::actingAs($this->admin)->test(Reports::class)
            ->call('confirm', Reports::ACTION_SUSPEND, $this->report->id)
            ->assertSee('Suspend the author?')
            ->set('note', 'Advertising is not allowed.')
            ->call('suspendAuthor')
            ->assertHasNoErrors()
            ->assertSee('No reports are waiting.');

        $this->author->refresh();
        $this->assertSame(CommunityStatus::Suspended, $this->author->community_status);
        $this->assertSame('Advertising is not allowed.', $this->author->review_note);
        $this->assertFalse($this->author->canAccessChat());
        $this->assertTrue($this->report->fresh()->isResolved());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::COMMUNITY_SUSPENDED, 'subject_id' => $this->author->id]);
    }

    #[Test]
    public function members_and_editors_cannot_open_the_reports_screen(): void
    {
        $this->actingAs(User::factory()->editor()->create())->get(route(AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS)))->assertForbidden();

        Livewire::actingAs(User::factory()->member()->create())->test(Reports::class)->assertForbidden();
    }
}
