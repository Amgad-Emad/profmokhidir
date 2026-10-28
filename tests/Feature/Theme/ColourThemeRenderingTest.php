<?php

namespace Tests\Feature\Theme;

use App\Constants\Appearance;
use App\Models\ColourTheme;
use App\Models\User;
use App\Services\ColourThemeService;
use App\Support\Colour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * How the active colour theme reaches every page: one cached <style> block in
 * <head> on the public site, the sign-in screens, the dashboard and the chat;
 * nothing at all while the brand applies; the browser chrome colour, the
 * archive duotone and the emails follow it; a draft is previewed on the live
 * site only by the admin who opened the signed link.
 */
class ColourThemeRenderingTest extends TestCase
{
    use RefreshDatabase;

    private const string OXFORD_SURFACE = '--surface:light-dark(#f4efe3,#0f1a30)';

    private function publish(ColourTheme $theme): void
    {
        app(ColourThemeService::class)->publish($theme, User::factory()->admin()->create());
    }

    #[Test]
    public function with_no_theme_the_site_renders_with_the_brand_defaults_and_no_style_block(): void
    {
        $this->assertSame(0, ColourTheme::query()->count());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-colour-theme', false)
            ->assertSee('content="#13221C"', false);
    }

    #[Test]
    public function the_brand_theme_adds_nothing_to_the_page_either(): void
    {
        app(ColourThemeService::class)->restoreBrand(null);

        $this->get('/')->assertOk()->assertDontSee('data-colour-theme', false);
    }

    #[Test]
    public function an_active_theme_is_printed_once_in_the_head_of_every_layout(): void
    {
        $this->publish(ColourTheme::factory()->create());

        foreach (['/', '/about', '/publications', '/tributes/new', '/login'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, self::OXFORD_SURFACE), $path);
            $this->assertLessThan(strpos($html, '</head>'), strpos($html, self::OXFORD_SURFACE), "{$path}: in <head>");
        }

        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()->assertSee(self::OXFORD_SURFACE, false);
        $this->actingAs(User::factory()->member()->create())->get('/community/chat')->assertOk()->assertSee(self::OXFORD_SURFACE, false);
    }

    #[Test]
    public function the_browser_chrome_colour_and_the_duotone_follow_the_theme(): void
    {
        $this->publish(ColourTheme::factory()->create());

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-light="#14213D"', $html, 'the masthead colour of the light mode');
        $navy = Colour::parse('#14213d');
        $this->assertStringContainsString(sprintf('tableValues="%.4f %.4f"', $navy[0], Colour::parse('#f4efe3')[0]), $html);
        $this->assertStringContainsString('id="duotone-archive-dark"', $html);
    }

    #[Test]
    public function saving_a_theme_clears_the_cached_block(): void
    {
        $theme = ColourTheme::factory()->create();
        $this->publish($theme);
        $this->get('/')->assertSee(self::OXFORD_SURFACE, false);

        app(ColourThemeService::class)->saveDraft($theme, [...$theme->light, 'surface' => '#f7f3ea'], $theme->dark, null);
        $this->get('/')->assertSee(self::OXFORD_SURFACE, false);

        $this->publish($theme->refresh());
        $this->get('/')->assertSee('--surface:light-dark(#f7f3ea,#0f1a30)', false)->assertDontSee(self::OXFORD_SURFACE, false);
    }

    #[Test]
    public function a_draft_is_previewed_on_the_site_only_by_the_admin_who_opened_the_signed_link(): void
    {
        $admin = User::factory()->admin()->create();
        $theme = ColourTheme::factory()->withDraft()->create();
        $url = app(ColourThemeService::class)->previewUrl($theme);

        $this->actingAs($admin)->get(route(Appearance::PREVIEW_ROUTE, ['theme' => $theme->id]))->assertForbidden();
        $this->actingAs(User::factory()->editor()->create())->get($url)->assertForbidden();

        $this->actingAs($admin)->get($url)->assertRedirect(route('home'));
        $this->actingAs($admin)->get('/')
            ->assertSee('--surface:light-dark(#f6efe0,#0f1a30)', false)
            ->assertSee('data-colour-theme-preview', false)
            ->assertSee('data-colour-preview-banner', false);

        // Visitors and other staff keep the live colours.
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get('/')->assertDontSee('#f6efe0', false)->assertDontSee('data-colour-preview-banner', false);

        $this->actingAs($admin)->withSession([Appearance::SESSION_PREVIEW => $theme->id])
            ->get(route(Appearance::PREVIEW_END_ROUTE, ['to' => '/about']))
            ->assertRedirect('/about')
            ->assertSessionMissing(Appearance::SESSION_PREVIEW);

        $this->get(route(Appearance::PREVIEW_END_ROUTE, ['to' => '//evil.example']))->assertRedirect(route('home'));
    }

    #[Test]
    public function expired_or_tampered_preview_links_are_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $theme = ColourTheme::factory()->withDraft()->create();
        $other = ColourTheme::factory()->create();
        $expired = URL::temporarySignedRoute(Appearance::PREVIEW_ROUTE, now()->subMinute(), ['theme' => $theme->id]);

        $this->actingAs($admin)->get($expired)->assertForbidden();
        $this->actingAs($admin)->get(str_replace('/preview/'.$theme->id, '/preview/'.$other->id, app(ColourThemeService::class)->previewUrl($theme)))->assertForbidden();
    }

    #[Test]
    public function emails_take_the_accent_and_button_of_the_active_theme(): void
    {
        $brandHtml = (string) (new MailMessage)->line('Hello')->action('Open', url('/'))->render();
        $this->assertStringContainsString('#6f5a34', $brandHtml, 'the brand accent (bronze links) by default');

        $this->publish(ColourTheme::factory()->create());
        $palette = app(ColourThemeService::class)->activePalette()['light'];
        $html = (string) (new MailMessage)->line('Hello')->action('Open', url('/'))->render();

        $this->assertStringContainsString($palette['accent-text'], $html);
        $this->assertStringContainsString('background-color: '.$palette['fill'], $html);
        $this->assertStringNotContainsString('#18181b', $html, 'none of the framework default colours remain');
    }
}
