<?php

namespace Tests\Unit\Support;

use App\Constants\PageBlocks;
use App\Constants\Pages;
use App\Support\PageBlockDefinition;
use App\Support\PageBlockValue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageBlockValueTest extends TestCase
{
    #[Test]
    public function missing_and_emptied_required_text_takes_the_default_and_optional_text_may_be_empty(): void
    {
        $required = PageBlocks::get(Pages::AWARDS, 'title');
        $optional = PageBlocks::get(Pages::HOME, 'hero_epigraph');

        $this->assertSame('Awards', PageBlockValue::normalise($required, null));
        $this->assertSame('Awards', PageBlockValue::normalise($required, "  \t "));
        $this->assertSame('Given', PageBlockValue::normalise($required, '  Given  '));
        $this->assertSame(60, mb_strlen(PageBlockValue::normalise($required, str_repeat('a', 80))));
        $this->assertSame('', PageBlockValue::normalise($optional, ''));
        $this->assertSame($optional->default, PageBlockValue::normalise($optional, null));
    }

    #[Test]
    public function long_text_is_sanitised_inline_or_with_blocks(): void
    {
        $inline = PageBlocks::get(Pages::COMMUNITY, 'who');
        $blocks = PageBlocks::get(Pages::PRIVACY, 'body');

        $this->assertSame('A<br><em>B</em>', PageBlockValue::normalise($inline, '<p>A</p><p><em style="x">B</em></p>'));
        $this->assertSame('<h2>H</h2><ul><li>I</li></ul>', PageBlockValue::normalise($blocks, '<h2 class="x">H</h2><ul><li>I</li></ul><script>s()</script>'));
        $this->assertSame($inline->default, PageBlockValue::normalise($inline, '<p> </p>'));
    }

    #[Test]
    public function links_keep_listed_destinations_or_real_web_addresses(): void
    {
        $link = PageBlocks::get(Pages::TRIBUTES, 'button_tribute');
        $optional = PageBlocks::get(Pages::HOME, 'button_story');

        $this->assertSame(['label' => $link->default['label'], 'route' => $link->default['route'], 'url' => null], PageBlockValue::normalise($link, ['label' => '', 'route' => 'admin.dashboard']));
        // An optional link (the home page's buttons) emptied on purpose has no label; a missing one takes the default.
        $this->assertSame(['label' => '', 'route' => 'about', 'url' => null], PageBlockValue::normalise($optional, ['label' => '', 'route' => 'about']));
        $this->assertSame(['label' => 'Read his story', 'route' => 'about', 'url' => null], PageBlockValue::normalise($optional, null));
        $this->assertSame(['label' => 'Go', 'route' => 'cv', 'url' => null], PageBlockValue::normalise($link, ['label' => 'Go', 'route' => 'cv', 'url' => 'javascript:alert(1)']));
        $this->assertSame(['label' => 'Go', 'route' => null, 'url' => 'https://example.org'], PageBlockValue::normalise($link, ['label' => 'Go', 'route' => 'cv', 'url' => 'https://example.org']));
    }

    #[Test]
    public function fixed_lists_keep_every_item_and_free_lists_drop_broken_ones(): void
    {
        $chapters = PageBlocks::get(Pages::NAVIGATION, 'chapters');
        $sections = PageBlocks::get(Pages::NAVIGATION, 'sections');
        $footer = PageBlocks::get(Pages::NAVIGATION, 'footer_links');

        $chapterItems = PageBlockValue::normalise($chapters, [['key' => 'work', 'label' => 'Writing', 'strap' => ''], ['key' => 'nonsense', 'label' => 'X', 'strap' => 'Y']]);
        $this->assertSame(['work', 'life', 'archive', 'community', 'tributes'], array_column($chapterItems, 'key'));
        $this->assertSame(['key' => 'work', 'label' => 'Writing', 'strap' => 'What he wrote, taught and argued.'], $chapterItems[0]);

        // Sections cannot be reordered: their order sets the chapter numerals.
        $sectionItems = PageBlockValue::normalise($sections, array_reverse($sections->default));
        $this->assertSame(array_column($sections->default, 'key'), array_column($sectionItems, 'key'));

        $this->assertSame([['label' => 'Home', 'route' => 'home']], PageBlockValue::normalise($footer, [['label' => 'Home', 'route' => 'home'], ['label' => 'Admin', 'route' => 'admin.dashboard'], ['label' => '', 'route' => 'cv']]));
        $this->assertSame($footer->default, PageBlockValue::normalise($footer, []));
        $this->assertCount($footer->maxItems, PageBlockValue::normalise($footer, array_fill(0, 40, ['label' => 'A', 'route' => 'cv'])));
    }

    #[Test]
    public function images_and_search_blocks_keep_their_shape(): void
    {
        $image = PageBlocks::get(Pages::HOME, 'hero_image');
        $seo = PageBlocks::get(Pages::AWARDS, 'seo');

        $this->assertSame(PageBlockDefinition::emptyImage(), PageBlockValue::normalise($image, 'nonsense'));
        $this->assertSame(['media_id' => 4, 'alt_text' => 'A', 'caption' => null, 'place' => null, 'year' => 1980, 'focus_x' => 100, 'focus_y' => 0], PageBlockValue::normalise($image, ['media_id' => '4', 'alt_text' => 'A', 'year' => '1980', 'focus_x' => 140, 'focus_y' => -3]));
        $this->assertSame(['title' => null, 'description' => 'D', 'media_id' => null, 'alt_text' => null], PageBlockValue::normalise($seo, ['title' => ' ', 'description' => 'D']));
    }

    #[Test]
    public function it_tells_a_default_value_from_a_changed_one(): void
    {
        $block = PageBlocks::get(Pages::AWARDS, 'title');

        $this->assertTrue(PageBlockValue::isDefault($block, ''));
        $this->assertFalse(PageBlockValue::isDefault($block, 'Other'));
    }
}
