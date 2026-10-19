<?php

namespace Tests\Unit\Services;

use App\Models\OtherItem;
use App\Services\OtherPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtherPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtherPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(OtherPageService::class);
    }

    #[Test]
    public function the_index_groups_published_items_by_category_with_uncategorised_last(): void
    {
        OtherItem::factory()->published()->category('In the press')->create();
        OtherItem::factory()->published()->category('Announcements')->count(2)->create();
        OtherItem::factory()->published()->create(['category' => null]);
        OtherItem::factory()->draft()->category('Announcements')->create();

        $data = $this->service->index();

        $this->assertSame('other', $data['section']['key']);
        $this->assertSame(['Announcements', 'In the press', OtherPageService::UNCATEGORISED_LABEL], array_column($data['categories'], 'label'));
        $this->assertSame(['category-announcements', 'category-in-the-press', 'category-further-information'], array_column($data['categories'], 'anchor'));
        $this->assertCount(2, $data['categories'][0]['items']);
        $this->assertCount(1, $data['categories'][2]['items']);
    }

    #[Test]
    public function find_returns_only_published_items_that_have_their_own_page(): void
    {
        $detail = OtherItem::factory()->published()->create(['slug' => 'detail', 'body' => '<p>Body</p>', 'destination_url' => null]);
        OtherItem::factory()->published()->create(['slug' => 'linked', 'body' => '<p>Body</p>', 'destination_url' => 'https://example.org']);
        OtherItem::factory()->published()->create(['slug' => 'bodiless', 'body' => null, 'destination_url' => null]);
        OtherItem::factory()->draft()->create(['slug' => 'draft', 'body' => '<p>Body</p>', 'destination_url' => null]);

        $this->assertTrue($this->service->find('detail')->is($detail));
        $this->assertNull($this->service->find('linked'));
        $this->assertNull($this->service->find('bodiless'));
        $this->assertNull($this->service->find('draft'));
        $this->assertSame(route('other.show', 'detail'), $this->service->detailUrl($detail));
    }

    #[Test]
    public function show_sanitises_the_body(): void
    {
        $item = OtherItem::factory()->published()->create(['body' => '<p>Text</p><iframe src="x"></iframe><h2>Heading</h2>']);

        $data = $this->service->show($item);

        $this->assertTrue($data['item']->is($item));
        $this->assertSame('<p>Text</p><h2>Heading</h2>', $data['body']);
        $this->assertTrue($data['section']['active']);
    }
}
