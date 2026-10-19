<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\PublicationType;
use App\Models\Publication;
use App\Services\PublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PublicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(PublicationService::class);
    }

    #[Test]
    public function slugs_are_generated_from_the_title_and_made_unique(): void
    {
        $first = $this->service->create(['title' => 'Dryland Agronomy', 'type' => 'book']);
        $second = $this->service->create(['title' => 'Dryland Agronomy', 'type' => PublicationType::Book]);

        $this->assertSame('dryland-agronomy', $first->slug);
        $this->assertSame('dryland-agronomy-2', $second->slug);
        $this->assertSame(PublicationType::Book, $first->type);
    }

    #[Test]
    public function editing_the_title_keeps_the_existing_slug(): void
    {
        $publication = $this->service->create(['title' => 'Original', 'type' => 'report']);

        $publication = $this->service->update($publication, ['title' => 'Changed']);

        $this->assertSame('original', $publication->slug);
        $this->assertSame('Changed', $publication->title);

        $publication = $this->service->update($publication, ['slug' => 'custom slug']);
        $this->assertSame('custom-slug', $publication->slug);
    }

    #[Test]
    public function the_doi_is_normalised_from_a_url(): void
    {
        $publication = $this->service->create(['title' => 'T', 'type' => 'other', 'doi' => ' https://doi.org/10.1000/abc ']);

        $this->assertSame('10.1000/abc', $publication->doi);
        $this->assertSame('https://doi.org/10.1000/abc', $publication->doiUrl());
    }

    #[Test]
    public function published_filtered_applies_type_and_year(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1990)->create();
        Publication::factory()->published()->type(PublicationType::Book)->year(2000)->create();
        Publication::factory()->published()->type(PublicationType::Report)->year(1990)->create();
        Publication::factory()->draft()->type(PublicationType::Book)->year(1990)->create();

        $this->assertSame(3, $this->service->publishedFiltered()->total());
        $this->assertSame(2, $this->service->publishedFiltered('book')->total());
        $this->assertSame(1, $this->service->publishedFiltered('book', 1990)->total());
        $this->assertSame(2, $this->service->publishedFiltered(null, 1990)->total());
        $this->assertSame(3, $this->service->publishedFiltered('not-a-type')->total());
    }

    #[Test]
    public function filters_are_cached_and_flushed_on_create(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1990)->create();

        $filters = $this->service->filters();
        $this->assertSame([1990], $filters['years']);
        $this->assertSame([PublicationType::Book], $filters['types']);
        $this->assertTrue(Cache::has(CacheKeys::PUBLICATIONS.'.filters'));

        $this->service->create(['title' => 'New', 'type' => 'report', 'year' => 2005, 'status' => 'published']);

        $this->assertFalse(Cache::has(CacheKeys::PUBLICATIONS.'.filters'));
        $this->assertSame([2005, 1990], $this->service->filters()['years']);
    }

    #[Test]
    public function latest_returns_newest_published_first(): void
    {
        Publication::factory()->published()->year(1980)->create();
        $newest = Publication::factory()->published()->year(2010)->create();
        Publication::factory()->published()->year(1995)->create();
        Publication::factory()->draft()->year(2020)->create();

        $latest = $this->service->latest(2);

        $this->assertCount(2, $latest);
        $this->assertTrue($latest->first()->is($newest));
        $this->assertTrue(Cache::has(CacheKeys::PUBLICATIONS.'.latest'));
    }

    #[Test]
    public function files_are_attached_and_removed_on_the_file_collection(): void
    {
        $publication = Publication::factory()->create();

        $media = $this->service->attachFile($publication, UploadedFile::fake()->create('paper.pdf', 10, 'application/pdf'));

        $this->assertSame(MediaCollections::FILE, $media->collection_name);
        $this->assertSame(Disks::PUBLIC, $media->disk);
        $this->assertTrue($publication->fresh()->file()->is($media));

        $this->service->removeFile($publication);
        $this->assertNull($publication->fresh()->file());
    }

    #[Test]
    public function find_published_by_slug_ignores_drafts(): void
    {
        $published = Publication::factory()->published()->create(['slug' => 'pub']);
        Publication::factory()->draft()->create(['slug' => 'draft']);

        $this->assertTrue($this->service->findPublishedBySlug('pub')->is($published));
        $this->assertNull($this->service->findPublishedBySlug('draft'));
    }
}
