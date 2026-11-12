<?php

namespace Tests\Feature\Components;

use App\Constants\BrandAssets;
use App\Constants\BrandMark;
use App\Constants\Locales;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandLockupTest extends TestCase
{
    #[Test]
    public function the_stacked_lockup_is_the_stacked_logo_without_the_tagline_by_default(): void
    {
        $html = Blade::render('<x-brand.lockup variant="stacked" />');

        $this->assertStringContainsString('brand-lockup--stacked', $html);
        $this->assertStringContainsString('src="'.asset(BrandAssets::path(BrandAssets::LOGO_STACKED_NO_TAGLINE)).'"', $html);
        $this->assertStringContainsString('alt="'.BrandMark::TITLE.'"', $html, 'The artwork names the Professor');

        $this->assertStringContainsString('src="'.asset(BrandAssets::path(BrandAssets::LOGO_STACKED)).'"', Blade::render('<x-brand.lockup variant="stacked" tagline />'));
    }

    #[Test]
    public function the_horizontal_lockup_is_compact_on_phones_and_one_line_from_sm_up(): void
    {
        $html = Blade::render('<x-brand.lockup variant="horizontal" />');

        $this->assertStringContainsString('brand-lockup--horizontal', $html);
        $this->assertMatchesRegularExpression('/<picture[^>]*><source media="\(min-width: 40rem\)" srcset="'.preg_quote(asset(BrandAssets::path(BrandAssets::LOGO_HORIZONTAL)), '/').'"/', $html);
        $this->assertStringContainsString('<img src="'.asset(BrandAssets::path(BrandAssets::LOGO_COMPACT)).'"', $html);

        $compact = Blade::render('<x-brand.lockup variant="horizontal" compact />');
        $this->assertStringNotContainsString('<picture', $compact);
        $this->assertStringContainsString(BrandAssets::path(BrandAssets::LOGO_COMPACT), $compact);
    }

    #[Test]
    public function tone_evergreen_draws_both_colourways_for_the_page_surface(): void
    {
        $html = Blade::render('<x-brand.lockup variant="stacked" tone="evergreen" />');

        $this->assertStringContainsString(BrandAssets::path(BrandAssets::LOGO_STACKED_NO_TAGLINE, BrandAssets::ON_LIGHT), $html);
        $this->assertStringContainsString(BrandAssets::path(BrandAssets::LOGO_STACKED_NO_TAGLINE, BrandAssets::ON_DARK), $html);
        $this->assertStringContainsString('brand-art--light', $html);
        $this->assertStringContainsString('brand-art--dark', $html);
    }

    #[Test]
    public function arabic_pages_set_the_translated_name_beside_the_mark(): void
    {
        app()->setLocale(Locales::AR);

        $html = Blade::render('<x-brand.lockup variant="stacked" tone="evergreen" />');

        $this->assertStringNotContainsString('brand-lockup--artwork', $html, 'The artwork spells the name in Latin capitals');
        $this->assertStringContainsString('data-size="72"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html, 'The mark is decorative beside the name');
        $this->assertMatchesRegularExpression('/brand-mark.*<span class="t-label text-accent-text">'.preg_quote(__('Professor'), '/').'<\/span>\s*<span class="font-display font-light leading-none text-3xl">'.preg_quote(__('Mohamed Osman Khidir'), '/').'<\/span>/s', $html);

        $this->assertStringContainsString('t-label text-ink', Blade::render('<x-brand.lockup label-tone="champagne" />'));
    }

    #[Test]
    public function it_renders_as_a_link_when_given_an_href(): void
    {
        $html = Blade::render('<x-brand.lockup href="/" class="shrink-0" />');

        $this->assertMatchesRegularExpression('/^<a href="\/"\s+class="[^"]*brand-lockup[^"]*shrink-0[^"]*"/', trim($html));
        $this->assertStringContainsString('</a>', $html);
        $this->assertStringStartsWith('<span', trim(Blade::render('<x-brand.lockup />')));
    }
}
