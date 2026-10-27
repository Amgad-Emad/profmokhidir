<?php

namespace Tests\Unit\Models;

use App\Constants\Locales;
use App\Models\Commentary;
use App\Models\Profile;
use App\Support\SearchText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasTranslatableContentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function reading_follows_the_current_language_and_falls_back_to_english(): void
    {
        $commentary = Commentary::factory()->create(['title' => ['en' => 'On water', 'ar' => 'عن الماء'], 'summary' => 'English summary', 'epigraph' => null]);

        app()->setLocale(Locales::AR);
        $this->assertSame('عن الماء', $commentary->title);
        $this->assertSame('English summary', $commentary->summary);
        $this->assertNull($commentary->epigraph);
        $this->assertSame(Locales::EN, $commentary->shownLocale('summary'));
        $this->assertFalse($commentary->showsFallback());

        app()->setLocale(Locales::EN);
        $this->assertSame('On water', $commentary->title);
    }

    #[Test]
    public function plain_writes_go_to_english_even_on_an_arabic_request(): void
    {
        $commentary = Commentary::factory()->create(['title' => ['en' => 'On water', 'ar' => 'عن الماء']]);

        app()->setLocale(Locales::AR);
        $commentary->update(['title' => 'On rivers']);

        $this->assertEquals(['en' => 'On rivers', 'ar' => 'عن الماء'], $commentary->fresh()->getTranslations('title'));
    }

    #[Test]
    public function records_needing_arabic_are_found_by_scope_and_by_method(): void
    {
        $done = Commentary::factory()->create(['title' => ['en' => 'A', 'ar' => 'أ'], 'summary' => ['en' => 'S', 'ar' => 'ص'], 'epigraph' => null, 'body' => null]);
        $partial = Commentary::factory()->create(['title' => ['en' => 'B', 'ar' => 'ب'], 'summary' => 'Only English', 'epigraph' => null, 'body' => null]);
        $none = Commentary::factory()->create(['title' => 'C', 'summary' => null, 'epigraph' => null, 'body' => null]);

        $this->assertFalse($done->needsTranslation());
        $this->assertSame(['summary'], $partial->missingTranslations());
        $this->assertTrue($none->showsFallback(Locales::AR));
        $this->assertEqualsCanonicalizing([$partial->id, $none->id], Commentary::query()->needingTranslation()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$done->id, $partial->id], Commentary::query()->translatedInto(Locales::AR)->pluck('id')->all());
    }

    #[Test]
    public function arabic_lists_are_kept_beside_english_ones(): void
    {
        $profile = Profile::factory()->create(['expertise' => ['en' => ['Agronomy'], 'ar' => ['علم المحاصيل']]]);

        app()->setLocale(Locales::AR);
        $this->assertSame(['علم المحاصيل'], $profile->expertise);
    }

    #[Test]
    public function the_search_column_folds_every_language_on_save(): void
    {
        $commentary = Commentary::factory()->create(['title' => ['en' => 'On water', 'ar' => 'عن المِيَاه']]);

        $this->assertStringContainsString('on water', $commentary->fresh()->getAttribute(SearchText::COLUMN));
        $this->assertStringContainsString('عن المياه', $commentary->fresh()->getAttribute(SearchText::COLUMN));
    }
}
