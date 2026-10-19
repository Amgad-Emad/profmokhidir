<?php

namespace Tests\Feature\Notifications;

use App\Constants\CommunityStatus;
use App\Models\User;
use App\Notifications\CommunityApplicationReviewed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunityApplicationReviewedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_queued_mail_with_a_subject_per_decision(): void
    {
        $notification = new CommunityApplicationReviewed(CommunityStatus::Approved);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(['mail'], $notification->via(User::factory()->make()));
        $this->assertSame('Your community application has been approved', $notification->subject());
        $this->assertSame('About your community application', (new CommunityApplicationReviewed(CommunityStatus::Rejected))->subject());
        $this->assertSame('Your community membership has been suspended', (new CommunityApplicationReviewed(CommunityStatus::Suspended))->subject());
        $this->assertSame('Your community membership has ended', (new CommunityApplicationReviewed(CommunityStatus::None))->subject());
    }

    #[Test]
    public function the_mail_greets_the_applicant_includes_the_note_and_links_to_the_community(): void
    {
        $applicant = User::factory()->applicant()->create(['name' => 'Omar Siddig']);

        $mail = (new CommunityApplicationReviewed(CommunityStatus::Approved, 'Welcome aboard.'))->toMail($applicant)->toArray();

        $this->assertSame('Hello Omar Siddig,', $mail['greeting']);
        $this->assertSame('Your community application has been approved', $mail['subject']);
        $this->assertStringContainsString('has been approved', implode(' ', $mail['introLines']));
        $this->assertStringContainsString('Note from the reviewer: Welcome aboard.', implode(' ', $mail['introLines']));
        $this->assertSame('Open the community', $mail['actionText']);
        $this->assertSame(route('community'), $mail['actionUrl']);
    }

    #[Test]
    public function a_rejection_without_a_note_has_no_note_line(): void
    {
        $applicant = User::factory()->applicant()->create();

        $mail = (new CommunityApplicationReviewed(CommunityStatus::Rejected))->toMail($applicant)->toArray();

        $this->assertStringNotContainsString('Note from the reviewer', implode(' ', $mail['introLines']));
        $this->assertStringContainsString('has not been accepted', implode(' ', $mail['introLines']));
        $this->assertSame('Visit the community page', $mail['actionText']);
        $this->assertSame(['status' => 'rejected', 'note' => null], (new CommunityApplicationReviewed(CommunityStatus::Rejected))->toArray($applicant));
    }
}
