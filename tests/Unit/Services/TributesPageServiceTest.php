<?php

namespace Tests\Unit\Services;

use App\Constants\PageSizes;
use App\Constants\Sections;
use App\Constants\SocialCards;
use App\Constants\Taglines;
use App\Constants\TributeForm;
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
    public function index_lists_approved_tributes_in_display_order_a_page_at_a_time_with_copy_filters_and_share_card(): void
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
        $this->assertNull($data['featured']);
        $this->assertNull($data['activeRelationship']);
        $this->assertSame(route('tributes'), $data['clearUrl']);
        $this->assertSame([
            ['value' => 'student', 'label' => 'Students', 'url' => route('tributes', ['relationship' => 'student']), 'active' => false],
            ['value' => 'colleague', 'label' => 'Colleagues', 'url' => route('tributes', ['relationship' => 'colleague']), 'active' => false],
            ['value' => 'family', 'label' => 'Family', 'url' => route('tributes', ['relationship' => 'family']), 'active' => false],
            ['value' => 'friend', 'label' => 'Friends', 'url' => route('tributes', ['relationship' => 'friend']), 'active' => false],
        ], $data['filters']);
        $this->assertSame([
            'label' => Taglines::TRIBUTES_LABEL,
            'heading' => Taglines::TRIBUTES_HEADING,
            'leaveTribute' => Taglines::LEAVE_TRIBUTE,
            'featuredLabel' => Taglines::TRIBUTES_FEATURED_LABEL,
            'bookLabel' => Taglines::TRIBUTES_BOOK_LABEL,
        ], $data['copy']);
        $this->assertSame(asset(SocialCards::TRIBUTES), $data['shareImage']);
        $this->assertSame(route('tributes.create'), $data['createUrl']);
    }

    #[Test]
    public function index_filters_by_relationship_and_marks_the_active_link(): void
    {
        Tribute::factory()->approved()->relationship(TributeRelationship::Student)->create(['name' => 'Student A']);
        Tribute::factory()->approved()->relationship(TributeRelationship::Family)->create(['name' => 'Family B']);

        $data = $this->service->index(TributeRelationship::Family);

        $this->assertSame(['Family B'], $data['tributes']->pluck('name')->all());
        $this->assertSame(TributeRelationship::Family, $data['activeRelationship']);
        $this->assertSame([false, false, true, false], array_column($data['filters'], 'active'));
        $this->assertSame(route('tributes', ['relationship' => 'family']), $this->service->url(TributeRelationship::Family));
        $this->assertSame(route('tributes'), $this->service->url());
    }

    #[Test]
    public function the_featured_tribute_opens_the_first_page_only_and_is_left_out_of_the_pages(): void
    {
        $featured = Tribute::factory()->featured()->relationship(TributeRelationship::Student)->create(['name' => 'Featured', 'sort_order' => 1]);
        Tribute::factory()->approved()->relationship(TributeRelationship::Colleague)->count(PageSizes::TRIBUTES)->create(['sort_order' => 2]);

        $data = $this->service->index();
        $this->assertTrue($data['featured']?->is($featured));
        $this->assertNotContains('Featured', $data['tributes']->pluck('name')->all());
        $this->assertSame(PageSizes::TRIBUTES, $data['tributes']->total());

        // Page two never repeats the frontispiece.
        request()->merge(['page' => 2]);
        $this->assertNull($this->service->index()['featured']);
        request()->merge(['page' => 1]);

        // Under a filter it belongs to, it still opens the book; under another it does not.
        $this->assertTrue($this->service->index(TributeRelationship::Student)['featured']?->is($featured));
        $this->assertSame(0, $this->service->index(TributeRelationship::Student)['tributes']->total());
        $this->assertNull($this->service->index(TributeRelationship::Colleague)['featured']);
        $this->assertSame(route('tributes.card', $featured), $this->service->cardUrl($featured));
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
    public function create_provides_the_first_step_with_hints_options_limit_and_urls(): void
    {
        $data = $this->service->create();

        $this->assertSame(Sections::TRIBUTES, $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertSame(Taglines::LEAVE_TRIBUTE, $data['title']);
        $this->assertSame(TributeForm::STEP_WRITE, $data['step']);
        $this->assertSame(TributeForm::steps(), $data['steps']);
        $this->assertSame(TributeForm::INTRO, $data['intro']);
        $this->assertSame(TributeForm::fields(), $data['fields']);
        $this->assertSame([
            'student' => 'Student', 'colleague' => 'Colleague', 'family' => 'Family', 'friend' => 'Friend', 'other' => 'Other',
        ], $data['relationships']);
        $this->assertSame(array_map(fn (TributeRelationship $r): string => $r->value, TributeRelationship::cases()), array_keys($data['relationships']));
        $this->assertSame(Tribute::MESSAGE_MAX_LENGTH, $data['messageMaxLength']);
        $this->assertSame(route('tributes.preview'), $data['previewUrl']);
        $this->assertSame(route('tributes'), $data['indexUrl']);
    }

    #[Test]
    public function preview_builds_the_tribute_exactly_as_it_will_appear(): void
    {
        $data = $this->service->preview([
            'name' => '  Ahmed El-Sheikh ', 'relationship' => 'student', 'location' => ' ', 'message' => ' He taught me to look twice. ', 'email' => 'a@example.com',
        ]);

        $this->assertSame(TributeForm::STEP_PREVIEW, $data['step']);
        $this->assertSame(TributeForm::PREVIEW_INTRO, $data['intro']);
        $this->assertSame(TributeForm::CONSENT, $data['consent']);
        $this->assertSame('Ahmed El-Sheikh', $data['tribute']->name);
        $this->assertSame(TributeRelationship::Student, $data['tribute']->relationship);
        $this->assertNull($data['tribute']->location);
        $this->assertSame('He taught me to look twice.', $data['tribute']->message);
        $this->assertFalse($data['tribute']->exists);
        $this->assertSame('a@example.com', $data['data']['email']);
        $this->assertSame(route('tributes.store'), $data['storeUrl']);
        $this->assertSame(route('tributes.edit'), $data['editUrl']);
        $this->assertTrue($data['section']['active']);
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
