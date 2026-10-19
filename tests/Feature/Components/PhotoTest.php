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

    /**
     * @param  array<string, mixed>  $properties
     */
    private function portrait(array $properties): Media
    {
        $profile = Profile::factory()->create();

        // The sync conversion job records its results on its own instance, so re-read the row.
        return $this->app->make(ProfileService::class)->setPortrait($profile, PlaceholderImage::grey(64, 64), $properties)->refresh();
    }
}
