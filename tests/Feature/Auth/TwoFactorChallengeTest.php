<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_admin_with_two_factor_must_pass_the_challenge_before_being_signed_in(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();

        $code = app(Google2FA::class)->getCurrentOtp(decrypt($admin->two_factor_secret));

        $this->post(route('two-factor.login'), ['code' => $code])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk();
    }

    #[Test]
    public function a_wrong_code_is_rejected(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password']);

        $this->from(route('two-factor.login'))
            ->post(route('two-factor.login'), ['code' => '000000'])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    #[Test]
    public function a_recovery_code_works_once(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret-password']);
        $code = $admin->recoveryCodes()[0];

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password']);
        $this->post(route('two-factor.login'), ['recovery_code' => $code])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);

        $this->assertNotContains($code, $admin->fresh()->recoveryCodes());
    }

    #[Test]
    public function the_two_factor_challenge_is_rate_limited(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.login'), ['code' => '000000']);
        }

        $this->post(route('two-factor.login'), ['code' => '000000'])->assertStatus(429);
    }

    #[Test]
    public function editors_without_two_factor_sign_in_directly(): void
    {
        $editor = User::factory()->editor()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $editor->email, 'password' => 'secret-password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($editor);
    }
}
