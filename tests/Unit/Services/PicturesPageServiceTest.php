<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Models\Album;
use App\Services\AlbumService;
use App\Services\PicturesPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\PictureView;
use Tests\TestCase;

class PicturesPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PicturesPageService $page;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->page = $this->app->make(PicturesPageService::class);
    }

    #[Test]
    public function the_index_lists_published_albums_with_cover_count_and_url(): void
    {
        $second = Album::factory()->published()->withPhotos(2, 64, 64)->create(['sort_order' => 2, 'slug' => 'second']);
        $first = Album::factory()->published()->withPhotos(3, 64, 64)->create(['sort_order' => 1, 'slug' => 'first']);
        $empty = Album::factory()->published()->create(['sort_order' => 3, 'slug' => 'empty']);
        Album::factory()->draft()->withPhotos(1, 64, 64)->create(['sort_order' => 0]);

        $this->app->make(AlbumService::class)->setCover($first, $first->photos()->last());

        $data = $this->page->index();

        $this->assertSame('pictures', $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertSame([$first->id, $second->id, $empty->id], array_map(fn (array $entry): int => $entry['album']->id, $data['albums']));
        $this->assertSame([3, 2, 0], array_column($data['albums'], 'count'));
        $this->assertSame(5, $data['total']);
        $this->assertSame(PictureView::Albums, $data['view']);
        $this->assertSame([], $data['decades']);
        $this->assertSame(
            [['label' => 'Albums', 'url' => route('pictures'), 'active' => true], ['label' => 'Through the decades', 'url' => route('pictures', ['view' => 'decades']), 'active' => false]],
            $data['views'],
        );
        $this->assertMatchesRegularExpression('/^\d{4}(–\d{4})?$/u', $data['albums'][0]['range']);
        $this->assertNull($data['albums'][2]['range']);
        $this->assertTrue($data['albums'][0]['cover']->is($first->fresh()->photos()->last()));
        $this->assertNull($data['albums'][2]['cover']);
        $this->assertSame(route('pictures.show', 'first'), $data['albums'][0]['url']);
        $this->assertTrue(Cache::has(CacheKeys::ALBUMS));
    }

    #[Test]
    public function the_decades_view_groups_every_published_photograph(): void
    {
        Album::factory()->published()->withWall([[64, 48, 1972], [48, 64, 1985]])->create();
        Album::factory()->draft()->withWall([[64, 48, 1999]])->create();

        $data = $this->page->index(PictureView::Decades);

        $this->assertSame(PictureView::Decades, $data['view']);
        $this->assertTrue($data['views'][1]['active']);
        $this->assertSame(['1980s', '1970s'], array_column($data['decades'], 'label'));
        $this->assertSame(route('pictures', ['view' => 'decades']), $this->page->indexUrl(PictureView::Decades));
        $this->assertSame(route('pictures'), $this->page->indexUrl());
    }

    #[Test]
    public function find_and_show_expose_ordered_photos_and_the_other_albums(): void
    {
        $album = Album::factory()->published()->withPhotos(3, 64, 64)->create(['slug' => 'field']);
        $other = Album::factory()->published()->create(['slug' => 'halls']);
        Album::factory()->draft()->create(['slug' => 'drafts']);

        $this->assertTrue($this->page->find('field')->is($album));
        $this->assertNull($this->page->find('drafts'));
        $this->assertNull($this->page->find('missing'));

        $data = $this->page->show($this->page->find('field'));

        $this->assertSame('pictures', $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertTrue($data['album']->is($album));
        $this->assertSame([1, 2, 3], array_map(fn (array $hung): int => $hung['photo']->order_column, $data['wall']));
        $this->assertSame([5, 5, 5], array_column($data['wall'], 'span'), '64x64 placeholders hang as squares');
        $this->assertNotNull($data['range']);
        $this->assertSame([$other->id], $data['albums']->pluck('id')->all());
        $this->assertSame(route('pictures.show', 'field'), $this->page->detailUrl($album));
    }
}
