<?php

namespace Tests\Unit\Services;

use App\Constants\CvSection;
use App\Constants\Disks;
use App\Constants\HomeTimeline;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\Award;
use App\Models\CvEntry;
use App\Services\AlbumService;
use App\Services\HomeTimelineService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class HomeTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    private HomeTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(HomeTimelineService::class);
    }

    #[Test]
    public function nothing_published_means_no_chapters_and_no_years_of_teaching(): void
    {
        CvEntry::factory()->draft()->create(['start_year' => 1970]);
        Award::factory()->draft()->create(['year' => 1980]);

        $this->assertSame([], $this->service->chapters());
        $this->assertNull($this->service->yearsOfTeaching());
    }

    #[Test]
    public function published_awards_are_grouped_into_decade_chapters_with_roman_numerals_and_cv_entries_are_not(): void
    {
        $this->entry(CvSection::Education, 'B.Sc.', 1958, 1962);
        $this->entry(CvSection::Appointment, 'Lecturer', 1969, 1975);
        $this->award('Fellowship', 1968, 'Academy');
        $this->award('Teaching Award', 1991, 'University');
        $this->award('Medal', 1993);
        Award::factory()->published()->create(['name' => 'Undated', 'year' => null]);

        $chapters = $this->service->chapters();

        $this->assertSame([1960, 1990], array_column($chapters, 'decade'), 'Only decades with a dated honour; education and appointments stay on the CV page.');
        $this->assertSame(['I', 'II'], array_column($chapters, 'numeral'));
        $this->assertSame([1, 2], array_column($chapters, 'number'));
        $this->assertSame(['1960–1969', '1990–1999'], array_column($chapters, 'years'));
        $this->assertSame(['chapter-1960s', 'chapter-1990s'], array_column($chapters, 'id'));

        $this->assertSame([
            'year' => 1968, 'years' => '1968', 'title' => 'Fellowship', 'detail' => 'Academy',
            'kind' => HomeTimeline::KIND_AWARD, 'label' => 'Award', 'lang' => '',
        ], $chapters[0]['milestones'][0]);
        $this->assertSame(['Teaching Award', 'Medal'], array_column($chapters[1]['milestones'], 'title'));
        $this->assertStringNotContainsString('Undated', json_encode(array_column($chapters, 'milestones')));
        $this->assertStringNotContainsString('B.Sc.', json_encode(array_column($chapters, 'milestones')));
    }

    #[Test]
    public function a_crowded_decade_keeps_its_first_honours_in_date_order(): void
    {
        foreach ([1988, 1981, 1986, 1983] as $year) {
            $this->award('Award of '.$year, $year);
        }

        $milestones = $this->service->chapters()[0]['milestones'];

        $this->assertCount(HomeTimeline::MILESTONES_PER_CHAPTER, $milestones);
        $this->assertSame([1981, 1983, 1986], array_column($milestones, 'year'));
    }

    #[Test]
    public function each_chapter_takes_an_unused_archive_photo_from_its_decade(): void
    {
        $this->award('Prize', 1964);
        $this->award('Medal', 1968);
        $this->award('Order', 1982);

        $album = Album::factory()->published()->create();
        $first = $this->photo($album, 1964);
        $this->photo($album, 1975);
        $this->photo($album, 1984, MediaTreatment::Portrait);
        $this->photo(Album::factory()->draft()->create(), 1985);

        $chapters = $this->service->chapters();

        $this->assertSame($first->id, $chapters[0]['photo']?->id);
        $this->assertNull($chapters[1]['photo'], 'Portraits and photos in draft albums are never used');
        $this->assertCount(2, $this->service->archivePhotos());
    }

    #[Test]
    public function photos_offered_to_the_timeline_replace_the_other_archive_photos(): void
    {
        $this->award('Prize', 1959);
        $this->award('Shield', 1986);

        $album = Album::factory()->published()->create();
        $this->photo($album, 1959);
        $this->photo($album, 1985);
        $shield = $this->photo($album, 1986, MediaTreatment::Portrait);

        $this->app->make(AlbumService::class)->setOnTimeline($album, $shield, true);

        $chapters = $this->service->chapters();

        $this->assertNull($chapters[0]['photo'], 'Once a photo is offered, decades without an offered photo show none.');
        $this->assertSame($shield->id, $chapters[1]['photo']?->id, 'Offered photos are used whatever their treatment.');
        $this->assertCount(1, $this->service->archivePhotos());
    }

    #[Test]
    public function years_of_teaching_run_from_the_first_appointment_to_the_last_year_the_cv_records(): void
    {
        $this->entry(CvSection::Education, 'Ph.D.', 1966, 1969);
        $this->entry(CvSection::Appointment, 'Lecturer', 1969, 1975);
        $this->entry(CvSection::Appointment, 'Professor', 1982, 2005);
        $this->entry(CvSection::Appointment, 'Professor Emeritus', 2005, null);

        $this->assertSame(36, $this->service->yearsOfTeaching());
    }

    #[Test]
    public function an_open_ended_appointment_alone_gives_no_figure(): void
    {
        $this->entry(CvSection::Appointment, 'Lecturer', 1969, null);

        $this->assertNull($this->service->yearsOfTeaching());
    }

    #[Test]
    public function decades_start_on_the_round_year(): void
    {
        $this->assertSame(1960, $this->service->decadeOf(1960));
        $this->assertSame(1960, $this->service->decadeOf(1969));
        $this->assertSame(2000, $this->service->decadeOf(2005));
    }

    private function entry(CvSection $section, string $title, ?int $start, ?int $end): CvEntry
    {
        return CvEntry::factory()->published()->create([
            'section' => $section, 'title' => $title, 'organisation' => 'University of Khartoum',
            'start_year' => $start, 'end_year' => $end,
        ]);
    }

    private function award(string $name, int $year, ?string $organisation = null): Award
    {
        return Award::factory()->published()->create(['name' => $name, 'organisation' => $organisation, 'year' => $year, 'description' => null]);
    }

    private function photo(Album $album, int $year, MediaTreatment $treatment = MediaTreatment::Archive): Media
    {
        return $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(64, 48), [
            MediaProperties::ALT_TEXT => 'Photo from '.$year,
            MediaProperties::PLACE => 'Khartoum',
            MediaProperties::YEAR => $year,
            MediaProperties::TREATMENT => $treatment->value,
        ]);
    }
}
