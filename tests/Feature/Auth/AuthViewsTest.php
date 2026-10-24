<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every Fortify screen renders our Blade view inside the guest layout: the
 * evergreen panel with the epigraph, the ivory column with the stacked lockup,
 * the solid primary pill, label-style labels and the seal beneath.
 */
class AuthViewsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_login_page_uses_the_branded_guest_layout(): void
    {
        $response = $this->get(route('login'))->assertOk()->assertViewIs('auth.login');

        $response
            ->assertSee('class="auth min-h-screen bg-surface text-ink"', false)
            ->assertSee('data-variant="stacked"', false)
            ->assertSee('Mohamed Osman Khidir')
            ->assertSee('class="auth__column form-style-underline" data-rise-group', false)
            ->assertSee('btn btn--primary w-full', false)
            ->assertSee('class="seal !mt-4"', false)
            ->assertSee('<h1 id="page-title" class="t-heading">Sign in</h1>', false)
            ->assertSee('t-label form-label', false)
            ->assertSee('Keep me signed in')
            ->assertSee(route('password.request'))
            ->assertSee('glow-brass')
            ->assertDontSee('auth-card')
            ->assertDontSee('btn--secondary', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function forgot_and_reset_password_pages_render(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertViewIs('auth.forgot-password')
            ->assertSee('Email me a reset link')
            ->assertSee('data-variant="stacked"', false);

        $user = User::factory()->editor()->create();
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertSee('Choose a new password')
            ->assertSee('value="'.$user->email.'"', false);
    }

    #[Test]
    public function a_reset_password_is_saved_through_the_user_service(): void
    {
        $user = User::factory()->editor()->create();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-brand-new-passphrase',
            'password_confirmation' => 'a-brand-new-passphrase',
        ])->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => $user->email, 'password' => 'a-brand-new-passphrase'])
            ->assertRedirect('/admin');
    }

    #[Test]
    public function email_verification_and_password_confirmation_pages_render(): void
    {
        $this->actingAs(User::factory()->editor()->unverified()->create())
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertViewIs('auth.verify-email')
            ->assertSee('Resend verification email');

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertViewIs('auth.confirm-password')
            ->assertSee('Confirm your password');
    }

    #[Test]
    public function the_two_factor_challenge_page_renders_both_code_and_recovery_forms(): void
    {
        $admin = User::factory()->admin()->withTwoFactor()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertRedirect(route('two-factor.login'));

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertViewIs('auth.two-factor-challenge')
            ->assertSee('Authentication code')
            ->assertSee('Recovery code')
            ->assertSee('Use a recovery code')
            ->assertSee('data-variant="stacked"', false);
    }

    #[Test]
    public function public_registration_stays_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }
}
