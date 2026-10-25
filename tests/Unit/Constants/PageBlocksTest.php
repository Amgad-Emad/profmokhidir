<?php

namespace Tests\Unit\Constants;

use App\Constants\PageBlocks;
use App\Constants\PageBlockType;
use App\Constants\PageLinks;
use App\Constants\Pages;
use App\Constants\Sections;
use App\Http\Requests\Admin\PageRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageBlocksTest extends TestCase
{
    #[Test]
    public function every_page_is_listed_and_every_section_has_a_page(): void
    {
        $this->assertSame(['home', 'about', 'cv', 'publications', 'awards', 'videos', 'pictures', 'teaching', 'commentary', 'community', 'tributes', 'other', 'privacy', 'errors', 'navigation'], Pages::all());

        foreach (Sections::all() as $section) {
            $this->assertSame($section, Pages::forSection($section));
            $this->assertTrue(PageBlocks::has($section, 'title'), $section);
            $this->assertTrue(PageBlocks::has($section, 'intro'), $section);
        }

        $this->assertSame(Pages::HOME, Pages::forRoute('home'));
        $this->assertNull(Pages::forRoute('videos.show'));
    }

    #[Test]
    public function every_block_has_a_label_a_section_and_a_default_that_passes_its_own_rules(): void
    {
        foreach (Pages::all() as $page) {
            $blocks = PageBlocks::for($page);
            $this->assertNotEmpty($blocks, $page);

            foreach ($blocks as $key => $block) {
                $this->assertSame($key, $block->key);
                $this->assertNotSame('', $block->label);
                $this->assertNotSame('', $block->section);

                if (in_array($block->type, [PageBlockType::ShortText, PageBlockType::LongText], true)) {
                    $this->assertNotNull($block->max, "{$page}.{$key} needs a length limit");
                    $this->assertNotSame('', $block->default, "{$page}.{$key} needs its original wording");
                }
            }

            $validator = Validator::make(PageBlocks::defaults($page), (new PageRequest)->forPage($page)->rules());
            $this->assertFalse($validator->fails(), $page.': '.implode(' ', $validator->errors()->all()));
        }
    }

    #[Test]
    public function every_link_destination_is_a_real_public_route(): void
    {
        foreach (PageLinks::routes() as $route) {
            $this->assertTrue(Route::has($route), $route);
            $this->assertStringStartsNotWith('admin.', $route);
        }
    }

    #[Test]
    public function the_admin_sections_follow_the_order_of_the_site(): void
    {
        $this->assertSame(['Hero', 'A life in chapters', 'His work in numbers', 'Continue the story', 'Search & sharing'], array_keys(PageBlocks::sections(Pages::HOME)));
        $this->assertSame('hero_label', array_key_first(PageBlocks::for(Pages::HOME)));
    }

    #[Test]
    public function unknown_pages_and_blocks_are_refused(): void
    {
        $this->assertFalse(PageBlocks::has('nowhere', 'title'));
        $this->assertFalse(PageBlocks::has(Pages::HOME, 'style'));

        $this->expectException(\InvalidArgumentException::class);
        PageBlocks::get(Pages::HOME, 'css');
    }
}
