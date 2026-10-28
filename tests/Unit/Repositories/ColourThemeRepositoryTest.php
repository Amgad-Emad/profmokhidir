<?php

namespace Tests\Unit\Repositories;

use App\Models\ColourTheme;
use App\Repositories\Contracts\ColourThemeRepositoryInterface;
use App\Repositories\Eloquent\EloquentColourThemeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ColourThemeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ColourThemeRepositoryInterface $themes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themes = app(ColourThemeRepositoryInterface::class);
    }

    #[Test]
    public function it_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentColourThemeRepository::class, $this->themes);
    }

    #[Test]
    public function activate_leaves_exactly_one_active_theme(): void
    {
        $first = ColourTheme::factory()->active()->create();
        $second = ColourTheme::factory()->create();

        $this->assertSame($first->id, $this->themes->active()?->id);

        $this->themes->activate($second);

        $this->assertSame($second->id, $this->themes->active()?->id);
        $this->assertSame(1, ColourTheme::query()->where('is_active', true)->count());
        $this->assertFalse($first->refresh()->is_active);
    }

    #[Test]
    public function it_lists_presets_first_and_finds_by_slug(): void
    {
        $custom = ColourTheme::factory()->create();
        $preset = ColourTheme::factory()->preset()->create();

        $this->assertSame([$preset->id, $custom->id], $this->themes->all()->pluck('id')->all());
        $this->assertSame($preset->id, $this->themes->findBySlug($preset->slug)?->id);
        $this->assertNull($this->themes->findBySlug('nothing-here'));
        $this->assertTrue($this->themes->slugExists($custom->slug));
        $this->assertFalse($this->themes->slugExists($custom->slug, $custom->id));
    }

    #[Test]
    public function it_creates_updates_and_deletes(): void
    {
        $theme = $this->themes->create(['name' => 'Plain', 'slug' => 'plain', 'light' => ['surface' => '#ffffff'], 'dark' => []]);
        $this->themes->update($theme, ['draft_light' => ['surface' => '#fafafa']]);

        $this->assertSame(['surface' => '#fafafa'], $this->themes->find($theme->id)->draft_light);
        $this->assertTrue($this->themes->find($theme->id)->hasDraft());
        $this->assertSame(['surface' => '#fafafa'], $theme->working()['light']);

        $this->assertTrue($this->themes->delete($theme));
        $this->assertNull($this->themes->find($theme->id));
    }
}
