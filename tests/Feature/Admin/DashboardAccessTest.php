<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function admins_and_editors_can_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('livewire', false);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function members_are_forbidden(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function unverified_staff_must_verify_their_email_first(): void
    {
        $this->actingAs(User::factory()->admin()->unverified()->create())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function the_public_home_page_does_not_load_livewire(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('livewire', false);
    }
}
