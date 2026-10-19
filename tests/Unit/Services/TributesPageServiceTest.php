<?php

namespace Tests\Unit\Services;

use App\Constants\PageSizes;
use App\Constants\Sections;
use App\Constants\SocialCards;
use App\Constants\Taglines;
use App\Constants\TributeRelationship;
use App\Models\Tribute;
use App\Services\TributesPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributesPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private TributesPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(TributesPageService::class);
    }

    #[Test]
    public function index_lists_approved_tributes_in_display_order_a_page_at_a_time_with_copy_and_share_card(): void
    {
        Tribute::factory()->approved()->create(['name' => 'Second', 'sort_order' => 2]);
        Tribute::factory()->approved()->create(['name' => 'First', 'sort_order' => 1]);
        Tribute::factory()->pending()->create(['name' => 'Pending']);
        Tribute::factory()->rejected()->create(['name' => 'Rejected']);

        $data = $this->service->index();

        $this->assertSame(Sections::TRIBUTES, $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertSame(['First', 'Second'], $data['tributes']->pluck('name')->all());
        $this->assertSame(PageSizes::TRIBUTES, $data['tributes']->perPage());
        $this->assertSame([
            'label' => Taglines::TRIBUTES_LABEL,
            'heading' => Taglines::TRIBUTES_HEADING,
            'leaveTribute' => Taglines::LEAVE_TRIBUTE,
        ], $data['copy']);
        $this->assertSame(asset(SocialCards::TRIBUTES), $data['shareImage']);
        $this->assertSame(route('tributes.create'), $data['createUrl']);
    }

    #[Test]
    public function index_paginates_at_the_configured_size(): void
    {
        Tribute::factory()->approved()->count(PageSizes::TRIBUTES + 1)->create();

        $tributes = $this->service->index()['tributes'];

        $this->assertSame(PageSizes::TRIBUTES, $tributes->count());
        $this->assertSame(PageSizes::TRIBUTES + 1, $tributes->total());
        $this->assertSame(2, $tributes->lastPage());
    }

    #[Test]
    public function create_provides_the_relationship_options_limit_and_urls(): void
    {
        $data = $this->service->create();

        $this->assertSame(Sections::TRIBUTES, $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertSame(Taglines::LEAVE_TRIBUTE, $data['title']);
        $this->assertSame([
            'student' => 'Student', 'colleague' => 'Colleague', 'family' => 'Family', 'friend' => 'Friend', 'other' => 'Other',
        ], $data['relationships']);
        $this->assertSame(array_map(fn (TributeRelationship $r): string => $r->value, TributeRelationship::cases()), array_keys($data['relationships']));
        $this->assertSame(Tribute::MESSAGE_MAX_LENGTH, $data['messageMaxLength']);
        $this->assertSame(route('tributes.store'), $data['storeUrl']);
        $this->assertSame(route('tributes'), $data['indexUrl']);
    }

    #[Test]
    public function thanks_carries_the_confirmation_line(): void
    {
        $data = $this->service->thanks();

        $this->assertSame(Taglines::TRIBUTE_THANKS, $data['message']);
        $this->assertTrue($data['section']['active']);
        $this->assertSame(route('tributes'), $data['indexUrl']);
    }
}
