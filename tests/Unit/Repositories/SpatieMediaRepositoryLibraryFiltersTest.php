<?php

namespace Tests\Unit\Repositories;

use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Models\Album;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * The media library's filters: images without alt text, images nothing on the
 * site uses (their record is gone or in the trash), and one album's photos.
 */
class SpatieMediaRepositoryLibraryFiltersTest extends TestCase
{
    use RefreshDatabase;

    private MediaRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->repository = $this->app->make(MediaRepositoryInterface::class);
    }

    #[Test]
    public function images_without_alt_text_are_counted_and_filtered(): void
    {
        $album = Album::factory()->create();
        $described = $this->photo($album, [MediaProperties::ALT_TEXT => 'A field']);
        $bare = $this->photo($album);

        $this->assertSame(1, $this->repository->countMissingAltText());
        $this->assertSame([$bare->getKey()], $this->ids([MediaRepositoryInterface::FILTER_MISSING_ALT => true]));
        $this->assertContains($described->getKey(), $this->ids([]));
    }

    #[Test]
    public function unused_images_belong_to_trashed_or_missing_records(): void
    {
        $live = Album::factory()->create();
        $trashed = Album::factory()->create();
        $used = $this->photo($live);
        $orphan = $this->photo($trashed);
        $trashed->delete();

        $this->assertSame([$orphan->getKey()], $this->ids([MediaRepositoryInterface::FILTER_UNUSED => true]));
        $this->assertNotContains($used->getKey(), $this->ids([MediaRepositoryInterface::FILTER_UNUSED => true]));
    }

    #[Test]
    public function the_album_filter_keeps_one_albums_photos(): void
    {
        $first = Album::factory()->create();
        $second = Album::factory()->create();
        $mine = $this->photo($first);
        $this->photo($second);

        $this->assertSame([$mine->getKey()], $this->ids([MediaRepositoryInterface::FILTER_ALBUM => $first->getKey()]));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function photo(Album $album, array $properties = []): Media
    {
        return $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(20, 20), $properties);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function ids(array $filters): array
    {
        return $this->repository->searchImages(null, $filters)->getCollection()->modelKeys();
    }
}
