<?php

namespace Tests\Feature\Components;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaConversions;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\Profile;
use App\Services\MediaService;
use App\Services\ProfileService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\AlbumService;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function a_portrait_renders_responsive_webp_sources_the_treatment_and_a_place_year_caption(): void
    {
        $media = $this->portrait([
            MediaProperties::ALT_TEXT => 'Portrait of the professor',
            MediaProperties::CAPTION => 'In his office',
            MediaProperties::PLACE => 'Khartoum',
            MediaProperties::YEAR => 1998,
        ]);

        $html = Blade::render('<x-photo :media="$media" />', ['media' => $media]);

        $this->assertStringContainsString('<figure class="photo flex flex-col gap-3">', $html);
        $this->assertStringContainsString('src="'.$media->getUrl(MediaConversions::LARGE).'"', $html);
        $this->assertStringContainsString(sprintf(
            'srcset="%s 480w, %s 960w, %s 1600w"',
            $media->getUrl(MediaConversions::SMALL),
            $media->getUrl(MediaConversions::MEDIUM),
            $media->getUrl(MediaConversions::LARGE),
        ), $html);
        $this->assertStringContainsString('sizes="100vw"', $html);
        $this->assertStringContainsString('alt="Portrait of the professor"', $html);
        $this->assertStringContainsString('class="photo-portrait h-full w-full object-cover"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringNotContainsString('fetchpriority', $html);
        $this->assertStringContainsString('<span class="t-label text-bronze">Khartoum, 1998</span>', $html);
        $this->assertStringNotContainsString('In his office', $html);
    }

    #[Test]
    public function the_caption_text_tone_eager_loading_and_aspect_are_opt_in(): void
    {
        $media = $this->portrait([
            MediaProperties::ALT_TEXT => 'Portrait',
            MediaProperties::CAPTION => 'In his office',
            MediaProperties::PLACE => 'Khartoum',
            MediaProperties::YEAR => 1998,
        ]);

        $html = Blade::render(
            '<x-photo :media="$media" tone="ivory" loading="eager" :show-caption="true" aspect="4 / 5" sizes="40vw" class="extra" />',
            ['media' => $media],
        );

        $this->assertStringContainsString('<figure class="photo flex flex-col gap-3 extra">', $html);
        $this->assertStringContainsString('style="aspect-ratio: 4 / 5"', $html);
        $this->assertStringContainsString('sizes="40vw"', $html);
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
        $this->assertStringContainsString('<span class="text-base font-light text-champagne">In his office</span>', $html);
        $this->assertStringContainsString('<span class="t-label text-brass">Khartoum, 1998</span>', $html);
    }

    #[Test]
    public function it_omits_the_caption_when_place_and_year_are_missing_and_handles_a_lone_year(): void
    {
        $media = $this->portrait([MediaProperties::ALT_TEXT => 'Portrait']);

        $this->assertStringNotContainsString('<figcaption', Blade::render('<x-photo :media="$media" />', ['media' => $media]));

        $media = $this->portrait([MediaProperties::ALT_TEXT => 'Portrait', MediaProperties::YEAR => 1974]);

        $this->assertStringContainsString('<span class="t-label text-bronze">1974</span>', Blade::render('<x-photo :media="$media" />', ['media' => $media]));
    }

    #[Test]
    public function archive_photos_get_the_duotone_treatment(): void
    {
        $album = Album::factory()->create();
        $media = $this->app->make(MediaService::class)->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, [
            MediaProperties::ALT_TEXT => 'Archive',
            MediaProperties::TREATMENT => MediaTreatment::Archive,
            MediaProperties::PLACE => 'Omdurman',
            MediaProperties::YEAR => 1962,
        ])->refresh();

        $html = Blade::render('<x-photo :media="$media" />', ['media' => $media]);

        $this->assertStringContainsString('class="photo-archive h-full w-full object-cover"', $html);
        $this->assertStringContainsString('Omdurman, 1962', $html);
    }

    #[Test]
    public function the_recorded_size_reserves_the_frame_and_captions_can_be_switched_off(): void
    {
        $media = $this->portrait([MediaProperties::ALT_TEXT => 'Portrait', MediaProperties::PLACE => 'Khartoum', MediaProperties::YEAR => 1998]);
        $html = Blade::render('<x-photo :media="$media" />', ['media' => $media]);

        $this->assertStringContainsString('style="aspect-ratio: 64 / 64"', $html);
        $this->assertStringContainsString('width="64" height="64"', $html);

        $html = Blade::render('<x-photo :media="$media" aspect="4 / 5" :captioned="false" />', ['media' => $media]);

        $this->assertStringContainsString('style="aspect-ratio: 4 / 5"', $html);
        $this->assertStringNotContainsString('<figcaption', $html);
        $this->assertStringNotContainsString('Khartoum', $html);
    }

    #[Test]
    public function a_lightbox_photo_wraps_the_frame_in_a_link_carrying_its_data(): void
    {
        $media = $this->portrait([
            MediaProperties::ALT_TEXT => 'Portrait "of" him', MediaProperties::CAPTION => 'In his office', MediaProperties::PLACE => 'Khartoum', MediaProperties::YEAR => 1998,
        ]);
        $html = Blade::render('<x-photo :media="$media" :lightbox="true" />', ['media' => $media]);

        $this->assertMatchesRegularExpression('/<a\s+href="'.preg_quote($media->getUrl(MediaConversions::LARGE), '/').'"\s+class="photo__link block rounded-card"\s+data-lightbox-item/', $html);
        $this->assertStringContainsString('data-lightbox-src="'.$media->getUrl(MediaConversions::LARGE).'"', $html);
        $this->assertStringContainsString('data-lightbox-srcset="'.$media->getUrl(MediaConversions::SMALL).' 480w', $html);
        $this->assertStringContainsString('data-lightbox-alt="Portrait &quot;of&quot; him"', $html);
        $this->assertStringContainsString('data-lightbox-treatment="photo-portrait"', $html);
        $this->assertStringContainsString('data-lightbox-caption="In his office"', $html);
        $this->assertStringContainsString('data-lightbox-meta="Khartoum, 1998"', $html);
        $this->assertStringContainsString('data-lightbox-width="64" data-lightbox-height="64"', $html);
        $this->assertStringContainsString('<span class="sr-only">View larger</span>', $html);
        $this->assertMatchesRegularExpression('/<\/a>\s*<figcaption/', $html);

        $this->assertStringNotContainsString('data-lightbox', Blade::render('<x-photo :media="$media" />', ['media' => $media]));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function portrait(array $properties): Media
    {
        $profile = Profile::factory()->create();

        // The sync conversion job records its results on its own instance, so re-read the row.
        return $this->app->make(ProfileService::class)->setPortrait($profile, PlaceholderImage::grey(64, 64), $properties)->refresh();
    }

    #[Test]
    public function a_wall_photo_sets_a_museum_label_and_an_archive_duotone_can_lift(): void
    {
        $album = Album::factory()->create();
        $media = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(64, 48), [
            MediaProperties::ALT_TEXT => 'Standing in a field', MediaProperties::CAPTION => 'Gezira research station', MediaProperties::PLACE => 'Wad Madani',
            MediaProperties::YEAR => 1972, MediaProperties::NOTE => 'With the first cohort.', MediaProperties::CREDIT => 'Family archive',
        ]);

        $html = Blade::render('<x-photo :media="$media" :lightbox="true" :label="true" :lift="true" :show-caption="true" />', ['media' => $media]);

        $this->assertStringContainsString('data-lightbox-note="With the first cohort."', $html);
        $this->assertStringContainsString('data-lightbox-credit="Family archive"', $html);
        $this->assertStringContainsString('photo__frame overflow-hidden rounded-card photo__frame--lift bg-stone', $html);
        $this->assertMatchesRegularExpression('/<img src="[^"]+"[^>]*alt=""[^>]*class="photo__under h-full w-full object-cover"[^>]*aria-hidden="true">\s*<img/', $html, 'The untreated print sits beneath the duotone');
        $this->assertSame(2, substr_count($html, '<img'));
        $this->assertStringContainsString('<figcaption class="museum-label">', $html);
        $this->assertStringContainsString('<span class="museum-label__title">Gezira research station</span>', $html);
        $this->assertStringContainsString('<span class="museum-label__meta smallcaps">Wad Madani, 1972</span>', $html);
        $this->assertStringContainsString('<span class="museum-label__note">With the first cohort.</span>', $html);
        $this->assertStringNotContainsString('t-label', $html);

        // Portraits keep the warm monochrome: no lift layer, and the label only when asked for.
        $portrait = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(48, 64), [MediaProperties::TREATMENT => MediaTreatment::Portrait, MediaProperties::YEAR => 1980]);
        $html = Blade::render('<x-photo :media="$media" :lift="true" />', ['media' => $portrait]);

        $this->assertStringNotContainsString('photo__under', $html);
        $this->assertStringNotContainsString('photo__frame--lift', $html);
        $this->assertStringContainsString('<span class="t-label text-bronze">1980</span>', $html);
    }
}
