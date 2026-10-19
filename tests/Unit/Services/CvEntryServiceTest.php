<?php

namespace Tests\Unit\Services;

use App\Constants\CvSection;
use App\Models\CvEntry;
use App\Services\CvEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CvEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CvEntryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CvEntryService::class);
    }

    #[Test]
    public function published_by_section_groups_in_section_order_and_omits_empty_sections(): void
    {
        CvEntry::factory()->published()->section(CvSection::Project)->create(['sort_order' => 1]);
        CvEntry::factory()->published()->section(CvSection::Education)->create(['sort_order' => 2, 'start_year' => 1960]);
        CvEntry::factory()->published()->section(CvSection::Education)->create(['sort_order' => 1, 'start_year' => 1970]);
        CvEntry::factory()->draft()->section(CvSection::Leadership)->create();

        $grouped = $this->service->publishedBySection();

        $this->assertSame([CvSection::Education->value, CvSection::Project->value], array_keys($grouped));
        $this->assertCount(2, $grouped[CvSection::Education->value]);
        $this->assertSame([1970, 1960], $grouped[CvSection::Education->value]->pluck('start_year')->all());
    }

    #[Test]
    public function latest_update_reads_the_newest_published_date(): void
    {
        $this->assertNull($this->service->latestUpdate());

        CvEntry::factory()->published()->create(['last_updated_at' => '2020-01-01']);
        CvEntry::factory()->published()->create(['last_updated_at' => '2023-05-05']);
        CvEntry::factory()->draft()->create(['last_updated_at' => '2024-09-09']);

        $this->assertSame('2023-05-05', $this->service->latestUpdate()->toDateString());
    }

    #[Test]
    public function prepare_casts_section_strings_and_blank_end_years(): void
    {
        $entry = $this->service->create([
            'title' => 'Lecturer',
            'section' => 'appointment',
            'start_year' => 1969,
            'end_year' => '',
        ]);

        $this->assertSame(CvSection::Appointment, $entry->section);
        $this->assertNull($entry->end_year);
        $this->assertTrue($entry->isCurrent());
    }
}
