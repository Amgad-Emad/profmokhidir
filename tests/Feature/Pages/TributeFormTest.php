<?php

namespace Tests\Feature\Pages;

use App\Constants\Taglines;
use App\Constants\TributeForm;
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
    public function the_first_step_renders_every_field_with_hints_a_live_counter_a_honeypot_and_no_livewire(): void
    {
        $response = $this->get(route('tributes.create'))->assertOk();

        $response
            ->assertSee('<title>'.Taglines::LEAVE_TRIBUTE.' · ', false)
            ->assertSeeInOrder(['section-header', 'Tributes', 'rule-brass', 'id="section-title"', Taglines::LEAVE_TRIBUTE, TributeForm::INTRO], false)
            ->assertSeeInOrder(['<p class="tribute-steps mt-8" data-rise data-step="write">', '<span class="sr-only">Step 1 of 2</span>', 'Step I of II', 'aria-current="step">Write</span>', '>Preview</span>'], false)
            ->assertSee('action="'.route('tributes.preview').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('class="form-style-underline mt-12 space-y-10" data-rise', false)
            ->assertSee('id="name" name="name" required="required"', false)
            ->assertSee(TributeForm::hint('name'))
            ->assertSee('<select class="form-control__input" id="relationship" name="relationship" required="required"', false)
            ->assertSee('<option value="student"', false)
            ->assertSee('<option value="other"', false)
            ->assertSee('id="location" name="location"', false)
            ->assertSee(TributeForm::hint('location'))
            ->assertSee('<textarea class="form-control__input" id="message" name="message" required="required"', false)
            ->assertSee('rows="8"', false)
            ->assertSee('Share a memory, a lesson or a moment.')
            ->assertSee('maxlength="'.Tribute::MESSAGE_MAX_LENGTH.'"', false)
            ->assertSee('data-form-counter data-max="'.Tribute::MESSAGE_MAX_LENGTH.'"', false)
            ->assertSee('0 / '.Tribute::MESSAGE_MAX_LENGTH.' characters</p>', false)
            ->assertSee('id="email" name="email"', false)
            ->assertSee('never shown publicly')
            ->assertSee('class="form-honeypot" aria-hidden="true"', false)
            ->assertSee('name="'.StoreTributeRequest::HONEYPOT.'" tabindex="-1" autocomplete="off"', false)
            ->assertDontSee('name="consent"', false)
            ->assertSee('Preview your tribute')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function the_preview_step_shows_the_tribute_as_it_will_appear_with_the_consent_and_hidden_fields(): void
    {
        $response = $this->post(route('tributes.preview'), $this->payload(['consent' => null]))->assertOk();

        $response
            ->assertSee('<meta name="robots" content="noindex', false)
            ->assertSeeInOrder([
                TributeForm::PREVIEW_INTRO,
                'data-step="preview"', '<span class="sr-only">Step 2 of 2</span>', 'Step II of II', '>Write</span>', 'aria-current="step">Preview</span>',
                '<div class="leaf mt-12" data-rise data-preview>', '<article class="tribute-entry">',
                '<span class="hang-quote">&ldquo;</span>He taught me to look twice before answering.&rdquo;</p>',
                '<h2 class="tribute-entry__author smallcaps">Ahmed El-Sheikh</h2>', 't-label text-accent-text tribute-entry__meta">Student · Khartoum, Sudan',
                '<form novalidate method="POST" action="'.route('tributes.store').'"',
                '<input type="hidden" name="name" value="Ahmed El-Sheikh">',
                '<input type="hidden" name="relationship" value="student">',
                '<input type="hidden" name="location" value="Khartoum, Sudan">',
                '<input type="hidden" name="message" value="He taught me to look twice before answering.">',
                '<input type="hidden" name="email" value="ahmed@example.com">',
                'class="form-honeypot" aria-hidden="true"',
                'id="consent" name="consent" required="required"', TributeForm::CONSENT,
                'Send tribute', 'formaction="'.route('tributes.edit').'"', 'formnovalidate', 'Change something',
            ], false)
            ->assertDontSee('tribute-entry__card', false)
            ->assertDontSee('livewire', false);

        $this->assertDatabaseCount('tributes', 0);
    }

    #[Test]
    public function an_invalid_first_step_returns_to_the_form_with_errors_and_input(): void
    {
        $this->from(route('tributes.create'))
            ->post(route('tributes.preview'), ['name' => '', 'relationship' => 'stranger', 'message' => '', 'location' => 'Khartoum'])
            ->assertRedirect(route('tributes.create'))
            ->assertSessionHasErrors(['name', 'relationship', 'message'])
            ->assertSessionDoesntHaveErrors(['consent'])
            ->assertSessionHasInput('location', 'Khartoum');
    }

    #[Test]
    public function the_preview_can_be_revisited_from_flashed_input_and_sends_the_visitor_back_otherwise(): void
    {
        $this->get('/tributes/preview')->assertRedirect(route('tributes.create'));

        $this->withSession(['_old_input' => $this->payload()])
            ->get('/tributes/preview')
            ->assertOk()
            ->assertSee('Ahmed El-Sheikh')
            ->assertSee('<input type="hidden" name="message" value="He taught me to look twice before answering.">', false);
    }

    #[Test]
    public function change_something_returns_to_the_form_with_the_fields_filled_in(): void
    {
        $response = $this->post(route('tributes.edit'), $this->payload())
            ->assertRedirect(route('tributes.create'))
            ->assertSessionHasInput('name', 'Ahmed El-Sheikh')
            ->assertSessionHasInput('message', 'He taught me to look twice before answering.');

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('value="Ahmed El-Sheikh"', false)
            ->assertSee('<option value="student" selected', false)
            ->assertSee('He taught me to look twice before answering.</textarea>', false)
            ->assertSee('44 / '.Tribute::MESSAGE_MAX_LENGTH.' characters</p>', false);
    }

    #[Test]
    public function sending_without_consent_returns_to_the_preview_with_the_tribute_intact(): void
    {
        Notification::fake();

        $response = $this->from('/tributes/preview')
            ->post(route('tributes.store'), $this->payload(['consent' => null]))
            ->assertRedirect('/tributes/preview')
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('tributes', 0);

        // The flashed errors are consumed by the assertion above, so render from a fresh submission.
        $this->followRedirects($this->from('/tributes/preview')->post(route('tributes.store'), $this->payload(['consent' => null])))
            ->assertOk()
            ->assertSee('Tick the box to agree that your tribute may be published.')
            ->assertSee('aria-current="page"', false)
            ->assertSee('<input type="hidden" name="name" value="Ahmed El-Sheikh">', false);
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
            ->assertSee('glow-brass on-dark bg-surface', false)
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
            ->assertSee('Enter your name.')
            ->assertSee('Please choose one of the listed relationships.')
            ->assertSee('Shorten your tribute to '.Tribute::MESSAGE_MAX_LENGTH.' characters or fewer.')
            ->assertSee('Enter an email address in the form name@example.com, or leave it empty.')
            ->assertDontSee('Tick the box to agree that your tribute may be published.');
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
            ->assertSeeInOrder(['t-label text-accent-text', 'Tributes', 'rule-brass', 'class="seal seal--open" aria-hidden="true" data-rise', 'brand-mark--ring', 'data-size="56" data-tone="ivory"', 'id="section-title" class="t-quote', Taglines::TRIBUTE_THANKS], false)
            ->assertSee('href="'.route('tributes').'"', false)
            ->assertDontSee('livewire', false);
    }
}
