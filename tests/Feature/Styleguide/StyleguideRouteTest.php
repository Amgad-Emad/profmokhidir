<?php

namespace Tests\Feature\Styleguide;

use App\Constants\Brand;
use App\Constants\Environments;
use App\Constants\Typography;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StyleguideRouteTest extends TestCase
{
    #[Test]
    public function it_is_hidden_outside_the_local_environment(): void
    {
        $this->assertSame(Environments::TESTING, $this->app->environment());

        $this->get('/styleguide')->assertNotFound();

        $this->app['env'] = Environments::PRODUCTION;
        $this->get('/styleguide')->assertNotFound();
    }

    #[Test]
    public function it_renders_the_full_design_system_locally(): void
    {
        $this->app['env'] = Environments::LOCAL;

        $response = $this->get(route('styleguide'))->assertOk();

        foreach (Brand::colours() as $name => $hex) {
            $response->assertSee($hex)->assertSee("var(--color-{$name})", false);
        }

        foreach (['t-display', 't-heading', 't-quote', 't-body', 't-label', 'btn-pill-filled', 'btn-pill-outline', 'card', 'rule-brass', 'glow-brass', 'photo-portrait', 'photo-archive'] as $class) {
            $response->assertSee($class);
        }

        foreach (['data-rise-group', 'data-rise', 'data-rule', 'data-scroll-scale'] as $attribute) {
            $response->assertSee($attribute, false);
        }

        $response->assertSee('Professor Mohamed Osman Khidir');
    }

    #[Test]
    public function it_uses_the_public_layout_without_livewire(): void
    {
        $this->app['env'] = Environments::LOCAL;

        $this->get(route('styleguide'))
            ->assertOk()
            ->assertDontSee('livewire', false)
            ->assertSee('id="duotone-archive"', false)
            ->assertSee(e(Typography::GOOGLE_FONTS_URL), false);
    }
}
