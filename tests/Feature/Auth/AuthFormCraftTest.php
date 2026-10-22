<?php

namespace Tests\Feature\Auth;

use App\Constants\AuthScreens;
use App\Constants\FormControls;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Form craft on the signed-out screens: underlined fields with the right
 * autocomplete, the password toggle and Caps Lock note, one calm error summary
 * linked to its field (with the generic credentials wording), the solid primary
 * pill with its busy label, the styled checkbox, the six-box code input and the
 * length-only strength hint.
 */
class AuthFormCraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_form_uses_underlined_fields_a_primary_pill_and_a_styled_checkbox(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $response
            ->assertSee('x-data="authForm()" x-on:submit="submit($event)" data-auth-form', false)
            ->assertSee('<div class="auth__column form-style-underline" data-rise-group>', false)
            ->assertSee('<label id="email-label" for="email" class="t-label form-label">Email</label>', false)
            ->assertSee('<p id="email-hint" class="form-hint"><span class="form-hint__requirement">'.FormControls::REQUIRED.'</span></p>', false)
            ->assertSee('<input class="form-control__input" id="email" name="email" required="required" aria-describedby="email-hint" data-label="email" type="email"', false)
            ->assertSee('data-msg-type="'.FormControls::MESSAGE_EMAIL.'" autocomplete="'.AuthScreens::AUTOCOMPLETE_USERNAME.'" autofocus="autofocus">', false)
            ->assertSee('x-bind:type="type" x-on:keydown="key($event)" x-on:keyup="key($event)" x-on:blur="blur()" x-on:input="input($event)" autocomplete="'.AuthScreens::AUTOCOMPLETE_CURRENT_PASSWORD.'" spellcheck="false" autocapitalize="off">', false)
            ->assertSee('class="form-control__action"', false)
            ->assertSee('aria-pressed="false" aria-label="'.FormControls::SHOW_PASSWORD.'" aria-controls="password"', false)
            ->assertSee('role="status" aria-live="polite" x-text="capsWarning"', false)
            ->assertSee(FormControls::CAPS_LOCK)
            ->assertSee('<input class="form-check__box" id="remember" name="remember" type="checkbox" value="1">', false)
            ->assertSee(AuthScreens::REMEMBER_LABEL)
            ->assertSee('<a href="'.route('password.request').'" class="link-hairline">'.AuthScreens::FORGOT_LINK.'</a>', false)
            ->assertSee('class="btn-pill-solid w-full" data-busy-label="'.AuthScreens::screen(AuthScreens::LOGIN)['busy'].'">'.AuthScreens::screen(AuthScreens::LOGIN)['action'].'</button>', false)
            ->assertSee('data-error-summary hidden>', false)
            ->assertDontSee('focus:border-bronze', false)
            ->assertDontSee('data-password-strength', false);
    }

    #[Test]
    public function a_failed_sign_in_shows_one_calm_summary_linked_to_the_field_with_the_generic_wording(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])->assertRedirect(route('login'));

        $response = $this->get(route('login'))->assertOk();

        $response
            ->assertSee('class="form-summary mt-8" role="alert" tabindex="-1" data-error-summary', false)
            ->assertSee(FormControls::ERROR_SUMMARY_TITLE)
            ->assertSee('<a href="#email">'.AuthScreens::FAILED_CREDENTIALS.'</a>', false)
            ->assertSee('aria-invalid="true" aria-describedby="email-hint email-error"', false)
            ->assertSee('<p id="email-error" class="form-message form-error" data-form-error role="alert">', false);

        $this->assertSame(2, substr_count($response->getContent(), AuthScreens::FAILED_CREDENTIALS), 'In the summary and under the field, nowhere else');
        $this->assertSame(1, substr_count($response->getContent(), 'data-error-summary'), 'One summary');
    }

    #[Test]
    public function an_unknown_address_and_a_wrong_password_read_the_same_so_accounts_cannot_be_probed(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
        $known = $this->get(route('login'))->getContent();

        $this->flushSession();

        $this->from(route('login'))->post(route('login'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
        $unknown = $this->get(route('login'))->getContent();

        $this->assertStringContainsString(AuthScreens::FAILED_CREDENTIALS, $known);
        $this->assertStringContainsString(AuthScreens::FAILED_CREDENTIALS, $unknown);

        $message = fn (string $html): string => preg_match('/<p id="email-error"[^>]*>(.*?)<\/p>/s', $html, $m) ? strip_tags($m[1]) : '';
        $this->assertSame(trim($message($known)), trim($message($unknown)));
    }

    #[Test]
    public function the_two_factor_challenge_has_six_digit_boxes_a_hidden_code_and_a_switch_to_a_recovery_code(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret-password']);
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password']);

        $response = $this->get(route('two-factor.login'))->assertOk();

        $response
            ->assertSee('x-data="authChallenge({ recovery: false })" data-two-factor', false)
            ->assertSee('x-data="formOtp({ length: '.AuthScreens::CODE_LENGTH.' })" data-otp', false)
            ->assertSee('<legend id="code-label" class="t-label form-label">Authentication code</legend>', false)
            ->assertSee('aria-label="Digit 1 of 6"', false)
            ->assertSee('aria-label="Digit 6 of 6"', false)
            ->assertSee('<input type="hidden" name="code" x-ref="value" value="" x-bind:disabled="recovery"', false)
            ->assertSee('id="recovery_code" name="recovery_code" required="required"', false)
            ->assertSee('x-bind:disabled="!recovery" x-ref="recovery" spellcheck="false" autocapitalize="off" autocomplete="one-time-code">', false)
            ->assertSee('data-two-factor-switch>'.AuthScreens::USE_RECOVERY_CODE.'</button>', false)
            ->assertSee(AuthScreens::screen(AuthScreens::TWO_FACTOR)['intro'])
            ->assertSee(AuthScreens::TWO_FACTOR_RECOVERY_INTRO)
            ->assertSee('data-busy-label="'.AuthScreens::screen(AuthScreens::TWO_FACTOR)['busy'].'"', false);

        $html = $response->getContent();
        $this->assertSame(AuthScreens::CODE_LENGTH, preg_match_all('/<div class="form-control"><input class="form-control__input" type="text" id="code-\d"/', $html));
        $this->assertStringContainsString('<input class="form-control__input" type="text" id="code-1" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" aria-label="Digit 1 of 6" autofocus="autofocus"', $html, 'The first box takes the autofilled code');
        $this->assertSame(AuthScreens::CODE_LENGTH - 1, preg_match_all('/maxlength="1" autocomplete="off"/', $html));
        $this->assertSame(3, preg_match_all('/autocomplete="one-time-code"/', $html), 'The first box, the recovery field and the no-script fallback');
        $this->assertStringContainsString('<noscript>', $html);
    }

    #[Test]
    public function a_wrong_code_links_the_summary_to_the_first_box_and_a_wrong_recovery_code_opens_the_recovery_field(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret-password']);
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password']);

        // Asserting on the session here would restart it and age the flashed errors away before the next request.
        $this->from(route('two-factor.login'))->post(route('two-factor.login'), ['code' => '000000'])->assertRedirect(route('two-factor.login'));
        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('data-error-summary', false)
            ->assertSee('<a href="#code-1">', false)
            ->assertSee('<p id="code-error" class="form-message form-error" data-form-error role="alert">', false);

        $this->flushSession();
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password']);

        $this->from(route('two-factor.login'))->post(route('two-factor.login'), ['recovery_code' => 'not-a-code'])->assertRedirect(route('two-factor.login'));
        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('x-data="authChallenge({ recovery: true })"', false)
            ->assertSee('<a href="#recovery_code">', false)
            ->assertSee('data-two-factor-switch>'.AuthScreens::USE_AUTHENTICATOR_CODE.'</button>', false);
    }

    #[Test]
    public function the_reset_form_asks_for_a_new_password_with_a_length_only_strength_hint(): void
    {
        $user = User::factory()->editor()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

        $response
            ->assertSee('<input type="hidden" name="token" value="'.$token.'">', false)
            ->assertSee('value="'.$user->email.'"', false)
            ->assertSee('id="password" name="password"', false)
            ->assertSee('id="password_confirmation" name="password_confirmation"', false)
            ->assertSee(AuthScreens::PASSWORD_HINT)
            ->assertSee('<div class="form-strength" data-password-strength>', false)
            ->assertSee('x-bind:style="\'--strength: \' + strength"', false)
            ->assertSee('data-busy-label="'.AuthScreens::screen(AuthScreens::RESET_PASSWORD)['busy'].'"', false);

        $html = $response->getContent();
        $this->assertSame(2, preg_match_all('/autocomplete="new-password"/', $html));
        $this->assertSame(1, preg_match_all('/data-password-strength/', $html), 'The confirmation field has no strength line');
        $this->assertStringContainsString('from\\u0022:'.AuthScreens::PASSWORD_GOOD_LENGTH, $html, 'The strength levels reach the script');
    }

    #[Test]
    public function the_confirm_and_verify_screens_keep_their_actions(): void
    {
        $this->actingAs(User::factory()->editor()->create())
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertSee('id="password" name="password"', false)
            ->assertSee('autocomplete="'.AuthScreens::AUTOCOMPLETE_CURRENT_PASSWORD.'"', false)
            ->assertSee('data-busy-label="'.AuthScreens::screen(AuthScreens::CONFIRM_PASSWORD)['busy'].'">'.AuthScreens::screen(AuthScreens::CONFIRM_PASSWORD)['action'].'</button>', false);

        $this->actingAs(User::factory()->editor()->unverified()->create())
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('action="'.route('verification.send').'"', false)
            ->assertSee('data-busy-label="'.AuthScreens::screen(AuthScreens::VERIFY_EMAIL)['busy'].'">'.AuthScreens::screen(AuthScreens::VERIFY_EMAIL)['action'].'</button>', false)
            ->assertSee('<button type="submit" class="link-hairline">Sign out</button>', false);
    }

    #[Test]
    public function the_forgot_password_form_leads_back_to_sign_in(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('id="email" name="email"', false)
            ->assertSee('autocomplete="'.AuthScreens::AUTOCOMPLETE_USERNAME.'"', false)
            ->assertSee('data-busy-label="'.AuthScreens::screen(AuthScreens::FORGOT_PASSWORD)['busy'].'">'.AuthScreens::screen(AuthScreens::FORGOT_PASSWORD)['action'].'</button>', false)
            ->assertSee('<a href="'.route('login').'" class="link-hairline">Back to sign in</a>', false);
    }
}
