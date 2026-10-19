<?php

namespace Tests\Feature\Pages;

use App\Constants\Taglines;
use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Http\Controllers\TributeController;
use App\Http\Requests\StoreTributeRequest;
use App\Models\Tribute;
use App\Models\User;
use App\Notifications\TributeAwaitingReview;
use App\Services\TributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ahmed El-Sheikh',
            'relationship' => TributeRelationship::Student->value,
            'location' => 'Khartoum, Sudan',
            'message' => 'He taught me to look twice before answering.',
            'email' => 'ahmed@example.com',
            'consent' => '1',
        ], $overrides);
    }

    #[Test]
    public function the_form_renders_every_field_with_a_live_counter_a_honeypot_and_no_livewire(): void
    {
        $response = $this->get(route('tributes.create'))->assertOk();

        $response
            ->assertSee('<title>'.Taglines::LEAVE_TRIBUTE.' · ', false)
            ->assertSeeInOrder(['section-header', 'Tributes', 'rule-brass', 'id="section-title"', Taglines::LEAVE_TRIBUTE], false)
            ->assertSee('action="'.route('tributes.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('id="name" name="name" type="text"', false)
            ->assertSee('<select id="relationship" name="relationship"', false)
            ->assertSee('<option value="student"', false)
            ->assertSee('<option value="other"', false)
            ->assertSee('id="location" name="location"', false)
            ->assertSee('x-data="{ count: 0 }"', false)
            ->assertSee('<textarea id="message" name="message" rows="8"', false)
            ->assertSee('maxlength="'.Tribute::MESSAGE_MAX_LENGTH.'"', false)
            ->assertSee('<span x-text="count">0</span> / '.Tribute::MESSAGE_MAX_LENGTH.' characters', false)
            ->assertSee('id="email" name="email" type="email"', false)
            ->assertSee('Never shown publicly.')
            ->assertSee('class="form-honeypot" aria-hidden="true"', false)
            ->assertSee('name="'.StoreTributeRequest::HONEYPOT.'" tabindex="-1" autocomplete="off"', false)
            ->assertSee('type="checkbox" name="consent"', false)
            ->assertSee('Send tribute')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function the_home_page_and_the_tributes_page_link_to_the_form(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('tributes.create').'"', false);
        $this->get(route('tributes'))->assertOk()->assertSee('href="'.route('tributes.create').'"', false);
    }

    #[Test]
    public function a_valid_submission_is_stored_as_pending_with_a_hashed_ip_and_shows_the_confirmation(): void
    {
        Notification::fake();

        $response = $this->from(route('tributes.create'))->post(route('tributes.store'), $this->payload(), ['REMOTE_ADDR' => '203.0.113.9']);

        $response->assertRedirect(route('tributes.thanks'))->assertSessionHasNoErrors();

        $tribute = Tribute::query()->sole();

        $this->assertSame(TributeStatus::Pending, $tribute->status);
        $this->assertSame('Ahmed El-Sheikh', $tribute->name);
        $this->assertSame(TributeRelationship::Student, $tribute->relationship);
        $this->assertSame('Khartoum, Sudan', $tribute->location);
        $this->assertSame('ahmed@example.com', $tribute->email);
        $this->assertSame(app(TributeService::class)->hashIp('203.0.113.9'), $tribute->ip_hash);
        $this->assertStringNotContainsString('203.0.113.9', (string) $tribute->ip_hash);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee(Taglines::TRIBUTE_THANKS)
            ->assertSee('glow-brass on-dark bg-evergreen', false)
            ->assertDontSee('quick')
            ->assertDontSee('ahmed@example.com');
    }

    #[Test]
    public function optional_fields_may_be_left_empty(): void
    {
        Notification::fake();

        $this->post(route('tributes.store'), $this->payload(['location' => '', 'email' => '']))
            ->assertRedirect(route('tributes.thanks'))
            ->assertSessionHasNoErrors();

        $tribute = Tribute::query()->sole();

        $this->assertNull($tribute->location);
        $this->assertNull($tribute->email);
    }

    #[Test]
    public function validation_rejects_missing_or_invalid_fields(): void
    {
        Notification::fake();

        $invalid = [
            'name' => '',
            'relationship' => 'stranger',
            'location' => str_repeat('x', StoreTributeRequest::LOCATION_MAX_LENGTH + 1),
            'message' => str_repeat('m', Tribute::MESSAGE_MAX_LENGTH + 1),
            'email' => 'not-an-email',
        ];

        $this->from(route('tributes.create'))
            ->post(route('tributes.store'), $invalid)
            ->assertRedirect(route('tributes.create'))
            ->assertSessionHasErrors(['name', 'relationship', 'location', 'message', 'email', 'consent']);

        $this->assertDatabaseCount('tributes', 0);
        Notification::assertNothingSent();

        // The flashed errors are consumed by the assertion above, so render from a fresh submission.
        $this->followRedirects($this->from(route('tributes.create'))->post(route('tributes.store'), $invalid))
            ->assertOk()
            ->assertSee('Please tell us your name.')
            ->assertSee('Please choose one of the listed relationships.')
            ->assertSee('Your tribute can be at most '.Tribute::MESSAGE_MAX_LENGTH.' characters.')
            ->assertSee('Please enter a valid email address, or leave it empty.')
            ->assertSee('Please confirm that your tribute may be published.');
    }

    #[Test]
    public function a_filled_honeypot_is_dropped_silently_with_the_same_confirmation(): void
    {
        Notification::fake();

        $this->post(route('tributes.store'), $this->payload([StoreTributeRequest::HONEYPOT => 'https://spam.example']))
            ->assertRedirect(route('tributes.thanks'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tributes', 0);
        Notification::assertNothingSent();
    }

    #[Test]
    public function submissions_are_limited_to_three_per_hour_per_address(): void
    {
        Notification::fake();

        $this->assertSame(3, TributeService::SUBMISSION_LIMIT);
        $this->assertSame(60, TributeService::SUBMISSION_WINDOW_MINUTES);

        foreach (range(1, 3) as $n) {
            $this->post(route('tributes.store'), $this->payload(['name' => 'Visitor '.$n]), ['REMOTE_ADDR' => '203.0.113.9'])
                ->assertRedirect(route('tributes.thanks'));
        }

        $this->from(route('tributes.create'))
            ->post(route('tributes.store'), $this->payload(['name' => 'Visitor 4']), ['REMOTE_ADDR' => '203.0.113.9'])
            ->assertRedirect(route('tributes.create'))
            ->assertSessionHasErrors(TributeController::LIMIT_ERROR)
            ->assertSessionHasInput('name', 'Visitor 4');

        $this->assertDatabaseCount('tributes', 3);

        // Another address is unaffected.
        $this->post(route('tributes.store'), $this->payload(['name' => 'Someone else']), ['REMOTE_ADDR' => '198.51.100.7'])
            ->assertRedirect(route('tributes.thanks'));

        $this->assertDatabaseCount('tributes', 4);

        $this->followRedirects($this->from(route('tributes.create'))->post(route('tributes.store'), $this->payload(['name' => 'Visitor 5']), ['REMOTE_ADDR' => '203.0.113.9']))
            ->assertOk()
            ->assertSee('role="alert"', false)
            ->assertSee('Too many tributes have been submitted from this address recently.');
    }

    #[Test]
    public function admins_and_editors_are_emailed_when_a_tribute_is_waiting(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $member = User::factory()->member()->create();

        $this->post(route('tributes.store'), $this->payload())->assertRedirect(route('tributes.thanks'));

        $tribute = Tribute::query()->sole();

        Notification::assertSentTo([$admin, $editor], TributeAwaitingReview::class, function (TributeAwaitingReview $notification, array $channels) use ($tribute): bool {
            return $channels === ['mail'] && $notification->tribute->is($tribute);
        });
        Notification::assertNotSentTo($member, TributeAwaitingReview::class);
        Notification::assertCount(2);
    }

    #[Test]
    public function the_confirmation_page_is_calm_and_links_back(): void
    {
        $this->get(route('tributes.thanks'))
            ->assertOk()
            ->assertSee('<title>Thank you · ', false)
            ->assertSeeInOrder(['t-label text-brass', 'Tributes', 'rule-brass', 'id="section-title" class="t-quote', Taglines::TRIBUTE_THANKS], false)
            ->assertSee('href="'.route('tributes').'"', false)
            ->assertDontSee('livewire', false);
    }
}
