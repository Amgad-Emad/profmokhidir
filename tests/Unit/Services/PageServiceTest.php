<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CommunityCopy;
use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\PageBlocks;
use App\Constants\Pages;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use App\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PageService $pages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pages = $this->app->make(PageService::class);
    }

    #[Test]
    public function install_creates_every_page_published_with_its_defaults_and_never_overwrites(): void
    {
        $this->pages->install();

        $this->assertSame(count(Pages::all()), Page::count());
        $this->assertSame(array_sum(array_map(fn ($page) => count(PageBlocks::for($page)), Pages::all())), PageBlock::count());
        $this->assertTrue(Page::query()->get()->every(fn (Page $page) => $page->status === ContentStatus::Published));

        $home = $this->pages->findByKey(Pages::HOME);
        $this->pages->publish($home, ['hero_label' => 'Kept']);
        PageBlock::query()->where('key', 'scroll_cue')->delete();

        $this->pages->install();

        $this->assertSame('Kept', $this->pages->liveValues($home)['hero_label']);
        $this->assertSame('Scroll', PageBlock::query()->where('key', 'scroll_cue')->first()->value);
    }

    #[Test]
    public function a_draft_is_kept_only_where_it_differs_from_the_live_value(): void
    {
        $this->pages->install();
        $home = $this->pages->findByKey(Pages::HOME);

        $this->pages->saveDraft($home, ['hero_label' => 'Changed', 'scroll_cue' => 'Scroll']);

        $this->assertSame(1, PageBlock::query()->whereNotNull('draft_value')->count());
        $this->assertSame('Changed', $this->pages->formValues($home)['hero_label']);
        $this->assertSame('In honour of', $this->pages->liveValues($home)['hero_label']);

        $this->pages->saveDraft($home, ['hero_label' => 'In honour of']);
        $this->assertFalse($this->pages->hasDraft($home));
    }

    #[Test]
    public function publishing_puts_drafts_live_and_is_audited(): void
    {
        $this->pages->install();
        $editor = User::factory()->editor()->create();
        $home = $this->pages->findByKey(Pages::HOME);
        $this->pages->unpublish($home, $editor);

        $this->pages->saveDraft($home, ['hero_label' => 'From the draft']);
        $page = $this->pages->publish($home, [], $editor);

        $this->assertTrue($page->isPublished());
        $this->assertSame('From the draft', $this->pages->liveValues($page)['hero_label']);
        $this->assertFalse($this->pages->hasDraft($page));
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::PAGE_PUBLISHED)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::PAGE_UNPUBLISHED)->exists());
    }

    #[Test]
    public function values_are_normalised_before_they_are_stored(): void
    {
        $this->pages->install();
        $community = $this->pages->findByKey(Pages::COMMUNITY);

        $this->pages->publish($community, [
            'heading' => '',
            'lead' => '<p>Safe<script>x()</script></p>',
            'conduct' => [['text' => 'One'], ['text' => '  '], ['text' => 'Two']],
        ]);

        $live = $this->pages->liveValues($community);
        $this->assertSame(CommunityCopy::HEADING, $live['heading']);
        $this->assertSame('Safe', $live['lead']);
        $this->assertSame([['text' => 'One'], ['text' => 'Two']], $live['conduct']);
    }

    #[Test]
    public function images_are_uploaded_to_their_block_and_a_live_one_is_never_removed(): void
    {
        Storage::fake(Disks::PUBLIC);
        $this->pages->install();
        $home = $this->pages->findByKey(Pages::HOME);

        $first = $this->pages->uploadImage($home, 'hero_image', UploadedFile::fake()->image('a.jpg'));
        $this->pages->publish($home, ['hero_image' => ['media_id' => $first->getKey(), 'alt_text' => 'A', 'caption' => 'C', 'place' => 'P', 'year' => 1980]]);

        $this->assertFalse($this->pages->removeImage($home, 'hero_image', (int) $first->getKey()));

        $second = $this->pages->uploadImage($home, 'hero_image', UploadedFile::fake()->image('b.jpg'), ['media_id' => $first->getKey()]);
        $third = $this->pages->uploadImage($home, 'hero_image', UploadedFile::fake()->image('c.jpg'), ['media_id' => $second->getKey()]);

        $block = PageBlock::query()->where('key', 'hero_image')->first();
        $this->assertEqualsCanonicalizing([$first->getKey(), $third->getKey()], $block->media()->pluck('id')->all());
        $this->assertSame($third->getKey(), $this->pages->imageFor($home, 'hero_image', $third->getKey())?->getKey());
    }

    #[Test]
    public function only_image_blocks_take_uploads(): void
    {
        $this->pages->install();

        $this->expectException(\InvalidArgumentException::class);
        $this->pages->uploadImage($this->pages->findByKey(Pages::HOME), 'hero_label', UploadedFile::fake()->image('a.jpg'));
    }
}
