<?php

namespace Tests\Feature\Styleguide;

use App\Constants\Brand;
use App\Constants\Components;
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

        foreach (['t-display', 't-heading', 't-quote', 't-body', 't-label', 'btn--primary', 'btn--secondary', 'card', 'rule-brass', 'glow-brass', 'photo-portrait', 'photo-archive'] as $class) {
            $response->assertSee($class);
        }

        foreach (['data-rise-group', 'data-rise', 'data-rule', 'data-scroll-scale'] as $attribute) {
            $response->assertSee($attribute, false);
        }

        $response->assertSee('Professor Mohamed Osman Khidir');

        // Every component is catalogued with its usage note.
        $response->assertSee('id="components"', false);

        foreach (Components::groups() as $key => $label) {
            $response->assertSee('data-component-group="'.$key.'"', false)->assertSee($label);
        }

        foreach (Components::all() as $component) {
            $response->assertSee('data-component="'.$component['tag'].'"', false)->assertSee($component['usage']);
        }
    }

    #[Test]
    public function it_uses_the_public_layout_without_livewire(): void
    {
        $this->app['env'] = Environments::LOCAL;

        $this->get(route('styleguide'))
            ->assertOk()
            ->assertDontSee('livewire', false)
            ->assertSee('id="duotone-archive"', false)
            ->assertSee(Typography::path(Typography::FILE_SANS), false);
    }
}
