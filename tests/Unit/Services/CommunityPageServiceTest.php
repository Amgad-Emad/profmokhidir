<?php

namespace Tests\Unit\Services;

use App\Constants\CommunityCopy;
use App\Constants\CommunityStatus;
use App\Constants\StatusTone;
use App\Models\User;
use App\Services\CommunityPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunityPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommunityPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CommunityPageService::class);
    }

    #[Test]
    public function the_index_bundles_the_copy_and_urls_and_no_standing_for_visitors(): void
    {
        $page = $this->service->index(null);

        $this->assertSame('community', $page['section']['key']);
        $this->assertTrue($page['section']['active']);
        $this->assertSame(CommunityCopy::HEADING, $page['copy']['heading']);
        $this->assertSame(CommunityCopy::conductRules(), $page['copy']['conduct']);
        $this->assertSame(CommunityCopy::PRIVACY, $page['copy']['privacy']);
        $this->assertSame(route('community.apply'), $page['applyUrl']);
        $this->assertSame(route('login'), $page['loginUrl']);
        $this->assertNull($page['standing']);
    }

    #[Test]
    public function each_community_status_maps_to_a_heading_message_tone_and_chat_link(): void
    {
        $pending = $this->service->standing(User::factory()->applicant()->create());
        $this->assertSame([CommunityStatus::Pending, CommunityCopy::PENDING_HEADING, StatusTone::Bronze, null, false], [$pending['status'], $pending['heading'], $pending['tone'], $pending['chatUrl'], $pending['canApply']]);

        $rejected = $this->service->standing(User::factory()->rejectedApplicant()->create());
        $this->assertSame([CommunityCopy::REJECTED_HEADING, CommunityCopy::REJECTED, StatusTone::Ash, null], [$rejected['heading'], $rejected['message'], $rejected['tone'], $rejected['chatUrl']]);

        $suspended = $this->service->standing(User::factory()->suspendedMember()->create());
        $this->assertSame([CommunityCopy::SUSPENDED_HEADING, null], [$suspended['heading'], $suspended['chatUrl']]);

        $member = $this->service->standing(User::factory()->member()->create());
        $this->assertSame([CommunityStatus::Approved, StatusTone::Cypress, route('community.chat')], [$member['status'], $member['tone'], $member['chatUrl']]);
    }

    #[Test]
    public function admins_always_get_the_chat_and_never_an_application_form(): void
    {
        $standing = $this->service->standing(User::factory()->admin()->create());

        $this->assertSame(route('community.chat'), $standing['chatUrl']);
        $this->assertFalse($standing['canApply']);
    }

    #[Test]
    public function users_who_never_applied_may_apply_and_removed_members_are_told_so(): void
    {
        $fresh = $this->service->standing(User::factory()->create());
        $this->assertSame(CommunityCopy::NOT_APPLIED_HEADING, $fresh['heading']);
        $this->assertTrue($fresh['canApply']);

        $removed = $this->service->standing(User::factory()->create(['reviewed_at' => now()]));
        $this->assertSame(CommunityCopy::REMOVED_HEADING, $removed['heading']);
        $this->assertSame(CommunityCopy::REMOVED, $removed['message']);
        $this->assertTrue($removed['canApply']);

        $editor = $this->service->standing(User::factory()->editor()->create());
        $this->assertFalse($editor['canApply']);
    }

    #[Test]
    public function an_approved_status_without_the_member_role_reads_as_suspended(): void
    {
        $user = User::factory()->create(['community_status' => CommunityStatus::Approved]);

        $standing = $this->service->standing($user);

        $this->assertSame(CommunityCopy::SUSPENDED_HEADING, $standing['heading']);
        $this->assertNull($standing['chatUrl']);
    }
}
