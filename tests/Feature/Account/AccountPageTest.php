<?php

namespace Tests\Feature\Account;

use App\Constants\AdminNavigation;
use App\Constants\AuthStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_and_unverified_users_cannot_open_the_account_page(): void
    {
        $this->get(route(AdminNavigation::ACCOUNT_ROUTE))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function staff_see_the_page_inside_the_admin_layout_and_members_inside_the_public_one(): void
    {
        $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertViewIs('account.show')
            ->assertSee('Your account')
            ->assertSee('id="admin-sidebar"', false)
            ->assertSee('admin-nav-link--active" aria-current="page" data-nav-item="account"', false)
            ->assertSee(route('user-profile-information.update'))
            ->assertSee(route('user-password.update'));

        $this->actingAs(User::factory()->member()->create())
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertDontSee('id="admin-sidebar"', false)
            ->assertDontSee('livewire', false)
            ->assertSee('Your account');
    }

    #[Test]
    public function the_user_can_change_their_name_and_email(): void
    {
        Notification::fake();
        $user = User::factory()->editor()->create();

        $this->actingAs($user)
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->put(route('user-profile-information.update'), ['name' => '  New Name ', 'email' => 'New@Example.com'])
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertSessionHas('status', AuthStatus::PROFILE_UPDATED);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function keeping_the_same_email_does_not_require_reverification(): void
    {
        Notification::fake();
        $user = User::factory()->editor()->create();

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), ['name' => 'Renamed', 'email' => $user->email]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('Renamed', $user->fresh()->name);
        Notification::assertNothingSent();
    }

    #[Test]
    public function profile_validation_errors_are_shown_on_the_account_page(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->editor()->create();

        $this->actingAs($user)
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->put(route('user-profile-information.update'), ['name' => '', 'email' => $other->email])
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertSessionHasErrorsIn('updateProfileInformation', ['name', 'email']);

        // Follow the redirect in one go: the errors render next to their fields with an icon.
        $this->actingAs($user)
            ->followingRedirects()
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->put(route('user-profile-information.update'), ['name' => '', 'email' => $other->email])
            ->assertOk()
            ->assertSee('role="alert"', false)
            ->assertSee('Enter name.')
            ->assertSee('That email is already in use. Choose another.');
    }

    #[Test]
    public function the_user_can_change_their_password_with_the_current_one(): void
    {
        $user = User::factory()->editor()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->put(route('user-password.update'), [
                'current_password' => 'wrong',
                'password' => 'a-brand-new-passphrase',
                'password_confirmation' => 'a-brand-new-passphrase',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['current_password']);

        $this->actingAs($user)
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->put(route('user-password.update'), [
                'current_password' => 'old-password',
                'password' => 'a-brand-new-passphrase',
                'password_confirmation' => 'a-brand-new-passphrase',
            ])
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertSessionHas('status', AuthStatus::PASSWORD_UPDATED);

        $this->assertTrue(Hash::check('a-brand-new-passphrase', $user->fresh()->password));
    }

    #[Test]
    public function two_factor_can_be_enabled_confirmed_and_disabled_from_the_account_page(): void
    {
        $user = User::factory()->editor()->withoutTwoFactor()->create();

        $this->actingAs($user)->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertSee('data-two-factor="off"', false)
            ->assertSee('Enable two-factor authentication');

        // Enabling needs a recently confirmed password.
        $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect(route('password.confirm'));

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->post(route('two-factor.enable'))
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertSessionHas('status', AuthStatus::TWO_FACTOR_ENABLED);

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('data-two-factor="pending"', false)
            ->assertSee('<svg', false)
            ->assertSee('data-setup-key', false)
            ->assertSee(route('two-factor.confirm'))
            ->assertSee('Cancel setup');

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', ['code']);

        $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertSessionHas('status', AuthStatus::TWO_FACTOR_CONFIRMED);

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('data-two-factor="confirmed"', false)
            ->assertSee('data-recovery-codes', false)
            ->assertSee($user->fresh()->recoveryCodes()[0])
            ->assertSee(route('two-factor.recovery-codes'))
            ->assertSee('Turn off two-factor authentication');

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->from(route(AdminNavigation::ACCOUNT_ROUTE))
            ->delete(route('two-factor.disable'))
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertSessionHas('status', AuthStatus::TWO_FACTOR_DISABLED);

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    #[Test]
    public function an_admin_can_turn_on_two_factor_and_the_dashboard_card_goes_away(): void
    {
        $admin = User::factory()->admin()->withoutTwoFactor()->create();

        $this->actingAs($admin)->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertOk()->assertSee('data-two-factor-card', false);

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post(route('two-factor.enable'));

        $code = app(Google2FA::class)->getCurrentOtp(decrypt($admin->fresh()->two_factor_secret));
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post(route('two-factor.confirm'), ['code' => $code]);

        $this->actingAs($admin->fresh())->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertOk()->assertDontSee('data-two-factor-card', false);
    }
}
