<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\CommunityCopy;
use App\Constants\Pages;
use App\Models\Page;
use App\Models\PageBlock;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Services\PageContentService;
use App\Services\PageService;
use App\Support\PreviewContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageContentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PageContentService $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->content = $this->app->make(PageContentService::class);
    }

    #[Test]
    public function with_nothing_stored_every_block_reads_its_default(): void
    {
        $this->assertSame('In honour of', $this->content->text(Pages::HOME, 'hero_label'));
        $this->assertSame(['label' => 'Read his story', 'url' => route('about')], $this->content->link(Pages::HOME, 'button_story'));
        $this->assertCount(5, $this->content->items(Pages::COMMUNITY, 'conduct'));
        $this->assertNull($this->content->image(Pages::HOME, 'hero_image'));
        $this->assertSame(['title' => null, 'description' => null, 'image' => null, 'imageAlt' => null], $this->content->seo(Pages::AWARDS));
    }

    #[Test]
    public function published_values_are_cached_per_page_and_the_cache_clears_on_save(): void
    {
        $page = Page::factory()->page(Pages::HOME)->published()->create();
        PageBlock::factory()->block($page, 'hero_label')->value('Cached label')->create();

        $this->assertSame('Cached label', $this->content->text(Pages::HOME, 'hero_label'));
        $this->assertTrue(Cache::has(CacheKeys::page(Pages::HOME)));

        // Written behind the service's back: the cached value is still served.
        PageBlock::query()->where('key', 'hero_label')->update(['value' => json_encode('Changed directly')]);
        $this->content->flush(Pages::ABOUT);
        $this->assertSame('Cached label', $this->content->text(Pages::HOME, 'hero_label'));

        // Saving through the page service clears the page's cache.
        $this->app->make(PageService::class)->publish($page, ['hero_label' => 'Published label']);
        $this->assertFalse(Cache::has(CacheKeys::page(Pages::HOME)));
        $this->assertSame('Published label', $this->content->text(Pages::HOME, 'hero_label'));
    }

    #[Test]
    public function a_draft_value_is_only_read_while_its_page_is_previewed(): void
    {
        $page = Page::factory()->page(Pages::HOME)->published()->create();
        PageBlock::factory()->block($page, 'hero_label')->value('Live')->draft('Draft')->create();

        $this->assertSame('Live', $this->content->text(Pages::HOME, 'hero_label'));

        $this->app->make(PreviewContext::class)->activate(Page::class, (int) $page->getKey());
        $this->assertSame('Draft', $this->content->text(Pages::HOME, 'hero_label'));

        $this->app->make(PreviewContext::class)->clear();
        $this->assertSame('Live', $this->content->text(Pages::HOME, 'hero_label'));
    }

    #[Test]
    public function an_unpublished_page_reads_its_defaults(): void
    {
        $page = Page::factory()->page(Pages::HOME)->draft()->create();
        PageBlock::factory()->block($page, 'hero_label')->value('Not yet')->create();

        $this->assertSame('In honour of', $this->content->text(Pages::HOME, 'hero_label'));
    }

    #[Test]
    public function long_text_is_sanitised_on_the_way_out_and_placeholders_are_escaped(): void
    {
        $page = Page::factory()->page(Pages::COMMUNITY)->published()->create();
        PageBlock::factory()->block($page, 'lead')->value('<p>One <em>two</em></p><p onclick="x()">three<script>alert(1)</script></p>')->create();
        $privacy = Page::factory()->page(Pages::PRIVACY)->published()->create();
        PageBlock::factory()->block($privacy, 'body')->value('<h2>Kept</h2><p>For :raw_days days.</p><iframe src="x"></iframe>')->create();

        $this->assertSame('One <em>two</em><br>three', (string) $this->content->html(Pages::COMMUNITY, 'lead'));
        $this->assertSame('<h2>Kept</h2><p>For &lt;b&gt;30 days.</p>', (string) $this->content->html(Pages::PRIVACY, 'body', [':raw_days' => '<b>30']));
    }

    #[Test]
    public function plain_text_with_special_characters_is_escaped(): void
    {
        $this->assertSame(e(CommunityCopy::INTRO), (string) $this->content->html(Pages::COMMUNITY, 'lead'));
    }

    #[Test]
    public function when_the_database_cannot_be_reached_the_built_in_copy_is_used(): void
    {
        $repository = $this->createMock(PageRepositoryInterface::class);
        $repository->method('findByKey')->willThrowException(new QueryException('mysql', 'select', [], new \RuntimeException('gone')));
        $content = new PageContentService($repository, new PreviewContext);

        $this->assertSame('Page not found', $content->text(Pages::ERRORS, 'title_404'));
    }

    #[Test]
    public function the_share_image_falls_back_to_the_site_wide_one(): void
    {
        $this->assertSame(['image' => null, 'imageAlt' => null], $this->content->shareImage(Pages::AWARDS));
    }
}
