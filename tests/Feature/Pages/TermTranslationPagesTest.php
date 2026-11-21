<?php

namespace Tests\Feature\Pages;

use App\Constants\TermVocabulary;
use App\Models\Commentary;
use App\Models\OtherItem;
use App\Models\TermTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Arabic pages show the Arabic names of categories, types and tags, while
 * grouping, anchors and filter addresses keep the English term.
 */
class TermTranslationPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function other_information_shows_arabic_category_and_type_names_on_arabic_pages(): void
    {
        OtherItem::factory()->published()->create(['category' => 'Announcements', 'item_type' => 'announcement']);
        TermTranslation::factory()->naming(TermVocabulary::OtherCategory, 'Announcements', 'إعلانات')->create();
        TermTranslation::factory()->naming(TermVocabulary::OtherType, 'announcement', 'إعلان')->create();

        $this->get('/ar/other')->assertOk()->assertSee('إعلانات')->assertSee('إعلان')
            ->assertSee('id="category-announcements"', false)
            ->assertDontSee('>Announcements<', false);

        $this->get('/other')->assertOk()->assertSee('Announcements')->assertDontSee('إعلانات');
    }

    #[Test]
    public function commentary_tags_read_in_arabic_but_filter_by_the_english_tag(): void
    {
        Commentary::factory()->published()->create(['tags' => ['climate']]);
        TermTranslation::factory()->naming(TermVocabulary::CommentaryTag, 'climate', 'المناخ')->create();

        $this->get('/ar/commentary')->assertOk()->assertSee('المناخ')->assertSee('tag=climate', false);
    }
}
