<?php

namespace Tests\Feature\Locale;

use App\Constants\Disks;
use App\Constants\PublicationType;
use App\Constants\Search;
use App\Models\Award;
use App\Models\CvEntry;
use App\Models\Profile;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The core public pages under /ar: Arabic chrome right to left, English content
 * that has no Arabic version marked as English, and the English pages unchanged.
 */
class ArabicSiteCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function the_arabic_home_page_is_right_to_left_with_arabic_copy_and_navigation(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('اقرأ قصته')
            ->assertSee('اترك كلمة وفاء')
            ->assertSee('حياة في فصول')
            ->assertSee('سيرته')
            ->assertSee('أعماله')
            ->assertSee('الأرشيف')
            ->assertSee('المجتمع العلمي')
            ->assertSee('انتقل إلى المحتوى')
            ->assertSee('محمد عثمان خضر')
            ->assertSee('استكشف')
            ->assertSee('section-card__arrow rtl-mirror', false)
            ->assertDontSee('Read his story')
            ->assertDontSee('Skip to content');
    }

    #[Test]
    public function the_arabic_about_page_notes_english_only_content_and_marks_it_left_to_right(): void
    {
        Profile::factory()->published()->create(['name' => 'Mohamed Osman Khidir', 'full_bio' => '<p>An English biography.</p>']);

        $this->get('/ar/about')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('عن الأستاذ الدكتور محمد عثمان خضر')
            ->assertSee('هذا المحتوى متاح بالإنجليزية فقط')
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('تابع القراءة')
            ->assertSee('href="'.url('/ar/cv').'"', false);
    }

    #[Test]
    public function the_arabic_about_page_shows_arabic_content_without_the_note(): void
    {
        Profile::factory()->published()->create(['name' => ['en' => 'Mohamed Osman Khidir', 'ar' => 'محمد عثمان خضر'], 'title' => null, 'affiliation' => null, 'full_bio' => ['en' => '<p>English.</p>', 'ar' => '<p>سيرة عربية.</p>']]);

        $this->get('/ar/about')
            ->assertOk()
            ->assertSee('عربية.')
            ->assertDontSee('هذا المحتوى متاح بالإنجليزية فقط');
    }

    #[Test]
    public function the_arabic_cv_counts_entries_in_arabic_and_names_the_sections(): void
    {
        CvEntry::factory()->published()->count(3)->create(['section' => 'education']);

        $this->get('/ar/cv')
            ->assertOk()
            ->assertSee('السيرة الذاتية والإنجازات')
            ->assertSee('التعليم')
            ->assertSee('3 بنود');
    }

    #[Test]
    public function the_arabic_awards_page_marks_english_awards_and_their_source_link(): void
    {
        Award::factory()->published()->create(['name' => 'Order of Merit', 'organisation' => null, 'description' => null, 'year' => 1990, 'source_url' => 'https://example.org/award']);

        $this->get('/ar/awards')
            ->assertOk()
            ->assertSee('الجوائز والتكريمات')
            ->assertSee('المصدر · example.org')
            ->assertSeeInOrder(['lang="en" dir="ltr"', 'Order of Merit'], false);
    }

    #[Test]
    public function the_arabic_publications_catalogue_translates_filters_counts_and_types(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->count(2)->create();

        $this->get('/ar/publications')
            ->assertOk()
            ->assertSee('المنشورات')
            ->assertSee('تصفية')
            ->assertSee('جميع الأنواع')
            ->assertSee('كتاب')
            ->assertSee('عقد 1970')
            ->assertSee('سجلان')
            ->assertSee('عرض 1–2 من أصل سجلين')
            ->assertDontSee('استشهاد')
            ->assertDontSee('citePanel', false);
    }

    #[Test]
    public function arabic_search_shows_an_arabic_interface_and_result_counts(): void
    {
        $this->get('/ar/search')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('ابحث في الأرشيف')
            ->assertSee('اكتب كلمة أو كلمتين للبحث في جميع فصول الموقع.');

        $this->get('/ar/search?q=zzzz')
            ->assertOk()
            ->assertSee('البحث: zzzz')
            ->assertSee('لا شيء في الأرشيف يطابق ما تبحث عنه.');

        app()->setLocale('ar');

        $this->assertSame('نتيجة واحدة في قسم واحد', Search::summary(1, 1));
        $this->assertSame('12 نتيجة في 3 أقسام', Search::summary(12, 3));
        $this->assertSame('نتيجتان في قسمين', Search::summary(2, 2));

        app()->setLocale('en');

        $this->assertSame('12 results in 3 sections', Search::summary(12, 3));
        $this->assertSame('1 result in 1 section', Search::summary(1, 1));
    }

    #[Test]
    public function a_missing_page_under_ar_shows_the_arabic_error_page(): void
    {
        $this->get('/ar/no-such-page')
            ->assertNotFound()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('الصفحة غير موجودة')
            ->assertSee('العودة إلى الرئيسية')
            ->assertSee('أو انتقل إلى أحد الفصول');
    }

    #[Test]
    public function the_english_pages_are_unchanged(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->count(2)->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('Read his story')
            ->assertSee('Skip to content')
            ->assertSee('Explore')
            ->assertDontSee('اقرأ قصته');

        $this->get('/publications')
            ->assertOk()
            ->assertSee('1970s')
            ->assertSee('Showing 1–2 of 2 records')
            ->assertSee('All types')
            ->assertDontSee('lang="en" dir="ltr" data-', false);

        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('Page not found');
    }
}
