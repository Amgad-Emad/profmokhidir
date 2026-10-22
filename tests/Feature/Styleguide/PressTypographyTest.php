<?php

namespace Tests\Feature\Styleguide;

use App\Constants\Environments;
use App\Constants\Ornaments;
use App\Constants\Typography;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The press typography layer: every component on the local style guide, the
 * stylesheet wiring, and tabular figures in the admin.
 */
class PressTypographyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_style_guide_shows_every_press_component(): void
    {
        $this->app['env'] = Environments::LOCAL;

        $response = $this->get(route('styleguide'))->assertOk();

        $response->assertSee('id="press"', false)
            ->assertSee('class="chapter-opening"', false)
            ->assertSee('class="epigraph', false)
            ->assertSee('class="drop-cap prose-bio"', false)
            ->assertSee('class="sidenote-host', false)
            ->assertSee('class="sidenote__ref"', false)
            ->assertSee('class="folio folio--top"', false)
            ->assertSee('class="folio folio--bottom', false)
            ->assertSee('class="seal"', false)
            ->assertSee('<span class="caps">FAO</span>', false)
            ->assertSee('1975–1992');

        foreach (Ornaments::types() as $type) {
            $response->assertSee('data-ornament="'.$type.'"', false);
        }
    }

    #[Test]
    public function the_stylesheet_imports_the_typography_layer_and_defines_the_measure_and_rhythm(): void
    {
        $app = file_get_contents(resource_path('css/app.css'));
        $typography = file_get_contents(resource_path('css/typography.css'));

        $this->assertStringContainsString("@import './typography.css';", $app);
        $this->assertStringContainsString('--container-measure: 33rem;', $app);
        $this->assertStringContainsString('--baseline: 0.5rem;', $typography);
        $this->assertStringContainsString('font-synthesis-small-caps: none;', $typography);
        $this->assertStringContainsString('font-variant-numeric: oldstyle-nums proportional-nums;', $typography);
        $this->assertStringContainsString('font-variant-numeric: lining-nums tabular-nums;', $typography);
        $this->assertStringContainsString('text-wrap: pretty;', $typography);
        $this->assertStringContainsString('text-wrap: balance;', $typography);
        $this->assertStringContainsString('hanging-punctuation: first last;', $typography);
    }

    #[Test]
    public function the_display_font_files_are_the_full_feature_builds(): void
    {
        // The Google Fonts builds lack small caps; the upstream Cormorant subsets that carry smcp/c2sc/onum/dlig are larger.
        $this->assertGreaterThan(30000, filesize(public_path(Typography::path(Typography::FILE_DISPLAY))));
        $this->assertGreaterThan(28000, filesize(public_path(Typography::path(Typography::FILE_DISPLAY_ITALIC))));
    }

    #[Test]
    public function the_admin_sets_figures_in_tabular_form(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<body class="min-h-screen bg-surface-sunken text-ink tabular-nums">', false);
    }
}
