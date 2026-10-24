<?php

namespace Tests\Feature\Auth;

use App\Constants\AdminNavigation;
use App\Constants\AuthStatus;
use App\Constants\Roles;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivatedAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_deactivated_account_cannot_sign_in(): void
    {
        $editor = User::factory()->editor()->deactivated()->create();

        $this->from(route('login'))
            ->post(route('login'), ['email' => $editor->email, 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => AuthStatus::message(AuthStatus::ACCOUNT_DEACTIVATED)]);

        $this->assertGuest();

        // Wrong passwords still get the generic message, so the status of an account is not revealed.
        $this->from(route('login'))
            ->post(route('login'), ['email' => $editor->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertStringNotContainsString('deactivated', (string) session('errors')?->first('email'));
    }

    #[Test]
    public function a_signed_in_user_is_signed_out_on_the_next_request_after_deactivation_including_livewire_updates(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertOk();
        app(UserService::class)->deactivate($editor, $admin);

        $this->actingAs($editor->fresh())->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', AuthStatus::ACCOUNT_DEACTIVATED);
        $this->assertGuest();

        $this->get(route('login'))->assertOk()->assertSee(AuthStatus::message(AuthStatus::ACCOUNT_DEACTIVATED));

        // Livewire re-applies the middleware on every component update.
        $this->assertContains(EnsureAccountIsActive::class, Livewire::getPersistentMiddleware());
    }

    #[Test]
    public function an_invited_admin_sets_a_password_from_the_link_gets_verified_and_is_sent_to_two_factor_setup(): void
    {
        Notification::fake();
        $root = User::factory()->admin()->create();
        $invited = app(UserService::class)->invite(['name' => 'New Admin', 'email' => 'new@example.com', 'role' => Roles::ADMIN], $root);
        $token = Password::broker()->createToken($invited);

        $this->get(route('password.reset', ['token' => $token, 'email' => $invited->email]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $invited->email,
            'password' => 'a-long-new-password-1',
            'password_confirmation' => 'a-long-new-password-1',
        ])->assertRedirect(route('login'));

        $this->assertNotNull($invited->fresh()->email_verified_at);
        $this->assertFalse(Password::broker()->tokenExists($invited, $token));

        $this->post(route('login'), ['email' => $invited->email, 'password' => 'a-long-new-password-1'])->assertRedirect();
        $this->assertAuthenticatedAs($invited);

        // Two-factor is recommended, not required: the new admin lands on the dashboard, which suggests it.
        $this->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-two-factor-card', false);
    }
}
