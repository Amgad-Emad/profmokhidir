<?php

namespace Tests\Feature\Community;

use App\Constants\AuditAction;
use App\Constants\CommunityCopy;
use App\Constants\CommunityStatus;
use App\Constants\RateLimits;
use App\Constants\Roles;
use App\Http\Requests\CommunitySignupRequest;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunitySignupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Fatima El-Tayeb',
            'email' => 'Fatima@Example.com',
            'password' => 'a-long-passphrase-9',
            'password_confirmation' => 'a-long-passphrase-9',
            'affiliation' => 'University of Khartoum',
            'academic_field' => 'Soil science',
            'reason_for_joining' => 'I studied under Professor Khidir and would like to keep in touch with colleagues.',
            'conduct_agreed' => '1',
            'privacy_agreed' => '1',
        ], $overrides);
    }

    #[Test]
    public function the_page_explains_the_community_and_shows_the_form_with_a_honeypot_and_no_livewire(): void
    {
        $response = $this->get(route('community'))->assertOk();

        $response
            ->assertSee('<title>Scientific Community · ', false)
            ->assertSee(CommunityCopy::HEADING)
            ->assertSee(CommunityCopy::WHO_HEADING)
            ->assertSee(CommunityCopy::CONDUCT_HEADING)
            ->assertSee(CommunityCopy::conductRules()[0])
            ->assertSee(CommunityCopy::PRIVACY)
            ->assertSee('action="'.route('community.apply').'"', false)
            ->assertSee('id="name" name="name"', false)
            ->assertSee('id="email" name="email"', false)
            ->assertSee('id="password" name="password"', false)
            ->assertSee('id="password_confirmation"', false)
            ->assertSee('id="affiliation" name="affiliation"', false)
            ->assertSee('id="academic_field" name="academic_field"', false)
            ->assertSee('id="reason_for_joining" name="reason_for_joining"', false)
            ->assertSee('name="conduct_agreed"', false)
            ->assertSee('name="privacy_agreed"', false)
            ->assertSee('class="form-honeypot" aria-hidden="true"', false)
            ->assertSee('name="'.CommunitySignupRequest::HONEYPOT.'" tabindex="-1" autocomplete="off"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function a_valid_application_creates_a_pending_applicant_signs_them_in_and_sends_the_verification_email(): void
    {
        Notification::fake();

        $response = $this->from(route('community'))->post(route('community.apply'), $this->payload());

        $response->assertRedirect(route('community'))->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'fatima@example.com')->firstOrFail();

        $this->assertSame('Fatima El-Tayeb', $user->name);
        $this->assertTrue(Hash::check('a-long-passphrase-9', $user->password));
        $this->assertSame(CommunityStatus::Pending, $user->community_status);
        $this->assertSame('University of Khartoum', $user->affiliation);
        $this->assertSame('Soil science', $user->academic_field);
        $this->assertStringContainsString('keep in touch', $user->reason_for_joining);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole(Roles::APPLICANT));
        $this->assertFalse($user->canAccessChat());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditAction::COMMUNITY_APPLIED, 'subject_id' => $user->id]);

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get(route('community'))->assertOk()
            ->assertSee(CommunityCopy::SUBMITTED)
            ->assertSee(CommunityCopy::PENDING_HEADING)
            ->assertDontSee('name="conduct_agreed"', false);
    }

    #[Test]
    public function the_honeypot_silently_discards_bots(): void
    {
        Notification::fake();

        $this->post(route('community.apply'), $this->payload([CommunitySignupRequest::HONEYPOT => 'https://spam.example']))
            ->assertRedirect(route('community'));

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
        Notification::assertNothingSent();
    }

    #[Test]
    public function every_field_and_both_agreements_are_required(): void
    {
        $response = $this->from(route('community'))->post(route('community.apply'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
            'affiliation' => '',
            'academic_field' => '',
            'reason_for_joining' => '',
        ]);

        $response->assertRedirect(route('community'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'affiliation', 'academic_field', 'reason_for_joining', 'conduct_agreed', 'privacy_agreed']);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function an_existing_email_address_is_refused_with_a_pointer_to_sign_in(): void
    {
        User::factory()->create(['email' => 'fatima@example.com']);

        $this->from(route('community'))->post(route('community.apply'), $this->payload())
            ->assertSessionHasErrors(['email' => 'An account with this email address already exists. Sign in to continue.']);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function signups_are_rate_limited_per_address(): void
    {
        Notification::fake();

        foreach (range(1, RateLimits::COMMUNITY_SIGNUP_PER_HOUR) as $i) {
            $this->post(route('community.apply'), $this->payload(['email' => "person{$i}@example.com"]))->assertRedirect();
            auth()->logout();
        }

        $this->post(route('community.apply'), $this->payload(['email' => 'one-too-many@example.com']))->assertStatus(429);
        $this->assertDatabaseMissing('users', ['email' => 'one-too-many@example.com']);
    }

    #[Test]
    public function a_signed_in_user_who_never_applied_can_apply_with_their_existing_account(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('community'))->assertOk()
            ->assertSee(CommunityCopy::NOT_APPLIED_HEADING)
            ->assertSee('name="affiliation"', false)
            ->assertDontSee('id="name" name="name"', false);

        $this->actingAs($user)->post(route('community.apply'), [
            'affiliation' => 'Gezira Research Station',
            'academic_field' => 'Agronomy',
            'reason_for_joining' => 'A former colleague.',
            'conduct_agreed' => '1',
            'privacy_agreed' => '1',
        ])->assertRedirect(route('community'));

        $user->refresh();
        $this->assertSame(CommunityStatus::Pending, $user->community_status);
        $this->assertSame('Gezira Research Station', $user->affiliation);
        $this->assertTrue($user->hasRole(Roles::APPLICANT));
        Notification::assertNothingSent();
    }

    #[Test]
    public function pending_applicants_members_and_staff_cannot_apply_again(): void
    {
        foreach ([User::factory()->applicant()->create(), User::factory()->member()->create(), User::factory()->editor()->create()] as $user) {
            $this->actingAs($user)->post(route('community.apply'), $this->payload())->assertForbidden();
        }
    }
}
