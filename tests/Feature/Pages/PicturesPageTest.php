<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Constants\MediaConversions;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PicturesPageTest extends TestCase
{
    use RefreshDatabase;

    private AlbumService $albums;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->albums = $this->app->make(AlbumService::class);
    }

    #[Test]
    public function the_index_renders_an_empty_state_without_published_albums(): void
    {
        Album::factory()->draft()->create(['title' => 'Draft album']);

        $this->get(route('pictures'))
            ->assertOk()
            ->assertSee('<title>Pictures · ', false)
            ->assertSeeInOrder(['section-header', 'Pictures', 'rule-brass', 'id="section-title"', 'Pictures'], false)
            ->assertSee('Photographs will be gathered into albums here once they are published.')
            ->assertDontSee('Draft album')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function the_index_shows_each_album_with_its_cover_count_title_and_description(): void
    {
        $field = Album::factory()->published()->withPhotos(3, 64, 48)->create(['title' => 'In the field', 'slug' => 'field', 'description' => 'Research stations across the decades.', 'sort_order' => 1]);
        Album::factory()->published()->create(['title' => 'Empty album', 'slug' => 'empty', 'description' => null, 'sort_order' => 2]);
        $this->albums->setCover($field, $field->photos()->last());
        $cover = $field->fresh()->cover();

        $this->get(route('pictures'))
            ->assertOk()
            ->assertSeeInOrder([
                'aria-label="View"', '3 photographs in 2 albums',
                'href="'.route('pictures').'" class="filter-pill" aria-current="true">Albums', 'href="'.route('pictures', ['view' => 'decades']).'" class="filter-pill">Through the decades',
                'class="exhibition mt-16" data-rise-group',
                'exhibition__item', 'href="'.route('pictures.show', 'field').'" class="exhibition__cover"',
                'photo__frame--lift', 'aspect-ratio: 64 / 48', 'class="photo__under', 'srcset="'.$cover->getUrl(MediaConversions::SMALL).' 480w', 'width="64" height="48"', 'photo-archive', 'loading="lazy"',
                'exhibition__title', 'In the field', 'exhibition__meta smallcaps', '3 photographs', 'Research stations across the decades.',
                'href="'.route('pictures.show', 'empty').'"', 'media-card__placeholder', 'Empty album', '0 photographs',
            ], false)
            ->assertDontSee('<figcaption', false)
            ->assertDontSee('Photographs will be gathered into albums here');
    }

    #[Test]
    public function an_album_page_renders_the_captioned_grid_and_the_lightbox(): void
    {
        $album = Album::factory()->published()->create(['title' => 'In the field', 'slug' => 'field', 'description' => 'Research stations across the decades.']);
        Album::factory()->published()->create(['title' => 'Lecture halls', 'slug' => 'halls']);
        Album::factory()->draft()->create(['title' => 'Drafts', 'slug' => 'drafts']);

        $this->albums->addPhoto($album, PlaceholderImage::grey(64, 48), [
            MediaProperties::ALT_TEXT => 'Standing in a sorghum field', MediaProperties::CAPTION => 'Gezira research station',
            MediaProperties::PLACE => 'Wad Madani', MediaProperties::YEAR => 1972, MediaProperties::NOTE => 'With the first cohort.', MediaProperties::CREDIT => 'Family archive',
        ]);
        $this->albums->addPhoto($album, PlaceholderImage::grey(48, 64), [
            MediaProperties::ALT_TEXT => 'Portrait at the gate', MediaProperties::TREATMENT => MediaTreatment::Portrait, MediaProperties::YEAR => 1980,
        ]);
        [$first, $second] = $album->fresh()->photos()->all();

        $response = $this->get(route('pictures.show', 'field'))->assertOk();

        $response
            ->assertSee('<title>In the field · ', false)
            ->assertSee('<meta name="description" content="Research stations across the decades.">', false)
            ->assertSeeInOrder([
                'section-header', 'Pictures', 'id="section-title"', 'In the field', 'Research stations across the decades.',
                'exhibition__meta smallcaps', '<span>1972–1980</span>', '2 photographs',
                'x-data="lightbox"', 'class="wall mt-16" data-rise-group aria-label="Photographs in In the field"',
                'class="wall__item" style="--span: 6"',
                'href="'.$first->getUrl(MediaConversions::LARGE).'"', 'photo__link', 'data-lightbox-item',
                'data-lightbox-src="'.$first->getUrl(MediaConversions::LARGE).'"',
                'data-lightbox-srcset="'.$first->getUrl(MediaConversions::SMALL).' 480w',
                'data-lightbox-alt="Standing in a sorghum field"', 'data-lightbox-treatment="photo-archive"',
                'data-lightbox-caption="Gezira research station"', 'data-lightbox-meta="Wad Madani, 1972"',
                'data-lightbox-note="With the first cohort."', 'data-lightbox-credit="Family archive"',
                'data-lightbox-width="64" data-lightbox-height="48"',
                'photo__frame--lift', 'aspect-ratio: 64 / 48', 'class="photo__under h-full w-full object-cover"',
                'alt="Standing in a sorghum field"', 'width="64" height="48"', 'class="photo-archive h-full w-full object-cover"', 'loading="lazy"',
                'View larger', '<figcaption class="museum-label">', 'museum-label__title">Gezira research station', 'museum-label__meta smallcaps">Wad Madani, 1972', 'museum-label__note">With the first cohort.',
                'class="wall__item" style="--span: 4"',
                'href="'.$second->getUrl(MediaConversions::LARGE).'"', 'data-lightbox-alt="Portrait at the gate"', 'data-lightbox-treatment="photo-portrait"',
                'data-lightbox-meta="1980"', 'alt="Portrait at the gate"', 'photo-portrait', 'museum-label__meta smallcaps">1980',
                '<dialog', 'x-ref="dialog"', 'class="lightbox on-dark"', 'aria-label="In the field"', '@keydown="onKeydown($event)"', '@touchstart.passive', '@touchend.passive',
                'aria-live="polite"', 'x-ref="close"', 'Close', 'x-ref="image"', 'sizes="100vw"', 'museum-label museum-label--ivory', 'x-text="current.credit"', 'Previous photograph', 'Next photograph',
                'aria-label="More albums"', 'href="'.route('pictures').'"', 'All albums', 'href="'.route('pictures', ['view' => 'decades']).'"', 'Through the decades', 'href="'.route('pictures.show', 'halls').'"', 'Lecture halls',
            ], false)
            ->assertDontSee('Drafts')
            ->assertDontSee('livewire', false);

        $html = $response->getContent();
        $this->assertStringNotContainsString('data-lightbox-caption', substr($html, strpos($html, 'data-lightbox-alt="Portrait at the gate"')));
        $this->assertStringNotContainsString('photo__under', substr($html, strpos($html, 'class="wall__item" style="--span: 4"')), 'Portraits keep the warm monochrome without a lift layer');
        $this->assertStringNotContainsString('href="'.route('pictures.show', 'field').'"', substr($html, strpos($html, 'aria-label="More albums"')), 'The album does not link to itself');
    }

    #[Test]
    public function an_album_without_photos_shows_an_empty_state_and_no_lightbox(): void
    {
        Album::factory()->published()->create(['slug' => 'empty', 'title' => 'Empty album', 'description' => null]);
        Album::factory()->draft()->create(['slug' => 'drafts']);

        $this->get(route('pictures.show', 'empty'))
            ->assertOk()
            ->assertSee('<meta name="description" content="Captioned photographs grouped in albums.">', false)
            ->assertSee('This album has no photographs yet.')
            ->assertDontSee('<dialog', false)
            ->assertDontSee('x-data="lightbox"', false);

        $this->get(route('pictures.show', 'drafts'))->assertNotFound();
        $this->get(route('pictures.show', 'missing'))->assertNotFound();
    }

    #[Test]
    public function the_pictures_section_is_marked_current_on_index_and_album_pages(): void
    {
        Album::factory()->published()->create(['slug' => 'field']);

        foreach ([route('pictures'), route('pictures.show', 'field')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);
            $this->assertSame([route('pictures')], array_values(array_unique($matches[1])));
        }
    }
}
