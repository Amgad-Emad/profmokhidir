<?php

namespace Tests\Feature\Auth;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\VerifyEmailResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_fortify_responses_are_ours(): void
    {
        $this->assertInstanceOf(LoginResponse::class, app(LoginResponseContract::class));
        $this->assertInstanceOf(VerifyEmailResponse::class, app(VerifyEmailResponseContract::class));
    }

    #[Test]
    public function members_and_applicants_land_on_the_community_page_after_signing_in(): void
    {
        foreach ([User::factory()->member(), User::factory()->applicant(), User::factory()->rejectedApplicant()] as $factory) {
            $user = $factory->create();

            $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('community'));

            auth()->logout();
        }
    }

    #[Test]
    public function staff_still_land_on_the_dashboard(): void
    {
        $editor = User::factory()->editor()->create();

        $this->post(route('login'), ['email' => $editor->email, 'password' => 'password'])->assertRedirect('/admin');
    }

    #[Test]
    public function signed_in_users_opening_the_sign_in_page_are_sent_where_they_belong(): void
    {
        $this->actingAs(User::factory()->member()->create())->get(route('login'))->assertRedirect(route('community'));
        $this->actingAs(User::factory()->editor()->create())->get(route('login'))->assertRedirect('/admin');
    }
}
