<?php

namespace Tests\Unit\Repositories;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Models\Album;
use App\Models\Publication;
use App\Models\Video;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpatieMediaRepositorySearchTest extends TestCase
{
    use RefreshDatabase;

    private MediaRepositoryInterface $media;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->media = $this->app->make(MediaRepositoryInterface::class);
    }

    #[Test]
    public function search_images_covers_every_image_collection_but_no_documents(): void
    {
        $album = Album::factory()->create();
        $video = Video::factory()->create();
        $complete = $this->media->attach($album, PlaceholderImage::grey(16, 16), MediaCollections::PHOTOS, [
            MediaProperties::ALT_TEXT => 'Lecture hall', MediaProperties::CAPTION => 'Teaching', MediaProperties::PLACE => 'Khartoum', MediaProperties::YEAR => 1975, MediaProperties::TREATMENT => 'archive',
        ]);
        $blankCaption = $this->media->attach($album, PlaceholderImage::grey(16, 16), MediaCollections::PHOTOS, [
            MediaProperties::ALT_TEXT => 'Alt', MediaProperties::CAPTION => '', MediaProperties::PLACE => 'Wad Medani', MediaProperties::YEAR => 1975,
        ]);
        $poster = $this->media->attach($video, PlaceholderImage::grey(16, 9), MediaCollections::THUMBNAIL, [MediaProperties::TREATMENT => 'portrait']);
        $this->media->attach(Publication::factory()->create(), UploadedFile::fake()->create('p.pdf', 1, 'application/pdf'), MediaCollections::FILE);

        $all = $this->media->searchImages();
        $this->assertSame(3, $all->total());
        $this->assertEqualsCanonicalizing([$complete->getKey(), $blankCaption->getKey(), $poster->getKey()], $all->pluck('id')->all());

        $this->assertSame([$complete->getKey()], $this->media->searchImages('lecture')->pluck('id')->all());
        $this->assertSame([$blankCaption->getKey()], $this->media->searchImages('medani')->pluck('id')->all());
        $this->assertSame([$poster->getKey()], $this->media->searchImages(null, [MediaRepositoryInterface::FILTER_COLLECTION => MediaCollections::THUMBNAIL])->pluck('id')->all());
        $this->assertSame([$poster->getKey()], $this->media->searchImages(null, [MediaRepositoryInterface::FILTER_TREATMENT => 'portrait'])->pluck('id')->all());
        $this->assertSame([$poster->getKey()], $this->media->searchImages(null, [MediaRepositoryInterface::FILTER_MODEL => Video::class])->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$blankCaption->getKey(), $poster->getKey()], $this->media->searchImages(null, [MediaRepositoryInterface::FILTER_INCOMPLETE => true])->pluck('id')->all());
        $this->assertSame(2, $this->media->countIncompleteImages());
        $this->assertSame(0, $this->media->searchImages('%')->total());
    }
}
