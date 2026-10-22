<?php

namespace Tests\Feature\Auth;

use App\Constants\AuthScreens;
use App\Constants\FormControls;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The community application form shares the sign-in craft: underlined fields,
 * the password toggle with a length-only strength line, styled checkboxes, the
 * solid primary pill with its busy label and one calm error summary. The card
 * box is gone; the section opens with its label, heading and a brass rule.
 */
class CommunitySignupFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_visitor_form_is_set_in_the_reading_room_style(): void
    {
        $response = $this->get(route('community'))->assertOk();

        $response
            ->assertSee('<section class="lg:pl-8" aria-labelledby="apply-heading">', false)
            ->assertSee(AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['title'])
            ->assertSee('class="rule-brass mt-6" data-rule', false)
            ->assertSee('x-data="authForm()" x-on:submit="submit($event)" data-apply-form', false)
            ->assertSee('class="form-style-underline mt-8 space-y-8" x-data="authForm()"', false)
            ->assertSee('<input class="form-control__input" id="name" name="name" required="required" aria-describedby="name-hint" data-label="your name" type="text"', false)
            ->assertSee('id="email" name="email" required="required" aria-describedby="email-hint"', false)
            ->assertSee('We will send a link to confirm it.')
            ->assertSee('id="password" name="password" required="required" aria-describedby="password-hint"', false)
            ->assertSee('id="password_confirmation" name="password_confirmation" required="required"', false)
            ->assertSee('<textarea class="form-control__input" id="reason_for_joining" name="reason_for_joining" required="required" maxlength="1500" aria-describedby="reason_for_joining-hint reason_for_joining-count"', false)
            ->assertSee('<p id="reason_for_joining-count" class="form-message form-counter" data-form-counter data-max="1500"', false)
            ->assertSee('<input class="form-check__box" id="conduct_agreed" name="conduct_agreed" required="required" type="checkbox" value="1" data-msg-missing="'.FormControls::MESSAGE_CHECK.'">', false)
            ->assertSee('<input class="form-check__box" id="privacy_agreed" name="privacy_agreed" required="required" type="checkbox" value="1"', false)
            ->assertSee('<div class="form-strength" data-password-strength>', false)
            ->assertSee(AuthScreens::PASSWORD_HINT)
            ->assertSee('class="btn-pill-solid w-full" data-busy-label="'.AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['busy'].'">'.AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['action'].'</button>', false)
            ->assertSee('<a href="'.route('login').'" class="link-hairline">Sign in</a>', false)
            ->assertSee('class="form-honeypot" aria-hidden="true"', false)
            ->assertDontSee('rounded-card border border-stone bg-linen p-8', false)
            ->assertSee('data-error-summary hidden>', false)
            ->assertDontSee('livewire', false);

        $html = $response->getContent();
        $this->assertSame(2, preg_match_all('/autocomplete="new-password"/', $html));
        $this->assertSame(1, preg_match_all('/data-password-strength/', $html), 'Only the password itself shows strength');
        $this->assertSame(2, preg_match_all('/class="form-control__action"/', $html), 'Both password fields can be shown');
    }

    #[Test]
    public function a_failed_application_shows_the_summary_linked_to_each_field(): void
    {
        $this->from(route('community'))->post(route('community.apply'), [])->assertRedirect(route('community'));

        $this->get(route('community'))
            ->assertOk()
            ->assertSee('role="alert" tabindex="-1" data-error-summary', false)
            ->assertSee(FormControls::ERROR_SUMMARY_TITLE)
            ->assertSee('<a href="#name">', false)
            ->assertSee('<a href="#email">', false)
            ->assertSee('<a href="#affiliation">', false)
            ->assertSee('<a href="#conduct_agreed">', false)
            ->assertSee('<p id="name-error" class="form-message form-error" data-form-error role="alert">', false)
            ->assertSee('<p id="conduct_agreed-error" class="form-message form-error" data-form-error role="alert">', false)
            ->assertSee('aria-invalid="true"', false);
    }

    #[Test]
    public function a_signed_in_user_who_may_apply_gets_the_same_form_without_account_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('community'))
            ->assertOk()
            ->assertSee('x-data="authForm()" x-on:submit="submit($event)" data-apply-form', false)
            ->assertSee('id="affiliation" name="affiliation" required="required" aria-describedby="affiliation-hint"', false)
            ->assertSee('autocomplete="organization"', false)
            ->assertSee('class="form-check__box"', false)
            ->assertSee('data-busy-label="'.AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['busy'].'"', false)
            ->assertDontSee('id="password" name="password"', false)
            ->assertDontSee('id="name" name="name"', false);
    }
}
