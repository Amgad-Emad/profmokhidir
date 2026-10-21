<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Constants\WallFormat;
use App\Models\Album;
use App\Services\AlbumService;
use App\Services\PhotoArchiveService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhotoArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    private PhotoArchiveService $archive;

    private AlbumService $albums;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->archive = $this->app->make(PhotoArchiveService::class);
        $this->albums = $this->app->make(AlbumService::class);
    }

    #[Test]
    public function the_wall_hangs_each_photograph_in_its_own_proportions_featured_ones_larger(): void
    {
        $album = Album::factory()->published()->withWall([[1600, 1067, 1972], [600, 800, 1974], [2000, 1000, 1979], [800, 800, 1981]])->create();
        $this->albums->addPhoto($album, PlaceholderImage::grey(40, 30), [MediaProperties::WIDTH => null, MediaProperties::HEIGHT => null]);

        $wall = $this->archive->wall($album->fresh());

        $this->assertSame([WallFormat::Landscape, WallFormat::Portrait, WallFormat::Panorama, WallFormat::Square, WallFormat::Landscape], array_column($wall, 'format'));
        $this->assertSame([8, 4, 8, 5, 6], array_column($wall, 'span'), 'The first print is featured by withWall() and hangs one size larger');
        $this->assertSame([true, false, false, false, false], array_column($wall, 'featured'));
        $this->assertSame([1970, 1970, 1970, 1980, null], array_column($wall, 'decade'));
        $this->assertSame('(min-width: 1280px) 821px, (min-width: 640px) 67vw, 100vw', $wall[0]['sizes']);
        $this->assertSame('(min-width: 1280px) 821px, (min-width: 640px) 67vw, 100vw', $wall[2]['sizes']);
        $this->assertTrue($wall[0]['album']->is($album));
        $this->assertSame('1972–1981', $this->archive->yearRange($album->fresh()));
    }

    #[Test]
    public function decades_group_every_published_photograph_newest_first_with_overrides_and_undated_last(): void
    {
        $field = Album::factory()->published()->withWall([[64, 48, 1972], [64, 48, 1985], [64, 48, 1979]])->create(['sort_order' => 2]);
        $halls = Album::factory()->published()->withWall([[64, 48, 1998]])->create(['sort_order' => 1]);
        $undated = $this->albums->addPhoto($halls, PlaceholderImage::grey(64, 48));
        Album::factory()->draft()->withWall([[64, 48, 1950]])->create();

        // An editor files the 1985 print under the 1970s.
        $moved = $field->fresh()->photos()->get(1);
        $this->albums->updatePhotoArchive($field, $moved, 1970, false);

        $groups = $this->archive->decades();

        $this->assertSame(['1990s', '1970s', 'Undated'], array_column($groups, 'label'));
        $this->assertSame([1990, 1970, null], array_column($groups, 'decade'));
        $this->assertSame([1, 3, 1], array_column($groups, 'count'));
        $this->assertSame([1972, 1979, 1985], array_map(fn (array $hung): int => (int) $hung['photo']->getCustomProperty(MediaProperties::YEAR), $groups[1]['photos']), 'Within a decade, prints hang by year');
        $this->assertTrue($groups[2]['photos'][0]['photo']->is($undated));
        $this->assertSame(1970, $this->archive->decadeOf($moved->fresh()));
        $this->assertNull($this->archive->decadeOf($undated));
        $this->assertSame([], $this->archive->decades() === [] ? [] : array_filter($groups, fn (array $group): bool => $group['count'] === 0));
    }

    #[Test]
    public function the_year_range_collapses_to_one_year_and_is_null_when_nothing_is_dated(): void
    {
        $album = Album::factory()->published()->withWall([[64, 48, 1972], [64, 48, 1972]])->create();
        $this->assertSame('1972', $this->archive->yearRange($album->fresh()));

        $bare = Album::factory()->published()->create();
        $this->albums->addPhoto($bare, PlaceholderImage::grey(64, 48));
        $this->assertNull($this->archive->yearRange($bare->fresh()));
        $this->assertSame([], $this->archive->wall(Album::factory()->create()));
    }
}
