<?php

namespace Tests\Unit\Services;

use App\Constants\CvSection;
use App\Models\CvEntry;
use App\Services\CvPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CvPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private CvPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CvPageService::class);
    }

    #[Test]
    public function it_builds_groups_in_section_order_with_counts_anchors_and_the_latest_update(): void
    {
        CvEntry::factory()->published()->section(CvSection::Project)->create(['last_updated_at' => '2021-01-01']);
        CvEntry::factory()->published()->section(CvSection::Education)->count(2)->create(['last_updated_at' => '2023-05-05']);
        CvEntry::factory()->draft()->section(CvSection::Leadership)->create(['last_updated_at' => '2025-01-01']);

        $data = $this->service->data();

        $this->assertSame('cv', $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertSame('2023-05-05', $data['lastUpdated']->toDateString());
        $this->assertSame(3, $data['total']);
        $this->assertSame(['education', 'project'], array_column($data['groups'], 'key'));
        $this->assertSame(['Education', 'Projects'], array_column($data['summary'], 'label'));
        $this->assertSame([2, 1], array_column($data['summary'], 'count'));
        $this->assertSame(['cv-education', 'cv-project'], array_column($data['summary'], 'anchor'));
        $this->assertCount(2, $data['groups'][0]['entries']);
        $this->assertArrayNotHasKey('entries', $data['summary'][0]);
    }

    #[Test]
    public function it_returns_no_groups_and_no_update_date_when_nothing_is_published(): void
    {
        CvEntry::factory()->draft()->create(['last_updated_at' => '2025-01-01']);

        $data = $this->service->data();

        $this->assertSame([], $data['groups']);
        $this->assertSame([], $data['summary']);
        $this->assertNull($data['lastUpdated']);
        $this->assertSame(0, $data['total']);
    }
}
