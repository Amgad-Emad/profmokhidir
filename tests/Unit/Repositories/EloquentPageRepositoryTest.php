<?php

namespace Tests\Unit\Repositories;

use App\Constants\PageBlockType;
use App\Constants\Pages;
use App\Models\Page;
use App\Models\PageBlock;
use App\Repositories\Contracts\PageRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PageRepositoryInterface $pages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pages = $this->app->make(PageRepositoryInterface::class);
    }

    #[Test]
    public function it_finds_and_creates_pages_by_key_in_order(): void
    {
        Page::factory()->page(Pages::ABOUT)->create();
        $home = $this->pages->firstOrCreate(Pages::HOME, ['sort_order' => 1]);

        $this->assertSame($home->getKey(), $this->pages->firstOrCreate(Pages::HOME)->getKey());
        $this->assertSame([Pages::HOME, Pages::ABOUT], $this->pages->all()->pluck('key')->all());
        $this->assertSame($home->getKey(), $this->pages->findByKey(Pages::HOME)?->getKey());
        $this->assertNull($this->pages->findByKey(Pages::ERRORS));
    }

    #[Test]
    public function it_saves_reads_counts_and_deletes_blocks(): void
    {
        $page = Page::factory()->page(Pages::HOME)->create();

        $this->pages->saveBlock($page, 'hero_label', PageBlockType::ShortText, ['value' => 'One', 'sort_order' => 2]);
        $this->pages->saveBlock($page, 'hero_label', PageBlockType::ShortText, ['draft_value' => 'Two']);
        $this->pages->saveBlock($page, 'button_story', PageBlockType::Link, ['value' => ['label' => 'Go', 'route' => 'about', 'url' => null], 'sort_order' => 1]);

        $blocks = $this->pages->blocks($page);
        $this->assertSame(['button_story', 'hero_label'], $blocks->keys()->all());
        $this->assertSame('One', $blocks['hero_label']->value);
        $this->assertSame('Two', $blocks['hero_label']->draft_value);
        $this->assertEquals(['label' => 'Go', 'route' => 'about', 'url' => null], $blocks['button_story']->value);
        $this->assertSame(1, $this->pages->countDrafts($page));

        $this->assertTrue($this->pages->deleteBlock($page, 'hero_label'));
        $this->assertNull($this->pages->block($page, 'hero_label'));
        $this->assertSame(1, PageBlock::count());
    }

    #[Test]
    public function deleting_a_page_removes_its_blocks(): void
    {
        $page = Page::factory()->page(Pages::HOME)->create();
        PageBlock::factory()->block($page, 'hero_label')->create();

        $page->delete();

        $this->assertSame(0, PageBlock::count());
    }
}
