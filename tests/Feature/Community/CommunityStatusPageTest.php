<?php

namespace Tests\Feature\Community;

use App\Constants\CommunityCopy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunityStatusPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_pending_applicant_is_told_their_application_is_being_reviewed(): void
    {
        $this->actingAs(User::factory()->applicant()->create())->get(route('community'))->assertOk()
            ->assertSee('data-standing="pending"', false)
            ->assertSee(CommunityCopy::PENDING_HEADING)
            ->assertSee(CommunityCopy::PENDING)
            ->assertDontSee(route('community.chat'))
            ->assertDontSee('name="conduct_agreed"', false);
    }

    #[Test]
    public function a_rejected_applicant_sees_a_polite_message_and_no_chat_link(): void
    {
        $this->actingAs(User::factory()->rejectedApplicant()->create())->get(route('community'))->assertOk()
            ->assertSee('data-standing="rejected"', false)
            ->assertSee(CommunityCopy::REJECTED_HEADING)
            ->assertSee(CommunityCopy::REJECTED)
            ->assertDontSee(route('community.chat'))
            ->assertDontSee('name="conduct_agreed"', false);
    }

    #[Test]
    public function a_suspended_member_sees_the_suspension_notice(): void
    {
        $this->actingAs(User::factory()->suspendedMember()->create())->get(route('community'))->assertOk()
            ->assertSee('data-standing="suspended"', false)
            ->assertSee(CommunityCopy::SUSPENDED_HEADING)
            ->assertSee(CommunityCopy::SUSPENDED)
            ->assertDontSee(route('community.chat'));
    }

    #[Test]
    public function an_approved_member_and_an_admin_get_a_link_to_the_chat(): void
    {
        foreach ([User::factory()->member()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('community'))->assertOk()
                ->assertSee('data-standing="approved"', false)
                ->assertSee('href="'.route('community.chat').'"', false)
                ->assertSee('Open the chat');
        }
    }

    #[Test]
    public function a_removed_member_is_told_their_membership_ended_and_may_apply_again(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['reviewed_at' => now(), 'reviewed_by' => $admin->id]);

        $this->actingAs($user)->get(route('community'))->assertOk()
            ->assertSee(CommunityCopy::REMOVED_HEADING)
            ->assertSee(CommunityCopy::REMOVED)
            ->assertSee('name="affiliation"', false);
    }

    #[Test]
    public function the_community_page_marks_itself_current_in_the_navigation_and_never_loads_livewire(): void
    {
        $html = $this->actingAs(User::factory()->member()->create())->get(route('community'))->assertOk()->getContent();

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringNotContainsStringIgnoringCase('livewire', $html);
    }
}
