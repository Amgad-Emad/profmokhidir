<?php

namespace Tests\Feature\Locale;

use App\Models\Commentary;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentFallbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_arabic_page_shows_the_arabic_version_when_it_exists(): void
    {
        Commentary::factory()->published()->create(['slug' => 'water', 'title' => ['en' => 'On water', 'ar' => 'عن الماء'], 'body' => ['en' => '<p>English body</p>', 'ar' => '<p>النص العربي</p>']]);

        $this->get('/ar/commentary/water')->assertOk()
            ->assertSee('عن الماء')->assertSee('العربي')
            ->assertDontSee('On water')
            ->assertDontSee('data-locale-fallback', false);

        $this->get('/commentary/water')->assertSee('On water')->assertDontSee('عن الماء');
    }

    #[Test]
    public function without_an_arabic_version_it_falls_back_to_english_with_a_note(): void
    {
        Commentary::factory()->published()->create(['slug' => 'soil', 'title' => 'On soil']);

        $this->get('/ar/commentary/soil')->assertOk()
            ->assertSee('On soil')
            ->assertSee('data-locale-fallback', false)
            ->assertSee('هذا المحتوى متاح بالإنجليزية فقط')
            ->assertSee('lang="en" dir="ltr"', false);

        $this->get('/commentary/soil')->assertDontSee('data-locale-fallback', false);
    }

    #[Test]
    public function the_arabic_search_matches_arabic_spellings_with_or_without_diacritics_and_alef_forms(): void
    {
        Publication::factory()->published()->create(['title' => ['en' => 'Dryland agronomy', 'ar' => 'الزراعة في الأراضي الجافة']]);

        $this->get('/ar/search?q='.urlencode('الاراضي'))->assertOk()->assertSee('الزراعة في الأراضي الجافة');
        $this->get('/ar/search?q='.urlencode('الأَرَاضِي'))->assertSee('الزراعة في الأراضي الجافة');
        $this->get('/search?q=dryland')->assertSee('Dryland agronomy');
    }
}
