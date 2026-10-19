<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_login_page_renders_our_blade_view(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Sign in')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function staff_can_log_in_and_land_on_the_admin_dashboard(): void
    {
        $user = User::factory()->editor()->create(['password' => 'secret-password']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    #[Test]
    public function password_reset_and_email_verification_pages_exist(): void
    {
        $this->get(route('password.request'))->assertOk()->assertViewIs('auth.forgot-password');

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertViewIs('auth.verify-email');
    }
}
