<?php

namespace Tests\Feature\Layout;

use App\Constants\Motion;
use App\Constants\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The refined header: it condenses on scroll, the active section carries the
 * brass rule, the search button and dialog are accessible, and the full-screen
 * menu lists the sections as a table of contents with Roman numerals.
 */
class HeaderWayfindingTest extends TestCase
{
    use RefreshDatabase;

    private string $html;

    protected function setUp(): void
    {
        parent::setUp();

        $this->html = $this->get(route('about'))->assertOk()->getContent();
    }

    #[Test]
    public function the_header_condenses_on_scroll_through_a_class_the_stylesheet_animates(): void
    {
        $this->assertMatchesRegularExpression('/<header class="site-header fixed inset-x-0 top-0 z-40" x-data="siteMenu" :class="\{ \'is-condensed\': condensed \}"[^>]*@scroll\.window\.passive="onScroll\(\)"/', $this->html);
        $this->assertStringContainsString('class="site-header__bar mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 sm:px-6"', $this->html);

        $css = file_get_contents(resource_path('css/wayfinding.css'));
        $this->assertStringContainsString(':root.header-condensed {', $css);
        $this->assertMatchesRegularExpression('/\.site-header__bar \{\s*height: var\(--header-height\);\s*transition: height var\(--duration-condense\) var\(--ease-rise\);/', $css);
        $this->assertStringContainsString('--duration-condense: '.Motion::HEADER_CONDENSE_DURATION_MS.'ms;', $css);

        $js = file_get_contents(resource_path('js/menu.js'));
        $this->assertStringContainsString('const CONDENSE_AFTER = '.Motion::HEADER_CONDENSE_AFTER_PX.';', $js);
        $this->assertStringContainsString('classList.toggle(CONDENSED_CLASS, condensed)', $js);
        $this->assertStringContainsString('requestAnimationFrame', $js);
    }

    #[Test]
    public function the_active_section_carries_the_brass_rule(): void
    {
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('about'), '/').'" class="nav-link t-label"\s+aria-current="page"\s*>About<\/a>/', $this->html);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertMatchesRegularExpression("/\.nav-link\[aria-current='page'\]::after \{\s*background-color: var\(--color-brass\);\s*transform: scaleX\(1\);/", $css);
    }

    #[Test]
    public function the_search_button_is_a_link_to_the_plain_page_with_the_keyboard_shortcuts_declared(): void
    {
        $this->assertStringContainsString('<a href="'.route(Search::ROUTE).'" id="site-search-opener" class="site-search-toggle" aria-keyshortcuts="'.Search::keyShortcuts().'" @click.prevent="$dispatch(\'search-open\', { opener: $el })">', $this->html);
        $this->assertStringContainsString('<span class="sr-only">'.Search::TITLE.'</span><span class="t-label hidden sm:inline lg:hidden xl:inline" aria-hidden="true">'.Search::TITLE.'</span>', $this->html, 'Icon only where the label would not fit; the name is always read out');
        $this->assertStringContainsString('<kbd class="site-search-toggle__key" aria-hidden="true">/</kbd>', $this->html);
    }

    #[Test]
    public function the_search_dialog_is_a_modal_with_a_focus_trap_escape_and_a_live_status(): void
    {
        $this->assertMatchesRegularExpression('/<div id="site-search" x-data="siteSearch" x-ref="panel" x-show="open" x-cloak[^>]*role="dialog" aria-modal="true" aria-labelledby="site-search-title" tabindex="-1" @search-open\.window="show\(\$event\.detail\?\.opener\)" @keydown\.escape\.stop="close\(\)" @keydown\.tab="trap\(\$event\)"/', $this->html);
        $this->assertStringContainsString('class="site-search glow-brass on-dark fixed inset-0 z-50 overflow-y-auto bg-evergreen text-champagne"', $this->html);
        $this->assertStringContainsString('<p id="site-search-title" class="t-label text-brass">'.Search::LABEL.'</p>', $this->html, 'Not a heading: the page h1 must stay first');
        $this->assertMatchesRegularExpression('/<form x-ref="form" method="get" action="'.preg_quote(route(Search::ROUTE), '/').'" role="search"/', $this->html);
        $this->assertStringContainsString('<label for="site-search-input" class="sr-only">'.Search::LABEL.'</label>', $this->html);
        $this->assertMatchesRegularExpression('/<input x-ref="input" id="site-search-input" type="search" name="'.Search::PARAM.'" x-model="query" @input\.debounce\.250ms="lookup\(\)"[^>]*aria-controls="site-search-results"/', $this->html);
        $this->assertStringContainsString('<p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="status"></p>', $this->html);
        $this->assertStringContainsString('data-skeleton', $this->html, 'A linen skeleton waits for results');
        $this->assertStringContainsString('<div id="site-search-results" x-ref="results"', $this->html);
        $this->assertStringContainsString(Search::PROMPT, $this->html);

        $js = file_get_contents(resource_path('js/search.js'));
        $this->assertStringContainsString("event.key === '/'", $js);
        $this->assertStringContainsString('event.metaKey || event.ctrlKey', $js);
        $this->assertStringContainsString("Accept: 'application/json'", $js);
        $this->assertStringContainsString('AbortController', $js);
        $this->assertStringContainsString("Alpine.data('siteSearch', siteSearch)", file_get_contents(resource_path('js/app.js')));
    }

    #[Test]
    public function the_full_screen_menu_is_a_table_of_contents_with_roman_numerals(): void
    {
        preg_match('/<nav class="[^"]*" aria-label="Site sections">(.*?)<\/nav>/s', $this->html, $menu);

        $this->assertStringContainsString('<p class="t-label text-brass site-menu__caption">Contents</p>', $menu[1]);
        $this->assertStringContainsString('<ol class="site-menu__list', $menu[1]);
        preg_match_all('/<span class="site-menu__numeral t-numeral text-brass" aria-hidden="true">([IVX]+)<\/span>/', $menu[1], $numerals);
        $this->assertSame(['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI'], $numerals[1]);
        $this->assertStringContainsString('<span class="sr-only">Chapter I: </span>About</a>', $menu[1]);
        $this->assertStringContainsString('class="site-menu__search link-draw smallcaps">'.Search::LABEL.'</a>', $menu[1]);
    }

    #[Test]
    public function public_pages_still_load_no_livewire(): void
    {
        $this->assertStringNotContainsString('livewire', $this->html);
    }
}
