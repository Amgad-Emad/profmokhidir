<?php

namespace Tests\Unit\Repositories;

use App\Constants\CvSection;
use App\Models\CvEntry;
use App\Repositories\Contracts\CvEntryRepositoryInterface;
use App\Repositories\Eloquent\EloquentCvEntryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentCvEntryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CvEntryRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(CvEntryRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentCvEntryRepository::class, $this->repository);
    }

    #[Test]
    public function it_lists_published_entries_in_a_section(): void
    {
        CvEntry::factory()->published()->section(CvSection::Education)->count(2)->create();
        CvEntry::factory()->draft()->section(CvSection::Education)->create();
        CvEntry::factory()->published()->section(CvSection::Project)->create();

        $education = $this->repository->publishedInSection(CvSection::Education);

        $this->assertCount(2, $education);
        $this->assertTrue($education->every(fn (CvEntry $entry): bool => $entry->section === CvSection::Education));
    }

    #[Test]
    public function published_entries_are_ordered_by_sort_order_then_start_year_desc(): void
    {
        $late = CvEntry::factory()->published()->sortOrder(2)->create(['start_year' => 2010]);
        $earlyOld = CvEntry::factory()->published()->sortOrder(1)->create(['start_year' => 1970]);
        $earlyNew = CvEntry::factory()->published()->sortOrder(1)->create(['start_year' => 1990]);

        $this->assertSame(
            [$earlyNew->id, $earlyOld->id, $late->id],
            $this->repository->published()->pluck('id')->all(),
        );
    }

    #[Test]
    public function latest_update_reflects_published_entries_only(): void
    {
        $this->assertNull($this->repository->latestUpdate());

        CvEntry::factory()->published()->create(['last_updated_at' => '2020-01-10']);
        CvEntry::factory()->published()->create(['last_updated_at' => '2023-05-01']);
        CvEntry::factory()->draft()->create(['last_updated_at' => '2025-01-01']);

        $this->assertSame('2023-05-01', $this->repository->latestUpdate()?->toDateString());
    }

    #[Test]
    public function search_matches_a_term_and_filters_by_section(): void
    {
        CvEntry::factory()->section(CvSection::Education)->create(['title' => 'PhD in Crop Physiology']);
        CvEntry::factory()->section(CvSection::Appointment)->create(['title' => 'Professor of Crop Science', 'organisation' => 'Khartoum']);
        CvEntry::factory()->section(CvSection::Leadership)->create(['title' => 'Dean', 'organisation' => 'Faculty']);

        $this->assertSame(2, $this->repository->search('Crop')->total());
        $this->assertSame(1, $this->repository->search('Crop', ['section' => CvSection::Appointment])->total());
        $this->assertSame(1, $this->repository->search(null, ['section' => 'leadership'])->total());
        $this->assertSame(1, $this->repository->search('Khartoum')->total());
    }
}
