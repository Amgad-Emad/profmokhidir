<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sixth_attempt_in_a_minute_is_throttled(): void
    {
        $user = User::factory()->editor()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('email');
        }

        $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(429);

        $this->assertGuest();
    }

    #[Test]
    public function the_throttle_is_per_email_and_ip(): void
    {
        $user = User::factory()->editor()->create(['password' => 'secret-password']);
        $other = User::factory()->editor()->create(['password' => 'secret-password']);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login'), ['email' => $other->email, 'password' => 'secret-password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($other);
    }
}
