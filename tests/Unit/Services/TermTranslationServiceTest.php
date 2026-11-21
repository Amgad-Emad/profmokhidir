<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\Locales;
use App\Constants\TermVocabulary;
use App\Models\AuditLog;
use App\Models\OtherItem;
use App\Models\TermTranslation;
use App\Models\User;
use App\Services\TermTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TermTranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TermTranslationService $terms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->terms = app(TermTranslationService::class);
    }

    #[Test]
    public function a_term_reads_in_the_page_language_and_falls_back_to_its_english(): void
    {
        TermTranslation::factory()->naming(TermVocabulary::OtherCategory, 'Archive', 'الأرشيف')->create();

        $this->assertSame('Archive', $this->terms->label(TermVocabulary::OtherCategory, 'Archive', Locales::DEFAULT));
        $this->assertSame('الأرشيف', $this->terms->label(TermVocabulary::OtherCategory, 'Archive', Locales::AR));
        $this->assertSame('In the press', $this->terms->label(TermVocabulary::OtherCategory, 'In the press', Locales::AR), 'Untranslated terms stay in English.');
        $this->assertSame('Archive', $this->terms->label(TermVocabulary::CommentaryTag, 'Archive', Locales::AR), 'Each vocabulary has its own names.');
        $this->assertNull($this->terms->label(TermVocabulary::OtherCategory, '  ', Locales::AR));
    }

    #[Test]
    public function other_information_types_are_shown_in_title_case_in_english(): void
    {
        $this->assertSame('Press Release', $this->terms->label(TermVocabulary::OtherType, 'press_release', Locales::DEFAULT));
    }

    #[Test]
    public function filter_options_keep_the_english_term_as_the_value(): void
    {
        app()->setLocale(Locales::AR);
        TermTranslation::factory()->naming(TermVocabulary::TeachingTopic, 'Lectures', 'المحاضرات')->create();

        $this->assertSame(['Lectures' => 'المحاضرات', 'Notes' => 'Notes'], $this->terms->options(TermVocabulary::TeachingTopic, ['Lectures', 'Notes']));
    }

    #[Test]
    public function saving_writes_names_removes_blank_ones_refreshes_the_cache_and_is_audited(): void
    {
        $editor = User::factory()->editor()->create();
        $this->assertSame('Archive', $this->terms->label(TermVocabulary::OtherCategory, 'Archive', Locales::AR));

        $this->assertSame(1, $this->terms->save([TermVocabulary::OtherCategory->value => ['Archive' => ' الأرشيف ']], Locales::AR, $editor));
        $this->assertSame('الأرشيف', $this->terms->label(TermVocabulary::OtherCategory, 'Archive', Locales::AR));
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TERMS_UPDATED)->exists());

        $this->terms->save([TermVocabulary::OtherCategory->value => ['Archive' => '']], Locales::AR, $editor);
        $this->assertSame('Archive', $this->terms->label(TermVocabulary::OtherCategory, 'Archive', Locales::AR));
        $this->assertSame(0, TermTranslation::query()->count());
    }

    #[Test]
    public function the_editing_screen_lists_the_terms_in_use_with_their_names(): void
    {
        OtherItem::factory()->published()->create(['category' => 'Archive', 'item_type' => 'resource']);
        OtherItem::factory()->published()->create(['category' => 'Announcements', 'item_type' => null]);
        TermTranslation::factory()->naming(TermVocabulary::OtherCategory, 'Archive', 'الأرشيف')->create();

        $groups = collect($this->terms->forEditing(Locales::AR))->keyBy(fn (array $group): string => $group['vocabulary']->value);

        $this->assertSame(['Announcements' => '', 'Archive' => 'الأرشيف'], $groups[TermVocabulary::OtherCategory->value]['terms']);
        $this->assertSame(['resource' => ''], $groups[TermVocabulary::OtherType->value]['terms']);
        $this->assertArrayNotHasKey(TermVocabulary::CommentaryTag->value, $groups->all(), 'Vocabularies with no terms in use are left out.');
    }
}
