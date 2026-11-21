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
    public function published_cv_entries_and_awards_are_grouped_into_decade_chapters_with_roman_numerals(): void
    {
        $this->entry(CvSection::Education, 'B.Sc.', 1958, 1962);
        $this->entry(CvSection::Appointment, 'Lecturer', 1969, 1975);
        Award::factory()->published()->create(['name' => 'Teaching Award', 'organisation' => 'University', 'year' => 1991]);
        Award::factory()->published()->create(['name' => 'Undated', 'year' => null]);
        CvEntry::factory()->published()->create(['title' => 'Undated entry', 'start_year' => null, 'end_year' => null]);
        CvEntry::factory()->published()->create(['section' => CvSection::Project, 'title' => 'End only', 'start_year' => null, 'end_year' => 1993]);

        $chapters = $this->service->chapters();

        $this->assertSame([1950, 1960, 1990], array_column($chapters, 'decade'));
        $this->assertSame(['I', 'II', 'III'], array_column($chapters, 'numeral'));
        $this->assertSame([1, 2, 3], array_column($chapters, 'number'));
        $this->assertSame(['1950–1959', '1960–1969', '1990–1999'], array_column($chapters, 'years'));
        $this->assertSame(['chapter-1950s', 'chapter-1960s', 'chapter-1990s'], array_column($chapters, 'id'));

        $this->assertSame([
            'year' => 1958, 'years' => '1958–1962', 'title' => 'B.Sc.', 'detail' => 'University of Khartoum',
            'kind' => CvSection::Education->value, 'label' => 'Education', 'lang' => '',
        ], $chapters[0]['milestones'][0]);
        $this->assertSame(['Teaching Award', 'End only'], array_column($chapters[2]['milestones'], 'title'));
        $this->assertSame('Award', $chapters[2]['milestones'][0]['label']);
        $this->assertSame('1993', $chapters[2]['milestones'][1]['years']);
        $this->assertStringNotContainsString('Undated', json_encode(array_column($chapters, 'milestones')));
    }

    #[Test]
    public function a_crowded_decade_keeps_its_three_most_telling_milestones_in_date_order(): void
    {
        $this->entry(CvSection::Project, 'Project', 1980, 1985);
        $this->entry(CvSection::Achievement, 'Achievement', 1981, 1981);
        $this->entry(CvSection::Leadership, 'Dean', 1988, 1994);
        $this->entry(CvSection::Appointment, 'Professor', 1982, 2005);
        Award::factory()->published()->create(['name' => 'Order of Merit', 'year' => 1986]);

        $milestones = $this->service->chapters()[0]['milestones'];

        $this->assertCount(HomeTimeline::MILESTONES_PER_CHAPTER, $milestones);
        $this->assertSame(['Professor', 'Order of Merit', 'Dean'], array_column($milestones, 'title'));
        $this->assertSame([1982, 1986, 1988], array_column($milestones, 'year'));
    }

    #[Test]
    public function each_chapter_takes_an_unused_archive_photo_from_its_decade(): void
    {
        $this->entry(CvSection::Education, 'B.Sc.', 1962, 1965);
        $this->entry(CvSection::Appointment, 'Lecturer', 1966, 1969);
        $this->entry(CvSection::Appointment, 'Professor', 1982, 2005);

        $album = Album::factory()->published()->create();
        $first = $this->photo($album, 1964);
        $second = $this->photo($album, 1968);
        $this->photo($album, 1975);
        $this->photo($album, 1984, MediaTreatment::Portrait);
        $this->photo(Album::factory()->draft()->create(), 1985);

        $chapters = $this->service->chapters();

        $this->assertSame($first->id, $chapters[0]['photo']?->id);
        $this->assertNull($chapters[1]['photo'], 'Portraits and photos in draft albums are never used');
        $this->assertCount(3, $this->service->archivePhotos());
        $this->assertNotSame($second->id, $chapters[1]['photo']?->id);
    }

    #[Test]
    public function photos_offered_to_the_timeline_replace_the_other_archive_photos(): void
    {
        $this->entry(CvSection::Education, 'B.Sc.', 1959, 1959);
        $this->entry(CvSection::Appointment, 'Lecturer', 1984, 1990);

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
