<?php

namespace Tests\Feature\Layout;

use App\Constants\Chapters;
use App\Constants\Disks;
use App\Constants\Masthead;
use App\Constants\Motion;
use App\Constants\Search;
use App\Models\Album;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The masthead: the evergreen glass and its states, the five chapter disclosures
 * and their panels, the right-hand side, the utility line, the scroll behaviour
 * (held in CSS variables and the script), the full-screen menu and the search dialog.
 */
class MastheadTest extends TestCase
{
    use RefreshDatabase;

    private string $html;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->html = $this->get(route('about'))->assertOk()->getContent();
    }

    private function header(?string $html = null): string
    {
        preg_match('/<header class="site-header.*?<\/header>/s', $html ?? $this->html, $header);

        return $header[0];
    }

    #[Test]
    public function the_glass_is_true_evergreen_with_a_solid_fallback_and_a_brass_hairline(): void
    {
        $this->assertStringContainsString('<div class="glass-header on-dark text-ink">', $this->html);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('color-mix(in srgb, var(--brand-evergreen) '.Masthead::GLASS_OPACITY_PERCENT.'%, transparent)', file_get_contents(resource_path('css/tokens.css')), 'The light-theme glass is evergreen at the masthead opacity');
        $this->assertMatchesRegularExpression('/\.glass-header \{\s*background-color: var\(--surface-glass\);\s*backdrop-filter: blur\('.Masthead::GLASS_BLUR_PX.'px\) saturate\('.Masthead::GLASS_SATURATE_PERCENT.'%\);[^}]*border-bottom: 1px solid color-mix\(in srgb, var\(--accent-line\) '.Masthead::HAIRLINE_OPACITY_PERCENT.'%, transparent\);/', $css);
        $this->assertMatchesRegularExpression('/@supports not \(backdrop-filter: blur\(1px\)\) \{\s*\.glass-header \{\s*background-color: var\(--surface-band\);/', $css);
        $this->assertStringNotContainsString('-webkit-backdrop-filter', $css, 'Lightning CSS adds the prefix; writing it by hand drops the standard property');

        $masthead = file_get_contents(resource_path('css/masthead.css'));
        $this->assertMatchesRegularExpression('/\.site-header\.is-over-hero \.glass-header \{\s*background-color: transparent;\s*backdrop-filter: none;\s*border-bottom-color: transparent;/', $masthead);
    }

    #[Test]
    public function the_bar_holds_five_chapter_disclosures_and_marks_the_active_one(): void
    {
        preg_match('/<nav class="site-chapters hidden lg:block" aria-label="'.Masthead::NAV_LABEL.'"[^>]*>(.*?)<\/nav>/s', $this->html, $nav);
        preg_match_all('/<button type="button" id="(chapter-[a-z]+-button)" class="chapter-link t-label" aria-expanded="false" :aria-expanded="[^"]+" aria-controls="(chapter-[a-z]+)"( aria-current="true")? @click="toggleChapter\(\'([a-z]+)\'\)"[^>]*>([A-Za-z]+)<\/button>/', $nav[1], $buttons, PREG_SET_ORDER);

        $this->assertCount(5, $buttons);
        $this->assertSame(['Life', 'Work', 'Archive', 'Community', 'Tributes'], array_column($buttons, 5));
        $this->assertSame(Chapters::all(), array_column($buttons, 4));
        $this->assertSame(['chapter-life', 'chapter-work', 'chapter-archive', 'chapter-community', 'chapter-tributes'], array_column($buttons, 2));
        $this->assertSame([' aria-current="true"', '', '', '', ''], array_column($buttons, 3), 'About belongs to Life, the only active chapter');
        $this->assertStringNotContainsString('nav-more', $this->html, 'No More overflow');

        foreach ($buttons as $button) {
            $this->assertStringContainsString('@keydown.down.prevent="openAndFocus(\''.$button[4].'\')"', $nav[1]);
        }
        $this->assertStringContainsString('@keydown.right.prevent="focusSibling($event, 1)"', $nav[1]);
        $this->assertStringContainsString('@keydown.left.prevent="focusSibling($event, -1)"', $nav[1]);
        $this->assertMatchesRegularExpression('/<li class="site-chapter" data-chapter="life" @mouseenter="hoverOpen\(\'life\'\)" @mouseleave="hoverClose\(\'life\'\)" @focusout="onFocusOut\(\$event, \'life\'\)" @click\.outside="onClickOutside\(\'life\'\)">/', $nav[1]);

        $css = file_get_contents(resource_path('css/masthead.css'));
        $this->assertMatchesRegularExpression("/\.chapter-link\[aria-current='true'\]::after \{\s*background-color: var\(--accent-line\);\s*transform: scaleX\(1\);/", $css);
        $this->assertMatchesRegularExpression("/\.chapter-link:hover::after,\s*\.chapter-link:focus-visible::after,\s*\.chapter-link\[aria-expanded='true'\]::after \{\s*transform: scaleX\(1\);/", $css, 'The hairline draws on hover and focus');
        $this->assertStringContainsString('transform-origin: left center;', $css);
    }

    #[Test]
    public function each_panel_carries_its_numeral_name_strap_links_and_descriptions(): void
    {
        preg_match('/<div id="chapter-life" class="mega-menu" x-show="isOpen\(\'life\'\)" x-cloak x-transition:enter="mega-menu--moving" x-transition:enter-start="mega-menu--hidden" x-transition:leave="mega-menu--moving" x-transition:leave-end="mega-menu--hidden" aria-labelledby="chapter-life-button" @keydown\.down\.prevent="moveFocus\(\$event, 1\)" @keydown\.up\.prevent="moveFocus\(\$event, -1\)" @keydown\.home\.prevent="moveFocus\(\$event, \'first\'\)" @keydown\.end\.prevent="moveFocus\(\$event, \'last\'\)">(.*?)<div id="chapter-work"/s', $this->html, $life);

        $this->assertStringContainsString('<p class="t-numeral text-accent-text" aria-hidden="true">I</p>', $life[1]);
        $this->assertStringContainsString('<p class="mega-menu__name"><span class="sr-only">Chapter I: </span>Life</p>', $life[1]);
        $this->assertStringContainsString('<p class="mega-menu__strap">The man, his path and his honours.</p>', $life[1]);
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('about'), '/').'" class="mega-menu__link" aria-current="page">\s*<span class="mega-menu__link-label">About<\/span>\s*<span class="mega-menu__link-description">'.preg_quote(Chapters::descriptions()['about'], '/').'<\/span>/', $life[1]);
        $this->assertStringContainsString('>Other Information</span>', $life[1], 'Other Information is reachable from Life');
        $this->assertStringNotContainsString('mega-menu__featured', $life[1], 'Nothing published: no featured block');

        preg_match_all('/<p class="t-numeral text-accent-text" aria-hidden="true">([IVX]+)<\/p>/', $this->header(), $numerals);
        $this->assertSame(['I', 'II', 'III', 'IV', 'V'], $numerals[1]);

        $css = file_get_contents(resource_path('css/masthead.css'));
        $this->assertStringContainsString('--mega-rise: '.Masthead::MENU_RISE_PX.'px;', $css);
        $this->assertStringContainsString('--duration-mega: '.Masthead::MENU_DURATION_MS.'ms;', $css);
        $this->assertMatchesRegularExpression('/\.mega-menu--hidden \{\s*opacity: 0;\s*transform: translateY\(var\(--mega-rise\)\);/', $css);
        $this->assertMatchesRegularExpression('/\.mega-menu__name \{[^}]*font-family: var\(--font-display\);/', $css);
        $this->assertMatchesRegularExpression('/\.mega-menu__link-description,\s*\.site-menu__link-description \{[^}]*font-family: var\(--font-sans\);\s*font-weight: 300;/', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', file_get_contents(resource_path('css/app.css')));
    }

    #[Test]
    public function the_panels_feature_the_latest_publication_newest_album_and_featured_tribute(): void
    {
        Publication::factory()->published()->year(2015)->create(['title' => 'Soils of the Gezira']);
        Album::factory()->published()->withPhotos(1, 64, 64)->create(['title' => 'In the field', 'published_at' => now()->subDay()]);
        Tribute::factory()->featured()->create(['name' => 'Amna Hassan', 'message' => 'He taught us to look twice.']);
        $this->app['cache']->flush(); // The first request in setUp cached the empty listings; factories bypass the services' flush.

        $header = $this->header($this->get(route('about'))->assertOk()->getContent());

        $this->assertMatchesRegularExpression('/<p class="t-label text-accent-text">Latest publication<\/p>\s*<a href="[^"]+#publication-[^"]+" class="featured-card" data-masthead-featured="publication">\s*<span class="featured-card__title">Soils of the Gezira<\/span>/', $header);
        $this->assertMatchesRegularExpression('/<p class="t-label text-accent-text">Newest album<\/p>\s*<a href="[^"]+" class="featured-card" data-masthead-featured="album">\s*<figure class="photo flex flex-col gap-3 featured-card__photo">.*?<img[^>]*class="photo-archive h-full w-full object-cover"[^>]*loading="lazy"/s', $header, 'The album cover is a small lazy-loaded duotone');
        $this->assertStringContainsString('<span class="featured-card__meta smallcaps">1 photograph</span>', $header);
        $this->assertMatchesRegularExpression('/data-masthead-featured="tribute">\s*<span class="featured-card__title">Amna Hassan<\/span>\s*<span class="featured-card__quote">&ldquo;He taught us to look twice\.&rdquo;<\/span>/', $header);
        $this->assertStringNotContainsString('museum-label', $header, 'No captions inside the panels');
    }

    #[Test]
    public function the_right_side_holds_only_the_search_button_the_tribute_pill_and_the_menu_button(): void
    {
        preg_match('/<div class="flex items-center gap-1 sm:gap-3">(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $this->header(), $right);
        preg_match_all('/<(a|button)\b/', $right[1], $controls);

        // Search, the tribute pill, the theme toggle (one button, no menu) and the menu toggle.
        $this->assertSame(['a', 'a', 'button', 'button'], $controls[1]);
        $this->assertStringContainsString('data-theme-toggle', $right[1]);
        $this->assertStringContainsString('<a href="'.route(Search::ROUTE).'" id="'.Masthead::SEARCH_OPENER_ID.'" class="site-search-toggle" aria-keyshortcuts="'.Search::keyShortcuts().'" @click.prevent="$dispatch(\'search-open\', { opener: $el })">', $right[1]);
        $this->assertStringContainsString('<span class="sr-only">'.Search::TITLE.'</span>', $right[1]);
        $this->assertStringContainsString('<kbd class="site-search-toggle__key" aria-hidden="true">/</kbd>', $right[1]);
        $this->assertStringContainsString('<a href="'.route('tributes.create').'" class="btn-pill-outline btn-pill--small site-header__tribute hidden xl:inline-flex">'.Masthead::LEAVE_TRIBUTE.'</a>', $right[1]);
        $this->assertMatchesRegularExpression('/<button type="button" x-ref="toggle" class="site-menu-toggle lg:hidden" aria-expanded="false" :aria-expanded="[^"]+" aria-controls="'.Masthead::MENU_ID.'" @click="toggleMenu\(\)">\s*<span class="t-label">'.Masthead::MENU.'<\/span>/', $right[1]);
    }

    #[Test]
    public function the_utility_line_sits_above_the_bar_on_wide_screens_and_changes_with_the_visitor(): void
    {
        preg_match('/<div class="masthead-utility hidden xl:block"[^>]*>(.*?)<\/div>\s*<\/div>/s', $this->html, $utility);

        $this->assertStringContainsString('<p class="t-label text-ink">'.Masthead::UTILITY_LINE.'</p>', $utility[1]);
        $this->assertStringContainsString('<a href="'.route('login').'" class="masthead-utility__link t-label">'.Masthead::SIGN_IN.'</a>', $utility[1]);
        $this->assertLessThan(strpos($this->html, 'site-header__bar'), strpos($this->html, 'masthead-utility'), 'Above the bar');

        $member = $this->actingAs(User::factory()->member()->create())->get(route('about'))->assertOk()->getContent();
        $this->assertStringContainsString('<a href="'.route('community.chat').'" class="masthead-utility__link t-label">'.Masthead::OPEN_CHAT.'</a>', $member);
        $this->assertStringNotContainsString(Masthead::SIGN_IN, $this->header($member));

        preg_match('/<div id="chapter-community".*?<div id="chapter-tributes"/s', $member, $community);
        $this->assertStringContainsString('>'.Masthead::OPEN_CHAT.'</span>', $community[0], 'Members get the chat link inside the Community panel');

        $css = file_get_contents(resource_path('css/masthead.css'));
        $this->assertStringContainsString('--masthead-utility-height: 2rem;', $css);
        $this->assertMatchesRegularExpression('/\.site-header\.is-compact \.masthead-utility \{\s*height: 0;\s*opacity: 0;/', $css, 'Hidden when the bar compacts');
    }

    #[Test]
    public function the_bar_compacts_on_scroll_down_reserves_its_height_and_keeps_anchors_clear(): void
    {
        $this->assertMatchesRegularExpression('/<header class="site-header fixed inset-x-0 top-0 z-40" x-data="masthead" :class="\{ \'is-compact\': compact, \'is-over-hero\': overHero, \'has-chapter-open\': openChapter !== null \}" @keydown\.escape\.window="onEscape\(\)" @resize\.window="onResize\(\)" @scroll\.window\.passive="onScroll\(\)">/', $this->html);
        $this->assertStringContainsString('<main id="main" tabindex="-1" class="site-main">', $this->html);

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('<main id="main" tabindex="-1" class="site-main site-main--hero">', $home);
        $this->assertMatchesRegularExpression('/<section class="hero[^"]*"[^>]*'.Masthead::HERO_ATTRIBUTE.'>/', $home);

        $css = file_get_contents(resource_path('css/masthead.css'));
        $this->assertStringContainsString('--masthead-height: 5.5rem; /* '.Masthead::HEIGHT_PX.'px */', $css);
        $this->assertStringContainsString('--masthead-compact-height: 4rem; /* '.Masthead::COMPACT_HEIGHT_PX.'px */', $css);
        $this->assertStringContainsString('--duration-masthead: '.Masthead::TRANSITION_MS.'ms;', $css);
        $this->assertSame(400, Motion::HEADER_CONDENSE_DURATION_MS);
        $this->assertStringContainsString('--masthead-offset: calc(var(--masthead-height) + var(--masthead-utility-height));', $css);
        $this->assertMatchesRegularExpression('/:root\.'.Masthead::COMPACT_CLASS.' \{\s*--header-height: var\(--masthead-compact-height\);/', $css);
        $this->assertMatchesRegularExpression('/\.site-main \{\s*padding-top: var\(--masthead-offset\);/', $css, 'The page reserves the full height, so nothing jumps');
        $this->assertMatchesRegularExpression('/\.site-header\.is-compact \.site-header__bar \{\s*height: var\(--masthead-compact-height\);/', $css);
        $this->assertMatchesRegularExpression('/\.site-header\.is-compact \.site-header__lockup \.t-label \{\s*max-height: 0;\s*opacity: 0;/', $css, 'The lockup folds to the mark and the name');
        $this->assertStringContainsString('scroll-padding-top: calc(var(--header-height) + 1.5rem);', file_get_contents(resource_path('css/app.css')));

        $js = file_get_contents(resource_path('js/masthead.js'));
        $this->assertStringContainsString('const COMPACT_AFTER = '.Masthead::COMPACT_AFTER_PX.';', $js);
        $this->assertStringContainsString('const SCROLL_JITTER = '.Masthead::SCROLL_JITTER_PX.';', $js);
        $this->assertStringContainsString('const HOVER_OPEN_DELAY = '.Masthead::HOVER_OPEN_DELAY_MS.';', $js);
        $this->assertStringContainsString("const COMPACT_CLASS = '".Masthead::COMPACT_CLASS."';", $js);
        $this->assertStringContainsString("const OVER_HERO_CLASS = '".Masthead::OVER_HERO_CLASS."';", $js);
        $this->assertStringContainsString("const HERO_SELECTOR = '[".Masthead::HERO_ATTRIBUTE."]';", $js);
        $this->assertStringContainsString('} else if (delta < -SCROLL_JITTER) {', $js, 'Scrolling up restores the bar');
        $this->assertStringContainsString('requestAnimationFrame', $js);
        $this->assertStringContainsString("Alpine.data('masthead', masthead)", file_get_contents(resource_path('js/app.js')));
        $this->assertFileDoesNotExist(resource_path('js/menu.js'));
    }

    #[Test]
    public function the_full_screen_menu_is_an_accordion_table_of_contents_with_search_and_the_pill(): void
    {
        preg_match('/<div id="'.Masthead::MENU_ID.'"[^>]*>(.*?)<div id="'.Masthead::SEARCH_ID.'"/s', $this->html, $menu);
        $menu = $menu[1];

        $this->assertMatchesRegularExpression('/<div id="'.Masthead::MENU_ID.'" x-ref="panel" x-show="open" x-cloak[^>]*role="dialog" aria-modal="true" aria-label="'.Masthead::MENU_DIALOG_LABEL.'" tabindex="-1" @keydown\.tab="trap\(\$event\)"/', $this->html);
        $this->assertMatchesRegularExpression('/<form novalidate method="get" action="'.preg_quote(route(Search::ROUTE), '/').'" role="search" class="site-menu__search">\s*<div class="form-field[^"]*form-style-underline[^"]*" data-form-field\s*>\s*<label id="site-menu-search-label" for="site-menu-search" class="t-label form-label form-label--hidden">'.Search::LABEL.'<\/label>/', $menu);
        $this->assertMatchesRegularExpression('/<input class="form-control__input site-menu__search-input" id="site-menu-search" name="'.Search::PARAM.'"[^>]*type="search"/', $menu);
        $this->assertLessThan(strpos($menu, 'aria-label="Site sections"'), strpos($menu, 'site-menu__search'), 'The search field comes first');
        $this->assertStringContainsString('<p class="t-label text-accent-text site-menu__caption">'.Masthead::CONTENTS.'</p>', $menu);

        preg_match_all('/<li class="site-menu__item" style="--i: (\d+)" x-data="\{ expanded: (true|false) \}">\s*<button type="button" class="site-menu__chapter" aria-expanded="(true|false)" :aria-expanded="[^"]+" aria-controls="(menu-chapter-[a-z]+)"( aria-current="true")?/', $menu, $items, PREG_SET_ORDER);
        $this->assertSame(['0', '1', '2', '3', '4'], array_column($items, 1));
        $this->assertSame(['true', 'false', 'false', 'false', 'false'], array_column($items, 3), 'The active chapter opens expanded');
        $this->assertSame(['menu-chapter-life', 'menu-chapter-work', 'menu-chapter-archive', 'menu-chapter-community', 'menu-chapter-tributes'], array_column($items, 4));

        preg_match_all('/<span class="site-menu__numeral t-numeral text-accent-text" aria-hidden="true">([IVX]+)<\/span>/', $menu, $numerals);
        $this->assertSame(['I', 'II', 'III', 'IV', 'V'], $numerals[1]);
        $this->assertStringContainsString('<span class="sr-only">Chapter I: </span>Life</span>', $menu);
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('about'), '/').'" class="site-menu__link" aria-current="page">\s*<span class="site-menu__link-label">About<\/span>/', $menu);
        $this->assertStringContainsString('Other Information', $menu);

        preg_match('/<div class="site-menu__foot[^"]*">(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $menu, $foot);
        $this->assertStringContainsString('<a href="'.route('tributes.create').'" class="btn-pill-outline on-dark">'.Masthead::LEAVE_TRIBUTE.'</a>', $foot[1]);
        $this->assertStringContainsString('>'.Masthead::SIGN_IN.'</a>', $foot[1]);
        $this->assertStringContainsString('x-ref="close"', $menu);

        $css = file_get_contents(resource_path('css/site-menu.css'));
        $this->assertMatchesRegularExpression('/\.site-menu__item \{\s*opacity: 0;\s*transform: translateY\(var\(--distance-rise\)\);/', $css);
        $this->assertStringContainsString('transition-delay: calc(var(--i, 0) * var(--duration-stagger));', $css);
    }

    #[Test]
    public function the_search_dialog_is_a_modal_with_a_focus_trap_escape_and_a_live_status(): void
    {
        $this->assertMatchesRegularExpression('/<div id="'.Masthead::SEARCH_ID.'" x-data="siteSearch" x-ref="panel" x-show="open" x-cloak[^>]*role="dialog" aria-modal="true" aria-labelledby="site-search-title" tabindex="-1" @search-open\.window="show\(\$event\.detail\?\.opener\)" @keydown\.escape\.stop="close\(\)" @keydown\.tab="trap\(\$event\)"/', $this->html);
        $this->assertStringContainsString('<p id="site-search-title" class="t-label text-accent-text">'.Search::LABEL.'</p>', $this->html, 'Not a heading: the page h1 must stay first');
        $this->assertMatchesRegularExpression('/<input class="form-control__input" id="site-search-input" name="'.Search::PARAM.'"[^>]*aria-describedby="site-search-hint"[^>]*type="search"[^>]*x-ref="input" x-model="query" x-on:input\.debounce\.250ms="lookup\(\)" aria-controls="site-search-results"/', $this->html);
        $this->assertStringContainsString('<p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="status"></p>', $this->html);
        $this->assertStringContainsString(Search::PROMPT, $this->html);
    }

    #[Test]
    public function public_pages_still_load_no_livewire(): void
    {
        $this->assertStringNotContainsString('livewire', $this->html);
    }
}
