<?php

namespace Tests\Feature\Database;

use App\Models\Album;
use App\Models\Profile;
use App\Models\Video;
use App\Support\SearchText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * In Arabic the Professor is always "الأستاذ الدكتور محمد عثمان خضر": stored
 * content with a short form is rewritten, and certificate wording is kept.
 */
class FullArabicNameMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const string FULL = 'الأستاذ الدكتور محمد عثمان خضر';

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_21_105946_use_full_arabic_name_in_stored_content.php');
    }

    #[Test]
    public function short_forms_become_the_full_name_and_the_profile_name_gains_the_title(): void
    {
        $profile = Profile::factory()->published()->create(['name' => ['en' => 'Mohamed Osman Khidir', 'ar' => 'محمد عثمان خضر']]);
        $video = Video::factory()->published()->create(['description' => ['en' => 'Receiving the degree.', 'ar' => 'البروفيسور خضر يتسلّم الدرجة.']]);
        $album = Album::factory()->published()->create(['description' => ['en' => 'Honours.', 'ar' => 'شهادات تكريم قدّمتها للبروفيسور خضر جامعات. صفحة «عن الأستاذ الدكتور خضر». شهادة تمنح محمد عثمان خضر درجة الدكتوراه.']]);

        $this->migration()->up();

        $this->assertSame(self::FULL, $profile->fresh()->getTranslation('name', 'ar'));
        $this->assertSame('Mohamed Osman Khidir', $profile->fresh()->getTranslation('name', 'en'));
        $this->assertSame(self::FULL.' يتسلّم الدرجة.', $video->fresh()->getTranslation('description', 'ar'));
        $this->assertSame(
            'شهادات تكريم قدّمتها للأستاذ الدكتور محمد عثمان خضر جامعات. صفحة «عن '.self::FULL.'». شهادة تمنح محمد عثمان خضر درجة الدكتوراه.',
            $album->fresh()->getTranslation('description', 'ar'),
            'Certificate wording that names him is left as written',
        );
        $this->assertStringContainsString(SearchText::normalise(self::FULL), (string) DB::table('videos')->where('id', $video->id)->value(SearchText::COLUMN));
        $this->assertStringNotContainsString('البروفيسور', (string) DB::table('videos')->where('id', $video->id)->value(SearchText::COLUMN));
    }

    #[Test]
    public function running_twice_changes_nothing_more(): void
    {
        $video = Video::factory()->published()->create(['description' => ['en' => 'x', 'ar' => 'البروفيسور محمد عثمان خضر في الحفل.']]);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(self::FULL.' في الحفل.', $video->fresh()->getTranslation('description', 'ar'));
    }
}
