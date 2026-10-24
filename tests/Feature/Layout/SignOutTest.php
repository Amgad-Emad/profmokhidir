<?php

namespace Tests\Feature\Layout;

use App\Constants\AdminNavigation;
use App\Constants\AuthStatus;
use App\Models\ChatChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every layout gives a signed-in user a working sign-out: a POST form to the
 * logout route with a CSRF token (never a link), in the public header menu,
 * the chat header menu, the admin top bar menu and sidebar foot, and at the
 * foot of the account page. Signing out lands on the home page with a notice.
 */
class SignOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function layouts(): array
    {
        return [
            'public header (member on the home page)' => ['member', 'home', 'data-account-menu'],
            'public account page (member)' => ['member', AdminNavigation::ACCOUNT_ROUTE, 'data-account-sign-out'],
            'chat header (member)' => ['member', 'community.chat', 'data-member-menu'],
            'admin top bar (editor)' => ['editor', AdminNavigation::DASHBOARD_ROUTE, 'data-user-menu'],
            'admin sidebar foot (admin)' => ['admin', AdminNavigation::DASHBOARD_ROUTE, 'data-sidebar-sign-out'],
            'admin account page (admin)' => ['admin', AdminNavigation::ACCOUNT_ROUTE, 'data-account-sign-out'],
        ];
    }

    #[Test]
    #[DataProvider('layouts')]
    public function each_layout_shows_a_csrf_protected_sign_out_form(string $role, string $route, string $marker): void
    {
        ChatChannel::factory()->room()->create();
        $user = User::factory()->{$role}()->create();

        $html = $this->actingAs($user)->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString($marker, $html);
        $this->assertMatchesRegularExpression('#<form novalidate method="POST" action="'.preg_quote(route('logout'), '#').'"[^>]*data-sign-out>\s*<input type="hidden" name="_token" value="[^"]+"#', $html);
        $this->assertStringNotContainsString('href="'.route('logout').'"', $html, 'Sign out is never a link');
    }

    #[Test]
    public function the_admin_shell_offers_sign_out_in_the_top_bar_and_the_sidebar(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())->get(route(AdminNavigation::DASHBOARD_ROUTE))->getContent();

        $this->assertSame(2, substr_count($html, 'action="'.route('logout').'"'));
        $this->assertStringContainsString('class="admin-user-menu__item" data-leave-ok>', $html);
        $this->assertStringContainsString('data-sidebar-sign-out="data-sidebar-sign-out" data-sign-out>', $html);
    }

    #[Test]
    public function signing_out_returns_to_the_home_page_with_a_quiet_notice(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', AuthStatus::SIGNED_OUT);

        $this->assertGuest();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-signed-out-notice', false)
            ->assertSee(AuthStatus::message(AuthStatus::SIGNED_OUT));

        $this->get(route('home'))->assertDontSee('data-signed-out-notice', false);
    }

    #[Test]
    public function signing_out_is_post_only(): void
    {
        $this->actingAs(User::factory()->member()->create());

        $this->get('/logout')->assertMethodNotAllowed();
        $this->assertAuthenticated();
    }
}
