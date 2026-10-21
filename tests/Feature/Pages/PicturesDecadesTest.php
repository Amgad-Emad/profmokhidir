<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Models\Album;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Through the decades": the timeline view of the Pictures index, switched
 * and remembered through ?view=decades.
 */
class PicturesDecadesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function the_timeline_groups_photographs_by_decade_and_remembers_the_view_in_the_url(): void
    {
        $field = Album::factory()->published()->withWall([[64, 48, 1972], [48, 64, 1985]])->create(['title' => 'In the field', 'slug' => 'field']);
        $this->app->make(AlbumService::class)->addPhoto($field, PlaceholderImage::grey(64, 48), ['alt_text' => 'An undated print', 'caption' => 'Undated print']);
        Album::factory()->draft()->withWall([[64, 48, 1950]])->create(['title' => 'Drafts']);

        $response = $this->get(route('pictures', ['view' => 'decades']))->assertOk();

        $response
            ->assertSeeInOrder([
                'aria-label="View"', 'href="'.route('pictures').'" class="filter-pill">Albums', 'href="'.route('pictures', ['view' => 'decades']).'" class="filter-pill" aria-current="true">Through the decades',
                'class="decades" x-data="lightbox"',
                'aria-labelledby="decade-1980"', 'id="decade-1980"', '1980s', '1 photograph', 'rule-brass', 'data-rule', 'class="wall mt-12" data-rise-group aria-label="Photographs from the 1980s"', 'style="--span: 4"', 'museum-label__meta smallcaps">',
                'aria-labelledby="decade-1970"', '1970s', '1 photograph', 'style="--span: 8"',
                'aria-labelledby="decade-undated"', 'Undated', '1 photograph', 'Undated print',
                '<dialog', 'aria-label="Through the decades"',
            ], false)
            ->assertDontSee('1950s')
            ->assertDontSee('exhibition__item', false)
            ->assertDontSee('livewire', false);

        $this->assertSame(1, substr_count($response->getContent(), '<dialog'), 'One object view serves the whole timeline');
    }

    #[Test]
    public function an_unknown_view_falls_back_to_the_album_index_and_the_empty_state_has_no_toggle(): void
    {
        $album = Album::factory()->published()->withWall([[64, 48, 1972]])->create(['title' => 'In the field']);

        $this->get(route('pictures', ['view' => 'sideways']))
            ->assertOk()
            ->assertSee('class="filter-pill" aria-current="true">Albums', false)
            ->assertSee('exhibition__item', false)
            ->assertDontSee('decade-1970');

        $this->app->make(AlbumService::class)->delete($album);

        $this->get(route('pictures', ['view' => 'decades']))
            ->assertOk()
            ->assertSee('Photographs will be gathered into albums here once they are published.')
            ->assertDontSee('aria-label="View"', false);
    }
}
