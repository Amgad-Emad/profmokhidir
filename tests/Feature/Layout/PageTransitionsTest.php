<?php

namespace Tests\Feature\Layout;

use App\Constants\Motion;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The soft page transitions, the drawn hairlines and the slow button fills live
 * in resources/css/wayfinding.css; this checks their timing against the Motion
 * constants and that they switch off under reduced motion.
 */
class PageTransitionsTest extends TestCase
{
    private string $css;

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(resource_path('css/wayfinding.css'));
    }

    #[Test]
    public function pages_cross_fade_over_400ms_only_when_motion_is_not_reduced(): void
    {
        $this->assertStringContainsString('--duration-page: '.Motion::PAGE_TRANSITION_MS.'ms;', $this->css);
        $this->assertStringContainsString("@import './wayfinding.css';", file_get_contents(resource_path('css/app.css')));

        preg_match('/@media \(prefers-reduced-motion: no-preference\) \{(.*?)\n\}/s', $this->css, $block);
        $this->assertStringContainsString('@view-transition {', $block[1]);
        $this->assertStringContainsString('navigation: auto;', $block[1]);
        $this->assertMatchesRegularExpression('/::view-transition-old\(root\),\s*::view-transition-new\(root\) \{\s*animation-duration: var\(--duration-page\);\s*animation-timing-function: var\(--ease-rise\);/', $block[1]);

        $this->assertSame(1, substr_count($this->css, '@view-transition'), 'The opt-in exists only inside the reduced-motion guard');
    }

    #[Test]
    public function the_header_and_its_mark_stay_in_place_as_their_own_transition_group(): void
    {
        $this->assertStringContainsString('.site-header {'."\n".'    view-transition-name: '.Motion::PAGE_TRANSITION_HEADER_NAME.';', $this->css);
        $this->assertMatchesRegularExpression('/::view-transition-old\(site-header\),\s*::view-transition-new\(site-header\) \{\s*animation: none;/', $this->css);
    }

    #[Test]
    public function links_draw_a_hairline_from_the_left_and_buttons_change_colour_slowly(): void
    {
        $this->assertMatchesRegularExpression('/\.link-draw::after \{[^}]*height: 1px;[^}]*transform: scaleX\(0\);\s*transform-origin: left center;\s*transition: transform var\(--duration-draw\) var\(--ease-rise\);/s', $this->css);
        $this->assertMatchesRegularExpression('/\.link-draw:hover::after,\s*\.link-draw:focus-visible::after \{\s*transform: scaleX\(1\);/', $this->css);
        $this->assertStringContainsString('--duration-draw: '.Motion::LINK_DRAW_MS.'ms;', $this->css);

        $app = file_get_contents(resource_path('css/app.css'));
        $buttons = file_get_contents(resource_path('css/buttons.css'));
        // Buttons change colour slowly (400ms on the brand easing) and never move or wipe.
        $this->assertMatchesRegularExpression('/\.btn \{[^}]*transition:\s*background-color 400ms var\(--ease-rise\),\s*border-color 400ms var\(--ease-rise\),\s*color 400ms var\(--ease-rise\)/s', $buttons);
        $this->assertStringNotContainsString('scaleX', $buttons);
        // The solid pill follows the --fill token, which .on-dark remaps to ivory with evergreen ink.
        $this->assertMatchesRegularExpression('/--btn-primary-bg: var\(--fill\);\s*--btn-primary-ink: var\(--fill-ink\);/s', file_get_contents(resource_path('css/tokens.css')));
        $this->assertMatchesRegularExpression('/\.on-dark \{[^}]*--fill: var\(--band-fill\);\s*--fill-ink: var\(--band-fill-ink\);/s', file_get_contents(resource_path('css/tokens.css')));
        $this->assertStringNotContainsString('linear-gradient', $app.$this->css, 'Underlines are pseudo-elements, not gradients');
    }

    #[Test]
    public function focus_rings_remain_visible_on_both_surfaces(): void
    {
        $app = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/:focus-visible \{\s*outline: 2px solid var\(--focus\);\s*outline-offset: 2px;/', $app);
        // --focus is bronze on ivory, brass on the light-theme band and in the dark theme, champagne on cypress.
        $tokens = file_get_contents(resource_path('css/tokens.css'));
        $this->assertStringContainsString('--focus: light-dark(var(--brand-bronze), var(--brand-brass));', $tokens);
        $this->assertMatchesRegularExpression('/\.on-dark \{[^}]*--focus: var\(--band-accent\);/s', $tokens);
        $this->assertStringContainsString('--band-accent: light-dark(var(--brand-brass), var(--brand-champagne));', $tokens);
        // Text fields (the search fields among them) draw no ring: their own line shows focus (tests/Unit/Design/FocusRulesTest).
        $forms = file_get_contents(resource_path('css/forms.css'));
        $this->assertStringNotContainsString('outline: 2px solid var(--focus)', $forms);
        $this->assertStringContainsString('--form-line: var(--field-focus-line);', $forms);
    }
}
