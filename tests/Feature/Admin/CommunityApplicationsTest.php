<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Livewire\Admin\Community\Applications;
use App\Models\User;
use App\Notifications\CommunityApplicationReviewed;
use App\Services\CommunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunityApplicationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function the_queue_lives_at_admin_community_and_shows_every_field_of_a_pending_application(): void
    {
        $applicant = User::factory()->applicant()->create([
            'name' => 'Fatima El-Tayeb',
            'email' => 'fatima@example.com',
            'affiliation' => 'University of Khartoum',
            'academic_field' => 'Soil science',
            'reason_for_joining' => 'A former student of the Professor.',
        ]);

        $this->assertSame(url('/admin/community'), route(AdminNavigation::routeFor(AdminNavigation::APPLICATIONS)));

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::APPLICATIONS)))->assertOk()
            ->assertSee('data-tab="pending"', false)
            ->assertSee('Fatima El-Tayeb')
            ->assertSee('fatima@example.com')
            ->assertSee('University of Khartoum')
            ->assertSee('Soil science')
            ->assertSee('A former student of the Professor.')
            ->assertSee('data-approve', false)
            ->assertSee('data-reject', false);

        // The members page is the same screen opened on the Members tab.
        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::MEMBERS)))->assertOk()
            ->assertSee('aria-selected="true" class="admin-tab inline-flex items-center gap-2" data-tab="approved"', false)
            ->assertSee('There are no members yet.');
    }

    #[Test]
    public function approving_records_the_reviewer_emails_the_applicant_and_is_audited(): void
    {
        $applicant = User::factory()->applicant()->create(['name' => 'Omar Siddig']);

        Livewire::actingAs($this->admin)->test(Applications::class)
            ->call('decide', Applications::ACTION_APPROVE, $applicant->id)
            ->assertSet('decidingAction', Applications::ACTION_APPROVE)
            ->assertSee('Approve this application?')
            ->set('note', 'Welcome, Omar.')
            ->call('confirmDecision')
            ->assertHasNoErrors()
            ->assertSet('decidingAction', null)
            ->assertSee('Omar Siddig has been approved and emailed.')
            ->assertDontSee('data-user="'.$applicant->id.'"', false); // no longer on the pending tab

        $applicant->refresh();
        $this->assertSame(CommunityStatus::Approved, $applicant->community_status);
        $this->assertSame($this->admin->id, $applicant->reviewed_by);
        $this->assertNotNull($applicant->reviewed_at);
        $this->assertSame('Welcome, Omar.', $applicant->review_note);
        $this->assertTrue($applicant->hasRole(Roles::MEMBER));
        $this->assertFalse($applicant->hasRole(Roles::APPLICANT));
        $this->assertTrue($applicant->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::COMMUNITY_APPROVED, 'subject_id' => $applicant->id]);

        Notification::assertSentTo($applicant, CommunityApplicationReviewed::class, fn (CommunityApplicationReviewed $notification): bool => $notification->status === CommunityStatus::Approved && $notification->note === 'Welcome, Omar.');
    }

    #[Test]
    public function rejecting_with_an_optional_note_emails_the_applicant(): void
    {
        $applicant = User::factory()->applicant()->create();

        Livewire::actingAs($this->admin)->test(Applications::class)
            ->call('decide', Applications::ACTION_REJECT, $applicant->id)
            ->call('confirmDecision')
            ->assertHasNoErrors();

        $applicant->refresh();
        $this->assertSame(CommunityStatus::Rejected, $applicant->community_status);
        $this->assertNull($applicant->review_note);
        $this->assertFalse($applicant->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::COMMUNITY_REJECTED, 'subject_id' => $applicant->id]);
        Notification::assertSentTo($applicant, CommunityApplicationReviewed::class, fn (CommunityApplicationReviewed $notification): bool => $notification->status === CommunityStatus::Rejected && $notification->note === null);
    }

    #[Test]
    public function members_can_be_suspended_reinstated_and_removed(): void
    {
        $member = User::factory()->member()->create(['name' => 'Amina Hassan']);

        $component = Livewire::actingAs($this->admin)->test(Applications::class)
            ->call('showTab', CommunityStatus::Approved->value)
            ->assertSee('Amina Hassan')
            ->assertSee('data-suspend', false)
            ->call('decide', Applications::ACTION_SUSPEND, $member->id)
            ->set('note', 'Repeated off-topic posts.')
            ->call('confirmDecision')
            ->assertHasNoErrors();

        $member->refresh();
        $this->assertSame(CommunityStatus::Suspended, $member->community_status);
        $this->assertFalse($member->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::COMMUNITY_SUSPENDED, 'subject_id' => $member->id]);
        Notification::assertSentTo($member, CommunityApplicationReviewed::class, fn (CommunityApplicationReviewed $n): bool => $n->status === CommunityStatus::Suspended);

        $component->call('showTab', CommunityStatus::Suspended->value)
            ->assertSee('Amina Hassan')
            ->assertSee('data-reinstate', false)
            ->call('decide', Applications::ACTION_REINSTATE, $member->id)
            ->call('confirmDecision')
            ->assertHasNoErrors();

        $member->refresh();
        $this->assertSame(CommunityStatus::Approved, $member->community_status);
        $this->assertTrue($member->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::COMMUNITY_REINSTATED, 'subject_id' => $member->id]);

        $component->call('showTab', CommunityStatus::Approved->value)
            ->call('decide', Applications::ACTION_REMOVE, $member->id)
            ->call('confirmDecision')
            ->assertHasNoErrors();

        $member->refresh();
        $this->assertSame(CommunityStatus::None, $member->community_status);
        $this->assertFalse($member->hasRole(Roles::MEMBER));
        $this->assertFalse($member->hasRole(Roles::APPLICANT));
        $this->assertFalse($member->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::COMMUNITY_REMOVED, 'subject_id' => $member->id]);
        Notification::assertSentTo($member, CommunityApplicationReviewed::class, fn (CommunityApplicationReviewed $n): bool => $n->status === CommunityStatus::None);
    }

    #[Test]
    public function a_note_that_is_too_long_is_refused_and_an_impossible_transition_is_explained(): void
    {
        $applicant = User::factory()->applicant()->create();

        $component = Livewire::actingAs($this->admin)->test(Applications::class)
            ->call('decide', Applications::ACTION_APPROVE, $applicant->id)
            ->set('note', str_repeat('x', 1001))
            ->call('confirmDecision')
            ->assertHasErrors(['note']);

        $this->assertSame(CommunityStatus::Pending, $applicant->fresh()->community_status);

        // Someone else rejected it meanwhile; "reject" is no longer possible.
        app(CommunityService::class)->reject($applicant, User::factory()->admin()->create());

        $component->set('note', '')
            ->call('decide', Applications::ACTION_REJECT, $applicant->id)
            ->call('confirmDecision')
            ->assertHasErrors(['note']);
    }

    #[Test]
    public function admins_cannot_review_themselves_and_editors_cannot_open_the_queue(): void
    {
        Livewire::actingAs($this->admin)->test(Applications::class)
            ->call('decide', Applications::ACTION_APPROVE, $this->admin->id)
            ->assertForbidden()
            ->assertSet('decidingId', null);

        Livewire::actingAs(User::factory()->editor()->create())->test(Applications::class)->assertForbidden();
    }
}
