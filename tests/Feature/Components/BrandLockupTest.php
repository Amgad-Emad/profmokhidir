<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandLockupTest extends TestCase
{
    #[Test]
    public function the_stacked_lockup_puts_a_72px_mark_above_the_label_and_name(): void
    {
        $html = Blade::render('<x-brand.lockup variant="stacked" />');

        $this->assertStringContainsString('brand-lockup--stacked', $html);
        $this->assertStringContainsString('data-size="72"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html, 'The mark is decorative beside the name');
        $this->assertMatchesRegularExpression('/<svg.*<span class="t-label text-accent-text">Professor<\/span>\s*<span class="font-display font-light leading-none text-3xl">Mohamed Osman Khidir<\/span>/s', $html);
    }

    #[Test]
    public function the_horizontal_lockup_puts_a_32px_ring_mark_left_of_the_text(): void
    {
        $html = Blade::render('<x-brand.lockup variant="horizontal" />');

        $this->assertStringContainsString('brand-lockup--horizontal', $html);
        $this->assertStringContainsString('data-size="32"', $html);
        $this->assertStringContainsString('data-variant="ring"', $html);
        $this->assertStringContainsString('text-lg sm:text-xl', $html);
    }

    #[Test]
    public function tone_evergreen_uses_a_bronze_label_for_light_surfaces(): void
    {
        $html = Blade::render('<x-brand.lockup tone="evergreen" />');

        $this->assertStringContainsString('t-label text-accent-text', $html);
        $this->assertStringContainsString('data-tone="evergreen"', $html);
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
