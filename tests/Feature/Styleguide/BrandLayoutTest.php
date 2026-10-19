<?php

namespace Tests\Feature\Styleguide;

use App\Constants\Typography;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The layouts load the brand fonts from Google Fonts and define the duotone filter once.
 */
class BrandLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_pages_preload_the_self_hosted_display_font_and_request_no_third_party(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response
            ->assertSee('<link rel="preload" href="'.asset(Typography::path(Typography::PRELOAD_FILE)).'" as="font" type="font/woff2" crossorigin>', false)
            ->assertDontSee('fonts.googleapis.com', false)
            ->assertDontSee('fonts.gstatic.com', false);

        $this->assertSame(1, substr_count($response->getContent(), 'id="duotone-archive"'), 'Duotone filter is defined exactly once');
        $this->assertSame(1, substr_count($response->getContent(), 'rel="preload" href="'.asset(Typography::path(Typography::PRELOAD_FILE))), 'Display font is preloaded once');
    }

    #[Test]
    public function every_font_file_exists_and_is_declared_with_swap_in_the_stylesheet(): void
    {
        $css = file_get_contents(resource_path('css/fonts.css'));

        foreach (Typography::files() as $font) {
            $path = public_path(Typography::path($font['file']));
            $this->assertFileExists($path);
            $this->assertSame('wOF2', file_get_contents($path, false, null, 0, 4), $font['file'].' is WOFF2');
            $this->assertStringContainsString("url('/".Typography::path($font['file'])."') format('woff2')", $css);
            $this->assertStringContainsString("font-family: '".$font['family']."'", $css);
            $this->assertStringContainsString('font-weight: '.$font['weight'].';', $css);
        }

        $this->assertSame(count(Typography::files()), substr_count($css, '@font-face'));
        $this->assertSame(count(Typography::files()), substr_count($css, 'font-display: swap'));
        $this->assertStringContainsString('unicode-range: U+0000-00FF', $css, 'Latin subset only');
    }

    #[Test]
    public function the_home_page_uses_the_motion_attributes_and_no_livewire(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-rise-group', false)
            ->assertSee('data-scroll-scale', false)
            ->assertSee('data-rule', false)
            ->assertSee('glow-brass')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function guest_and_admin_layouts_load_the_same_fonts(): void
    {
        $preload = '<link rel="preload" href="'.asset(Typography::path(Typography::PRELOAD_FILE)).'" as="font" type="font/woff2" crossorigin>';

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($preload, false);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($preload, false)
            ->assertSee('id="duotone-archive"', false);
    }
}
