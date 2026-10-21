<?php

namespace Tests\Unit\Constants;

use App\Constants\Archive;
use App\Constants\PictureView;
use App\Constants\WallFormat;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    #[Test]
    public function decades_run_from_the_first_to_the_current_one_with_labels(): void
    {
        $decades = Archive::decades();

        $this->assertSame(Archive::FIRST_DECADE, $decades[0]);
        $this->assertSame(Archive::decadeOf((int) date('Y')), end($decades));
        $this->assertSame(10, $decades[1] - $decades[0]);
        $this->assertSame('1970s', Archive::label(1970));
        $this->assertSame('Undated', Archive::label(null));
        $this->assertSame(1980, Archive::decadeOf(1987));
        $this->assertSame(['1930s', '1940s'], array_slice(array_values(Archive::decadeOptions()), 0, 2));
        $this->assertSame([1930, 1940], array_slice(array_keys(Archive::decadeOptions()), 0, 2));
    }

    #[Test]
    public function the_pictures_view_is_read_from_the_query_string_and_written_back(): void
    {
        $this->assertSame(PictureView::Decades, PictureView::fromQuery('decades'));
        $this->assertSame(PictureView::Albums, PictureView::fromQuery('albums'));
        $this->assertSame(PictureView::Albums, PictureView::fromQuery('nonsense'));
        $this->assertSame(PictureView::Albums, PictureView::fromQuery(null));
        $this->assertSame(PictureView::Albums, PictureView::fromQuery(['decades']));
        $this->assertSame([], PictureView::Albums->query());
        $this->assertSame([Archive::QUERY_VIEW => 'decades'], PictureView::Decades->query());
        $this->assertSame('Through the decades', PictureView::Decades->label());
    }

    #[Test]
    public function wall_formats_follow_the_photographs_proportions_and_span_the_twelve_columns(): void
    {
        $this->assertSame(WallFormat::Portrait, WallFormat::fromDimensions(600, 800));
        $this->assertSame(WallFormat::Square, WallFormat::fromDimensions(800, 800));
        $this->assertSame(WallFormat::Square, WallFormat::fromDimensions(900, 800));
        $this->assertSame(WallFormat::Landscape, WallFormat::fromDimensions(1600, 1067));
        $this->assertSame(WallFormat::Panorama, WallFormat::fromDimensions(2000, 1000));
        $this->assertSame(WallFormat::Landscape, WallFormat::fromDimensions(null, null));
        $this->assertSame(WallFormat::Landscape, WallFormat::fromDimensions(0, 10));

        $this->assertSame([4, 5, 6, 8], array_map(fn (WallFormat $format): int => $format->span(), WallFormat::cases()));
        $this->assertSame([6, 7, 8, 10], array_map(fn (WallFormat $format): int => $format->span(true), WallFormat::cases()));
        $this->assertLessThanOrEqual(Archive::WALL_COLUMNS, WallFormat::Panorama->span(true));
    }
}
