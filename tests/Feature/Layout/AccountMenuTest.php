<?php

namespace Tests\Feature\Layout;

use App\Constants\AdminNavigation;
use App\Constants\Masthead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The public masthead's account menu: a quiet Sign in link for visitors; for a
 * signed-in user the initials, name and a menu with Your account, Community
 * chat (members), Dashboard (staff), the theme toggle and Sign out.
 */
class AccountMenuTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function visitors_see_a_quiet_sign_in_link_and_no_menu(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<a href="'.route('login').'" class="site-account-signin link-draw t-label hidden sm:inline-flex" data-sign-in-link>'.Masthead::SIGN_IN_SHORT.'</a>', false)
            ->assertDontSee('data-account-menu', false)
            ->assertDontSee('data-sign-out', false);
    }

    #[Test]
    public function a_member_gets_the_menu_with_the_chat_but_not_the_dashboard(): void
    {
        $member = User::factory()->member()->create(['name' => 'Amina Osman']);

        $this->actingAs($member)->get(route('about'))
            ->assertOk()
            ->assertSee('data-account-menu', false)
            ->assertSee('aria-controls="'.Masthead::ACCOUNT_MENU_ID.'"', false)
            ->assertSee('<span class="site-account__avatar" aria-hidden="true">AO</span>', false)
            ->assertSee('Amina Osman')
            ->assertSee(Masthead::SIGNED_IN_AS.' Amina Osman')
            ->assertSee('data-account-item="account"', false)
            ->assertSee('href="'.route('community.chat').'" class="site-account__item" data-account-item="chat"', false)
            ->assertDontSee('data-account-item="dashboard"', false)
            ->assertSee('id="theme-switch-account-menu"', false)
            ->assertSee('class="site-account__item" data-leave-ok>', false)
            ->assertDontSee('data-sign-in-link', false);
    }

    #[Test]
    public function staff_get_the_dashboard_in_the_menu(): void
    {
        $this->actingAs(User::factory()->editor()->create())->get(route('home'))
            ->assertSee('href="'.route(AdminNavigation::DASHBOARD_ROUTE).'" class="site-account__item" data-account-item="dashboard"', false)
            ->assertDontSee('data-account-item="chat"', false);
    }

    #[Test]
    public function a_pending_applicant_gets_only_the_account_and_sign_out(): void
    {
        $this->actingAs(User::factory()->applicant()->create())->get(route('home'))
            ->assertSee('data-account-item="account"', false)
            ->assertDontSee('data-account-item="chat"', false)
            ->assertDontSee('data-account-item="dashboard"', false)
            ->assertSee('data-sign-out', false);
    }
}
