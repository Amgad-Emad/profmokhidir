<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Exceptions\CommunityApplicationException;
use App\Models\User;
use App\Services\CommunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunityServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommunityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CommunityService::class);
    }

    #[Test]
    public function apply_marks_the_user_pending_assigns_the_applicant_role_and_is_audited(): void
    {
        $user = User::factory()->create();

        $user = $this->service->apply($user, [
            'affiliation' => ' University ',
            'academic_field' => 'Agronomy',
            'reason_for_joining' => '',
        ]);

        $this->assertSame(CommunityStatus::Pending, $user->community_status);
        $this->assertSame('University', $user->affiliation);
        $this->assertNull($user->reason_for_joining);
        $this->assertTrue($user->fresh()->hasRole(Roles::APPLICANT));
        $this->assertFalse($user->fresh()->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditAction::COMMUNITY_APPLIED, 'subject_id' => $user->id]);
    }

    #[Test]
    public function apply_rejects_pending_and_approved_users(): void
    {
        $this->expectException(CommunityApplicationException::class);
        $this->service->apply(User::factory()->applicant()->create(), []);
    }

    #[Test]
    public function apply_rejects_approved_members(): void
    {
        $this->expectException(CommunityApplicationException::class);
        $this->service->apply(User::factory()->member()->create(), []);
    }

    #[Test]
    public function rejected_applicants_may_apply_again(): void
    {
        $user = $this->service->apply(User::factory()->rejectedApplicant()->create(), ['affiliation' => 'X']);

        $this->assertSame(CommunityStatus::Pending, $user->community_status);
        $this->assertNull($user->review_note);
    }

    #[Test]
    public function approve_grants_membership_and_chat_access(): void
    {
        $admin = User::factory()->admin()->create();
        $applicant = User::factory()->applicant()->create();

        $member = $this->service->approve($applicant, $admin, 'Welcome');

        $this->assertSame(CommunityStatus::Approved, $member->community_status);
        $this->assertSame($admin->id, $member->reviewed_by);
        $this->assertNotNull($member->reviewed_at);
        $this->assertSame('Welcome', $member->review_note);
        $this->assertTrue($member->hasRole(Roles::MEMBER));
        $this->assertFalse($member->hasRole(Roles::APPLICANT));
        $this->assertTrue($member->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => AuditAction::COMMUNITY_APPROVED, 'subject_id' => $member->id]);
    }

    #[Test]
    public function reject_only_applies_to_pending_applications(): void
    {
        $admin = User::factory()->admin()->create();
        $applicant = User::factory()->applicant()->create();

        $rejected = $this->service->reject($applicant, $admin, 'Incomplete');

        $this->assertSame(CommunityStatus::Rejected, $rejected->community_status);
        $this->assertSame('Incomplete', $rejected->review_note);
        $this->assertFalse($rejected->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => AuditAction::COMMUNITY_REJECTED, 'subject_id' => $rejected->id]);

        $this->expectException(CommunityApplicationException::class);
        $this->service->reject($rejected, $admin);
    }

    #[Test]
    public function suspend_removes_membership_and_a_suspended_member_can_be_re_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $this->assertTrue($member->canAccessChat());

        $suspended = $this->service->suspend($member, $admin, 'Conduct');

        $this->assertSame(CommunityStatus::Suspended, $suspended->community_status);
        $this->assertFalse($suspended->hasRole(Roles::MEMBER));
        $this->assertFalse($suspended->canAccessChat());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => AuditAction::COMMUNITY_SUSPENDED, 'subject_id' => $suspended->id]);

        $restored = $this->service->approve($suspended, $admin);
        $this->assertTrue($restored->hasRole(Roles::MEMBER));
        $this->assertTrue($restored->canAccessChat());
    }

    #[Test]
    public function suspend_rejects_users_who_are_not_approved(): void
    {
        $this->expectException(CommunityApplicationException::class);
        $this->service->suspend(User::factory()->applicant()->create(), User::factory()->admin()->create());
    }

    #[Test]
    public function listing_helpers_filter_by_community_status(): void
    {
        User::factory()->applicant()->count(2)->create();
        User::factory()->member()->count(3)->create();
        User::factory()->rejectedApplicant()->create();

        $this->assertCount(2, $this->service->pendingApplicants());
        $this->assertSame(2, $this->service->countPending());
        $this->assertCount(3, $this->service->members());
        $this->assertSame(3, $this->service->paginate('approved')->total());
        $this->assertSame(6, $this->service->paginate()->total());
    }
}
