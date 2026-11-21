<?php

namespace Tests\Feature\Database;

use App\Constants\Locales;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\VideoProvider;
use App\Models\Album;
use App\Models\Award;
use App\Models\CvEntry;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Video;
use Database\Seeders\ProfileSeeder;
use Database\Seeders\StakeholderArchiveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The stakeholders' archive lands in its sections, in both languages, and
 * retires the placeholders it replaces. Image conversions are left queued.
 */
class StakeholderArchiveSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_archive_is_imported_into_every_section_and_replaces_the_placeholders(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        Queue::fake();

        $this->seed(ProfileSeeder::class);
        $placeholder = Award::factory()->published()->create(['name' => '[Placeholder] Old award']);
        $real = Award::factory()->published()->create(['name' => 'An award an editor added', 'description' => null]);

        $this->seed(StakeholderArchiveSeeder::class);

        $manifest = StakeholderArchiveSeeder::manifest();

        $this->assertSoftDeleted($placeholder);
        $this->assertNotSoftDeleted($real);
        $this->assertSame(count($manifest['awards']) + 1, Award::query()->count());
        $this->assertSame(count($manifest['albums']), Album::query()->published()->count());
        $this->assertSame(count($manifest['cv']), CvEntry::query()->published()->count());
        $this->assertSame(count($manifest['publications']), Publication::query()->published()->count());
        $this->assertSame(count($manifest['teaching']['materials']), TeachingMaterial::query()->published()->count());
        $this->assertSame(count($manifest['videos']), Video::query()->published()->count());

        $album = Album::query()->orderBy('sort_order')->firstOrFail();
        $this->assertSame('الدكتوراه الفخرية من جامعة الأحفاد للبنات', $album->getTranslation('title', Locales::AR));
        $this->assertCount(count($manifest['albums'][0]['photos']), $album->photos());
        $this->assertSame('ahfad-2019-portrait.jpg', $album->cover()?->file_name);
        $this->assertSame('Omdurman', $album->cover()->getCustomProperty(MediaProperties::PLACE));
        $this->assertSame('أم درمان', $album->cover()->getCustomProperty(MediaProperties::translationPath(Locales::AR, MediaProperties::PLACE)));

        $portrait = Profile::query()->firstOrFail()->getFirstMedia(MediaCollections::PORTRAIT);
        $this->assertSame('ahfad-2019-portrait.jpg', $portrait?->file_name);

        Publication::query()->get()->each(fn (Publication $paper) => $this->assertNotNull($paper->getFirstMedia(MediaCollections::FILE), $paper->title));
        TeachingMaterial::query()->get()->each(fn (TeachingMaterial $material) => $this->assertNotNull($material->getFirstMedia(MediaCollections::FILE), $material->title));

        $ceremony = Video::query()->where('provider', VideoProvider::File->value)->orderBy('sort_order')->firstOrFail();
        $this->assertTrue($ceremony->is_featured);
        $this->assertNotNull($ceremony->videoFile());
        $this->assertNotNull($ceremony->thumbnail());

        $this->assertFileExists(StakeholderArchiveSeeder::ARCHIVE_PATH.'/photos/ahfad-2019-portrait.jpg', 'The archive files are copied, never moved.');
    }

    #[Test]
    public function running_the_import_twice_adds_nothing_the_second_time(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        Queue::fake();

        $this->seed(StakeholderArchiveSeeder::class);
        $this->assertSame('محمد عثمان خضر', Profile::query()->firstOrFail()->getTranslation('name', Locales::AR), 'A new site gets its profile from the archive.');
        $awards = Award::query()->count();
        $albums = Album::query()->count();

        $this->seed(StakeholderArchiveSeeder::class);

        $this->assertSame($awards, Award::query()->count());
        $this->assertSame($albums, Album::query()->count());
    }

    #[Test]
    public function running_it_again_on_an_earlier_import_offers_the_award_photos_to_the_home_timeline(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        Queue::fake();

        $this->seed(StakeholderArchiveSeeder::class);

        // An import made before the timeline flags existed.
        foreach (\App\Models\Media::query()->where('collection_name', MediaCollections::PHOTOS)->get() as $photo) {
            $photo->forgetCustomProperty(MediaProperties::ON_TIMELINE);
            $photo->save();
        }
        app(\App\Services\AlbumService::class)->flushCache();
        $chapters = collect(app(\App\Services\HomeTimelineService::class)->chapters())->keyBy('decade');
        $this->assertSame('ahfad-2019-certificate.jpg', $chapters[2010]['photo']?->file_name, 'Before: the 2010s show a scanned certificate.');

        $this->seed(StakeholderArchiveSeeder::class);

        $chapters = collect(app(\App\Services\HomeTimelineService::class)->chapters())->keyBy('decade');
        $this->assertSame([1980, 1990, 2000, 2010], $chapters->keys()->all(), 'Only decades with a dated honour.');
        $this->assertSame('honour-1986-yarmouk-shield.jpg', $chapters[1980]['photo']?->file_name);
        $this->assertSame('ahfad-2019-conferral.jpg', $chapters[2010]['photo']?->file_name, 'After: the photograph of the honour itself.');
    }
}
