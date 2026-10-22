<?php

namespace Tests\Unit\Repositories;

use App\Constants\MediaProperties;
use App\Models\Album;
use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Repositories\Eloquent\EloquentAlbumRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentAlbumRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AlbumRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->repository = $this->app->make(AlbumRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentAlbumRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_by_slug_and_checks_slug_existence(): void
    {
        $draft = Album::factory()->draft()->create(['slug' => 'drafts']);
        $published = Album::factory()->published()->create(['slug' => 'in-the-field']);

        $this->assertTrue($draft->is($this->repository->findBySlug('drafts')));
        $this->assertNull($this->repository->findPublishedBySlug('drafts'));
        $this->assertTrue($published->is($this->repository->findPublishedBySlug('in-the-field')));

        $this->assertTrue($this->repository->slugExists('in-the-field'));
        $this->assertFalse($this->repository->slugExists('in-the-field', $published->id));
        $this->assertFalse($this->repository->slugExists('missing'));
    }

    #[Test]
    public function published_albums_carry_their_photos_in_order_and_expose_a_cover(): void
    {
        Album::factory()->published()->withPhotos(2, 64, 64)->create();
        Album::factory()->draft()->create();

        $published = $this->repository->published();

        $this->assertCount(1, $published);

        $album = $published->first();
        $this->assertTrue($album->relationLoaded('media'));

        $photos = $album->photos();
        $this->assertCount(2, $photos);
        $this->assertSame([1, 2], $photos->pluck('order_column')->all());
        $this->assertSame('[Placeholder] Photo 1', $photos->first()->getCustomProperty(MediaProperties::CAPTION));

        // No flag: the first photo is the cover.
        $this->assertTrue($photos->first()->is($album->cover()));

        // Flag the second photo: it becomes the cover.
        $photos->last()->setCustomProperty(MediaProperties::IS_COVER, true)->save();

        $this->assertTrue($photos->last()->is($album->fresh()->cover()));
    }

    #[Test]
    public function cover_is_null_for_an_album_without_photos(): void
    {
        $album = Album::factory()->published()->create();

        $this->assertNull($album->cover());
    }

    #[Test]
    public function the_newest_published_album_is_the_latest_by_publication_date(): void
    {
        $this->assertNull($this->repository->newestPublished());

        Album::factory()->published()->create(['title' => 'Older', 'published_at' => now()->subYear(), 'sort_order' => 1]);
        $newest = Album::factory()->published()->create(['title' => 'Newest', 'published_at' => now()->subDay(), 'sort_order' => 9]);
        Album::factory()->draft()->create(['title' => 'Draft', 'published_at' => now()]);

        $this->assertTrue($this->repository->newestPublished()->is($newest), 'Publication date wins over display order; drafts are skipped');
        $this->assertTrue($this->repository->newestPublished()->relationLoaded('media'));
    }
}
