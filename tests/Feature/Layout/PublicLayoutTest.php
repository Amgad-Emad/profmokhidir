<?php

namespace Tests\Feature\Layout;

use App\Constants\Brand;
use App\Constants\BrandAssets;
use App\Constants\BrandMark;
use App\View\Components\Site\Footer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicLayoutTest extends TestCase
{
    use RefreshDatabase;

    private string $html;

    protected function setUp(): void
    {
        parent::setUp();

        $this->html = $this->get(route('about'))->assertOk()->getContent();
    }

    #[Test]
    public function the_skip_link_is_the_first_focusable_element_and_targets_main(): void
    {
        preg_match('/<body[^>]*>(.*?)<\/body>/s', $this->html, $body);
        preg_match('/<(a|button|input|select|textarea)\b[^>]*>/', $body[1], $first);

        $this->assertStringContainsString('href="#main"', $first[0]);
        $this->assertStringContainsString('skip-link', $first[0]);
        $this->assertStringContainsString('Skip to content', $this->html);
        $this->assertMatchesRegularExpression('/<main id="main" tabindex="-1"/', $this->html);
    }

    #[Test]
    public function the_header_is_fixed_glassy_evergreen_with_the_horizontal_lockup(): void
    {
        $this->assertMatchesRegularExpression('/<header class="site-header fixed inset-x-0 top-0 z-40"/', $this->html);
        $this->assertStringContainsString('glass-header on-dark text-ink', $this->html);

        preg_match('/<header.*?<nav/s', $this->html, $beforeNav);
        $this->assertStringContainsString('data-variant="horizontal"', $beforeNav[0]);
        $this->assertStringContainsString('href="'.route('home').'"', $beforeNav[0]);
        $this->assertStringContainsString('Mohamed Osman Khidir', $beforeNav[0]);
    }

    #[Test]
    public function the_masthead_lists_the_five_chapters_and_the_footer_every_section(): void
    {
        preg_match('/<nav class="site-chapters hidden lg:block" aria-label="Primary"[^>]*>(.*?)<\/nav>/s', $this->html, $nav);
        preg_match_all('/class="chapter-link t-label"[^>]*>([A-Za-z]+)<\/button>/', $nav[1], $buttons);

        $this->assertSame(['Life', 'Work', 'Archive', 'Community', 'Tributes'], $buttons[1]);
        $this->assertStringNotContainsString('More', $nav[1]);
    }

    #[Test]
    public function the_footer_has_the_stacked_lockup_closing_line_links_domain_and_copyright(): void
    {
        preg_match('/<footer.*<\/footer>/s', $this->html, $footer);
        $footer = $footer[0];

        $this->assertStringContainsString('bg-surface-footer text-ink', $footer);
        $this->assertStringContainsString('data-variant="stacked"', $footer);
        $this->assertStringContainsString('<p class="t-quote max-w-xl">'.Footer::CLOSING_LINE.'</p>', $footer);
        $this->assertStringContainsString('<p class="t-label text-accent-text">'.Footer::DOMAIN.'</p>', $footer);
        $this->assertStringContainsString('© '.date('Y'), $footer);

        preg_match_all('/class="footer-link"[^>]*>([^<]+)<\/a>/', $footer, $links);
        $this->assertCount(11, $links[1]);
        $this->assertContains('Other Information', $links[1]);

        preg_match('/<body[^>]*>(.*?)<\/body>/s', $this->html, $body);

        foreach (['facebook', 'twitter', 'x.com', 'linkedin', 'instagram', 'gtag', 'analytics', 'fbq('] as $widget) {
            $this->assertStringNotContainsStringIgnoringCase($widget, $body[1], "No {$widget} widget or tracker");
        }
    }

    #[Test]
    public function the_layout_links_the_favicon_set_and_theme_colour(): void
    {
        $this->assertStringContainsString('<link rel="icon" href="'.asset('favicon.ico').'" sizes="48x48">', $this->html);
        $this->assertStringContainsString('<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml">', $this->html);
        $this->assertStringContainsString('<link rel="icon" href="'.asset('favicon-32x32.png').'" sizes="32x32" type="image/png">', $this->html);
        $this->assertStringContainsString('<link rel="icon" href="'.asset('favicon-16x16.png').'" sizes="16x16" type="image/png">', $this->html);
        $this->assertStringContainsString('<link rel="mask-icon" href="'.asset('safari-pinned-tab.svg').'" color="'.Brand::EVERGREEN.'">', $this->html);
        $this->assertStringContainsString('<link rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'" sizes="180x180">', $this->html);
        $this->assertStringContainsString('<link rel="manifest" href="'.asset('site.webmanifest').'">', $this->html);
        // One theme-color per scheme (evergreen chrome in light, cypress in dark), from <x-theme.head>.
        $this->assertSame(2, substr_count($this->html, 'name="theme-color"'));
        $this->assertStringContainsString('<meta name="theme-color" content="'.Brand::EVERGREEN.'" media="(prefers-color-scheme: light)"', $this->html);
        $this->assertStringContainsString('<meta name="theme-color" content="'.Brand::CYPRESS.'" media="(prefers-color-scheme: dark)"', $this->html);
    }

    #[Test]
    public function the_favicon_files_exist_and_are_built_from_the_mark(): void
    {
        $svg = file_get_contents(public_path('favicon.svg'));
        $this->assertSame(file_get_contents(public_path(BrandAssets::ICON)), $svg, 'favicon.svg is the evergreen app icon');
        $this->assertStringContainsString('fill="'.Brand::EVERGREEN.'"', $svg);
        $this->assertStringContainsString('role="img"', $svg);

        foreach (['favicon-16x16.png' => 16, 'favicon-32x32.png' => 32, 'favicon-48x48.png' => 48, 'favicon-96x96.png' => 96, 'apple-touch-icon.png' => 180, 'android-chrome-192x192.png' => 192, 'android-chrome-512x512.png' => 512, 'maskable-icon-192x192.png' => 192, 'maskable-icon-512x512.png' => 512] as $file => $size) {
            [$width, $height, $type] = getimagesize(public_path($file));
            $this->assertSame([$size, $size, IMAGETYPE_PNG], [$width, $height, $type], $file);
        }

        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(Brand::EVERGREEN, $manifest['theme_color']);
        $this->assertSame(Brand::IVORY, $manifest['background_color']);
        $this->assertSame(BrandMark::TITLE, $manifest['name']);
        $this->assertSame(['/android-chrome-192x192.png', '/android-chrome-512x512.png', '/maskable-icon-192x192.png', '/maskable-icon-512x512.png'], array_column($manifest['icons'], 'src'));

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    #[Test]
    public function guest_and_admin_layouts_share_the_favicon_set(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('<link rel="manifest"', false);
    }
}
