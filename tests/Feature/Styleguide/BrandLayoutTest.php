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
    public function public_pages_preconnect_and_load_google_fonts_with_swap(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response
            ->assertSee('<link rel="preconnect" href="https://fonts.googleapis.com">', false)
            ->assertSee('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>', false)
            ->assertSee('<link rel="stylesheet" href="'.e(Typography::GOOGLE_FONTS_URL).'">', false)
            ->assertSee('display=swap', false)
            ->assertSee('Cormorant+Garamond:ital,wght@0,300;1,300', false)
            ->assertSee('Hanken+Grotesk:wght@300;400;500', false)
            ->assertSee('JetBrains+Mono', false);

        $this->assertSame(1, substr_count($response->getContent(), 'id="duotone-archive"'), 'Duotone filter is defined exactly once');
        $this->assertSame(1, substr_count($response->getContent(), 'fonts.googleapis.com/css2'), 'Font stylesheet is requested once');
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
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(e(Typography::GOOGLE_FONTS_URL), false);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(e(Typography::GOOGLE_FONTS_URL), false)
            ->assertSee('id="duotone-archive"', false);
    }
}
