<?php

namespace Tests\Feature\Auth;

use App\Constants\AuthScreens;
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
            ->assertSee('<input id="name" name="name" type="text" value="" class="field__input" required="required" autocomplete="name">', false)
            ->assertSee('<input id="email" name="email" type="email" value="" class="field__input" required="required" autocomplete="email" aria-describedby="email-hint">', false)
            ->assertSee('<input id="password" name="password" type="password" value="" class="field__input field__input--toggle" required="required" autocomplete="new-password" aria-describedby="password-hint"', false)
            ->assertSee('<input id="password_confirmation" name="password_confirmation" type="password" value="" class="field__input field__input--toggle" required="required" autocomplete="new-password" x-bind:type="type"', false)
            ->assertSee('<textarea id="reason_for_joining" name="reason_for_joining" rows="6" class="field__input resize-y" required="required" maxlength="1500" x-on:input="count = $event.target.value.length" aria-describedby="reason_for_joining-count">', false)
            ->assertSee('<input type="checkbox" id="conduct_agreed" name="conduct_agreed" value="1" class="check__input" required="required">', false)
            ->assertSee('<input type="checkbox" id="privacy_agreed" name="privacy_agreed" value="1" class="check__input" required="required">', false)
            ->assertSee('<div class="strength" data-password-strength>', false)
            ->assertSee(AuthScreens::PASSWORD_HINT)
            ->assertSee('class="btn-pill-solid w-full" data-busy-label="'.AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['busy'].'">'.AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['action'].'</button>', false)
            ->assertSee('<a href="'.route('login').'" class="link-hairline">Sign in</a>', false)
            ->assertSee('class="form-honeypot" aria-hidden="true"', false)
            ->assertDontSee('rounded-card border border-stone bg-linen p-8', false)
            ->assertDontSee('data-error-summary', false)
            ->assertDontSee('livewire', false);

        $html = $response->getContent();
        $this->assertSame(2, preg_match_all('/autocomplete="new-password"/', $html));
        $this->assertSame(1, preg_match_all('/data-password-strength/', $html), 'Only the password itself shows strength');
        $this->assertSame(2, preg_match_all('/class="field__toggle"/', $html), 'Both password fields can be shown');
    }

    #[Test]
    public function a_failed_application_shows_the_summary_linked_to_each_field(): void
    {
        $this->from(route('community'))->post(route('community.apply'), [])->assertRedirect(route('community'));

        $this->get(route('community'))
            ->assertOk()
            ->assertSee('role="alert" tabindex="-1" data-error-summary', false)
            ->assertSee(AuthScreens::ERROR_SUMMARY_TITLE)
            ->assertSee('<a href="#name">', false)
            ->assertSee('<a href="#email">', false)
            ->assertSee('<a href="#affiliation">', false)
            ->assertSee('<a href="#conduct_agreed">', false)
            ->assertSee('<p id="name-error" class="field__error" role="alert">', false)
            ->assertSee('<p id="conduct_agreed-error" class="field__error" role="alert">', false)
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
            ->assertSee('<input id="affiliation" name="affiliation" type="text" value="" class="field__input" required="required" autocomplete="organization" aria-describedby="affiliation-hint">', false)
            ->assertSee('class="check__input"', false)
            ->assertSee('data-busy-label="'.AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['busy'].'"', false)
            ->assertDontSee('<input id="password"', false)
            ->assertDontSee('<input id="name"', false);
    }
}
