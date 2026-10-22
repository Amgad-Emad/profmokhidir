<?php

namespace Tests\Feature\Auth;

use App\Constants\AuthScreens;
use App\Constants\Disks;
use App\Constants\Seo;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The reading-room layout of the signed-out screens: an evergreen panel with
 * the brass glow, the archive portrait (decorative, lazy) and the epigraph; an
 * ivory column with the stacked lockup, the form, the way back and the seal.
 */
class AuthLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_page_is_split_between_the_evergreen_panel_and_the_ivory_column(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $response
            ->assertSee('<title>Sign in · '.Seo::SITE_NAME.'</title>', false)
            ->assertSee('class="auth min-h-screen bg-surface text-ink"', false)
            ->assertSee('class="auth__split"', false)
            ->assertSee('<aside class="auth__panel glow-brass on-dark" aria-label="'.AuthScreens::PANEL_LABEL.'">', false)
            ->assertSee('“'.AuthScreens::EPIGRAPH.'”')
            ->assertSee('class="auth__epigraph mt-3 text-ink"', false)
            ->assertSee('data-variant="horizontal"', false)
            ->assertSee('data-variant="stacked"', false)
            ->assertSee('class="auth__column form-style-underline" data-rise-group', false)
            ->assertSee('<h1 id="page-title" class="t-heading">Sign in</h1>', false)
            ->assertSee(AuthScreens::screen(AuthScreens::LOGIN)['intro'])
            ->assertSee('class="rule-brass mt-6" data-rule', false)
            ->assertSee('id="duotone-archive"', false)
            ->assertSee('<a href="'.route('home').'" class="link-hairline" data-auth-back>'.AuthScreens::BACK_LABEL.'</a>', false)
            ->assertSee('class="seal !mt-4"', false)
            ->assertDontSee('auth-card')
            ->assertDontSee('rounded-card border border-stone bg-surface-sunken', false)
            ->assertDontSee('livewire', false);

        $this->assertSame(1, substr_count($response->getContent(), 'data-variant="stacked"'), 'One stacked lockup, for the ivory column');
        $this->assertSame(1, substr_count($response->getContent(), 'data-variant="horizontal"'), 'One horizontal lockup, for the mobile band');
    }

    #[Test]
    public function the_panel_shows_the_published_portrait_as_a_decorative_lazy_archive_print_with_its_place_and_year(): void
    {
        Storage::fake(Disks::PUBLIC);
        Profile::factory()->published()->withPortrait()->create();

        $response = $this->get(route('login'))->assertOk();

        $response
            ->assertSee('<figure class="auth__portrait" data-auth-portrait>', false)
            ->assertSee('class="photo-archive"', false)
            ->assertSee('<p class="t-label text-accent-text" data-auth-caption>Khartoum, 1998</p>', false);

        $this->assertMatchesRegularExpression('/<img src="[^"]+"[^>]*\salt=""[^>]*\sloading="lazy"/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/data-auth-portrait>\s*<img[^>]*fetchpriority/', $response->getContent(), 'The print is never prioritised');
        $this->assertDoesNotMatchRegularExpression('/data-auth-portrait>\s*<img[^>]*photo-portrait/', $response->getContent(), 'The print uses the duotone, not the warm monochrome');
    }

    #[Test]
    public function without_a_published_portrait_the_panel_holds_the_epigraph_alone(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('data-auth-portrait', false)
            ->assertDontSee('data-auth-caption', false)
            ->assertSee('“'.AuthScreens::EPIGRAPH.'”');
    }

    #[Test]
    public function every_screen_carries_its_title_the_way_back_and_the_seal(): void
    {
        $user = User::factory()->editor()->create();
        $token = Password::broker()->createToken($user);

        $screens = [
            [$this->get(route('password.request')), AuthScreens::FORGOT_PASSWORD],
            [$this->get(route('password.reset', ['token' => $token, 'email' => $user->email])), AuthScreens::RESET_PASSWORD],
            [$this->actingAs(User::factory()->editor()->unverified()->create())->get(route('verification.notice')), AuthScreens::VERIFY_EMAIL],
            [$this->actingAs($user)->get(route('password.confirm')), AuthScreens::CONFIRM_PASSWORD],
        ];

        foreach ($screens as [$response, $screen]) {
            $title = AuthScreens::screen($screen)['title'];

            $response
                ->assertOk()
                ->assertSee('<title>'.$title.' · '.Seo::SITE_NAME.'</title>', false)
                ->assertSee('<h1 id="page-title" class="t-heading">'.$title.'</h1>', false)
                ->assertSee('data-auth-screen="'.$screen.'"', false)
                ->assertSee('data-auth-back>'.AuthScreens::BACK_LABEL.'</a>', false)
                ->assertSee('class="seal !mt-4"', false)
                ->assertSee('glow-brass')
                ->assertDontSee('livewire', false);
        }
    }

    #[Test]
    public function the_two_factor_screen_is_titled_and_closes_the_same_way(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret-password']);
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password'])->assertRedirect(route('two-factor.login'));

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('<title>Two-factor authentication · '.Seo::SITE_NAME.'</title>', false)
            ->assertSee('data-auth-screen="'.AuthScreens::TWO_FACTOR.'"', false)
            ->assertSee(AuthScreens::screen(AuthScreens::TWO_FACTOR)['intro'])
            ->assertSee('data-auth-back>'.AuthScreens::BACK_LABEL.'</a>', false)
            ->assertSee('class="seal !mt-4"', false);
    }

    #[Test]
    public function members_and_staff_share_the_screen_and_still_land_where_they_did(): void
    {
        $editor = User::factory()->editor()->create(['password' => 'secret-password']);
        $member = User::factory()->member()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $editor->email, 'password' => 'secret-password'])->assertRedirect('/admin');
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $member->email, 'password' => 'secret-password'])->assertRedirect(route('community'));
    }
}
