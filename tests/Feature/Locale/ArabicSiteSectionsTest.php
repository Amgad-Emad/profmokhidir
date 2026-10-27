<?php

namespace Tests\Feature\Locale;

use App\Constants\Disks;
use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Models\Album;
use App\Models\Commentary;
use App\Models\OtherItem;
use App\Models\TeachingMaterial;
use App\Models\Tribute;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The archive, teaching, commentary, other, tributes and community pages under
 * /ar: Arabic chrome right to left, English-only content marked as English with
 * the fallback note on detail pages, and visitors' tributes printed as written.
 */
class ArabicSiteSectionsTest extends TestCase
{
    use RefreshDatabase;

    private const string FALLBACK_NOTE = 'هذا المحتوى متاح بالإنجليزية فقط';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function the_arabic_videos_index_and_detail_are_right_to_left_with_english_titles_marked(): void
    {
        $featured = Video::factory()->published()->featured()->create(['title' => 'Opening lecture', 'description' => 'An English description.']);
        Video::factory()->published()->create(['title' => ['en' => 'Second lecture', 'ar' => 'المحاضرة الثانية']]);

        $this->get('/ar/videos')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('المحاضرة الثانية')
            ->assertSee('lang="en" dir="ltr"', false);

        $this->get('/ar/videos/'.$featured->slug)
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee(self::FALLBACK_NOTE)
            ->assertSee('مرئيات أخرى');
    }

    #[Test]
    public function the_arabic_pictures_index_and_album_count_photographs_in_arabic(): void
    {
        $album = Album::factory()->published()->withPhotos(2)->create(['title' => ['en' => 'Field work', 'ar' => 'العمل الميداني']]);

        $this->get('/ar/pictures')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('العمل الميداني')
            ->assertSee('صورتان')
            ->assertSee('عبر العقود')
            ->assertDontSee('photographs in');

        $this->get('/ar/pictures/'.$album->slug)
            ->assertOk()
            ->assertSee('العمل الميداني')
            ->assertSee('صور ألبوم العمل الميداني')
            ->assertSee('عرض بحجم أكبر')
            ->assertDontSee(self::FALLBACK_NOTE);

        $this->get('/ar/pictures?view=decades')->assertOk()->assertSee('عبر العقود');
    }

    #[Test]
    public function the_arabic_teaching_page_translates_filters_types_and_counts(): void
    {
        TeachingMaterial::factory()->published()->course('Soil science')->create(['title' => 'Week one notes', 'version' => '2']);

        $this->get('/ar/teaching')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('عنصر واحد')
            ->assertSee('الإصدار 2')
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertDontSee('1 item');
    }

    #[Test]
    public function an_english_only_commentary_shows_the_fallback_note_and_reading_time_in_arabic(): void
    {
        $commentary = Commentary::factory()->published()->create(['title' => 'On patience', 'summary' => 'A short piece.', 'body' => '<p>Words.</p>', 'external_url' => null]);

        $this->get('/ar/commentary')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('On patience')
            ->assertSee('lang="en" dir="ltr"', false);

        $this->get('/ar/commentary/'.$commentary->slug)
            ->assertOk()
            ->assertSee(self::FALLBACK_NOTE)
            ->assertSee('قراءة في دقيقة واحدة')
            ->assertDontSee('min read');
    }

    #[Test]
    public function a_translated_commentary_has_no_fallback_note(): void
    {
        $commentary = Commentary::factory()->published()->create([
            'title' => ['en' => 'On patience', 'ar' => 'في الصبر'],
            'summary' => ['en' => 'A short piece.', 'ar' => 'مقالة قصيرة.'],
            'body' => ['en' => '<p>Words.</p>', 'ar' => '<p>كلمات.</p>'],
            'external_url' => null,
        ]);

        $this->get('/ar/commentary/'.$commentary->slug)
            ->assertOk()
            ->assertSee('في الصبر')
            ->assertSee('كلمات.')
            ->assertDontSee(self::FALLBACK_NOTE);
    }

    #[Test]
    public function the_arabic_other_information_page_and_detail(): void
    {
        $item = OtherItem::factory()->published()->category('')->create(['title' => 'A note', 'destination_url' => null, 'body' => '<p>Body.</p>']);

        $this->get('/ar/other')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('معلومات إضافية')
            ->assertSee('عنصر واحد');

        $this->get('/ar/other/'.$item->slug)
            ->assertOk()
            ->assertSee(self::FALLBACK_NOTE);
    }

    #[Test]
    public function the_arabic_tributes_book_prints_tributes_as_written_with_arabic_labels(): void
    {
        Tribute::factory()->approved()->create(['name' => 'أحمد علي', 'relationship' => TributeRelationship::Student, 'message' => 'كان معلمًا كريمًا.']);

        $this->get('/ar/tributes')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('كان معلمًا كريمًا.')
            ->assertSee('class="tribute-entry__text" dir="auto"', false)
            ->assertSee('طالب')
            ->assertSee('الطلاب')
            ->assertSee('images/social/tributes-ar.png', false);
    }

    #[Test]
    public function the_tribute_form_works_in_arabic_from_writing_to_thanks(): void
    {
        $this->get('/ar/tributes/new')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('الخطوة 1 من 2', false)
            ->assertSee('اسمك')
            ->assertSee('action="'.url('/ar/tributes/preview').'"', false);

        $payload = [
            'name' => 'سارة محمد',
            'relationship' => TributeRelationship::Colleague->value,
            'location' => 'الخرطوم',
            'message' => 'رحم الله أستاذنا.',
            'email' => '',
        ];

        $this->post('/ar/tributes/preview', $payload)
            ->assertOk()
            ->assertSee('الخطوة 2 من 2', false)
            ->assertSee('رحم الله أستاذنا.')
            ->assertSee('action="'.url('/ar/tributes').'"', false);

        $this->post('/ar/tributes', [...$payload, 'consent' => '1'])
            ->assertRedirect(url('/ar/tributes/thank-you'));

        $this->assertDatabaseHas('tributes', ['name' => 'سارة محمد', 'status' => TributeStatus::Pending->value]);

        $this->get('/ar/tributes/thank-you')->assertOk()->assertSee('lang="ar" dir="rtl"', false);
    }

    #[Test]
    public function arabic_tribute_validation_messages_are_in_arabic(): void
    {
        $this->from('/ar/tributes/new')
            ->post('/ar/tributes/preview', ['name' => '', 'relationship' => '', 'message' => ''])
            ->assertSessionHasErrors(['name' => 'أدخل اسمك.', 'message' => 'اكتب كلمتك.']);
    }

    #[Test]
    public function the_arabic_community_page_translates_the_signup_form(): void
    {
        $this->get('/ar/community')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('الجهة')
            ->assertSee('التخصص الأكاديمي')
            ->assertSee('سبب رغبتك في الانضمام')
            ->assertSee('اقرأ بيان الخصوصية')
            ->assertSee('هل أنت عضو بالفعل؟', false)
            ->assertDontSee('Already a member?');
    }

    #[Test]
    public function the_english_pages_are_unchanged(): void
    {
        Album::factory()->published()->withPhotos(2)->create();
        $commentary = Commentary::factory()->published()->create(['body' => '<p>Words.</p>']);

        $this->get('/pictures')->assertOk()->assertSee('2 photographs in 1 album')->assertSee('<html lang="en" dir="ltr"', false);
        $this->get('/commentary/'.$commentary->slug)->assertOk()->assertSee('1 min read')->assertDontSee(self::FALLBACK_NOTE);
        $this->get('/tributes')->assertOk()->assertSee('images/social/tributes.png', false);
    }
}
