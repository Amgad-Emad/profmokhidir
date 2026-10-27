<?php

namespace Tests\Feature\Locale;

use App\Constants\Disks;
use App\Constants\SocialCards;
use App\Constants\TributeCards;
use App\Models\Tribute;
use App\Services\SeoService;
use App\Services\TributeShareCardService;
use App\Support\BrandCanvas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Arabic share images: the static cards have Arabic editions picked on Arabic
 * pages, tribute cards viewed under /ar are drawn as their own Arabic edition,
 * and Arabic text is shaped (joined, right to left) before GD draws it.
 */
class ArabicShareCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function the_card_for_the_current_language_is_chosen(): void
    {
        $this->assertSame(SocialCards::SITE, SocialCards::forLocale(SocialCards::SITE, 'en'));
        $this->assertSame(SocialCards::SITE_AR, SocialCards::forLocale(SocialCards::SITE, 'ar'));
        $this->assertSame(SocialCards::TRIBUTES_AR, SocialCards::forLocale(SocialCards::TRIBUTES, 'ar'));

        app()->setLocale('ar');
        $this->assertSame(asset(SocialCards::SITE_AR), app(SeoService::class)->image());
    }

    #[Test]
    public function arabic_pages_share_the_arabic_site_card(): void
    {
        $this->get('/ar/videos')->assertOk()->assertSee(asset(SocialCards::SITE_AR), false);
        $this->get('/videos')->assertOk()->assertSee(asset(SocialCards::SITE), false)->assertDontSee(SocialCards::SITE_AR, false);
    }

    #[Test]
    public function the_command_draws_both_editions_of_every_card(): void
    {
        $directory = storage_path('framework/testing/social-cards-'.getmypid());

        $this->artisan('brand:social-cards', ['--output' => $directory])->assertSuccessful();

        foreach ([SocialCards::SITE, SocialCards::SITE_AR, SocialCards::TRIBUTES, SocialCards::TRIBUTES_AR] as $card) {
            $this->assertFileExists($directory.'/'.$card);
            $this->assertSame([SocialCards::WIDTH, SocialCards::HEIGHT], array_slice(getimagesize($directory.'/'.$card), 0, 2));
        }

        $this->assertNotSame(md5_file($directory.'/'.SocialCards::SITE), md5_file($directory.'/'.SocialCards::SITE_AR));

        File::deleteDirectory($directory);
    }

    #[Test]
    public function arabic_text_is_shaped_into_joined_right_to_left_forms_and_set_in_amiri(): void
    {
        $visual = BrandCanvas::visual('في ذكراه');

        // Presentation forms (U+FE70–U+FEFF) and the first word drawn last (rightmost).
        $this->assertMatchesRegularExpression('/[\x{FE70}-\x{FEFF}]/u', $visual);
        $this->assertSame('هاﺮﻛذ ﻲﻓ', $visual);
        $this->assertStringEndsWith('ﻲﻓ', $visual);
        $this->assertSame('Latin only', BrandCanvas::visual('Latin only'));
        $this->assertSame(SocialCards::FONT_ARABIC, BrandCanvas::fontFor(SocialCards::FONT_DISPLAY_ITALIC, 'نص'));
        $this->assertSame(SocialCards::FONT_ARABIC_BOLD, BrandCanvas::fontFor(SocialCards::FONT_LABEL, 'نص'));
        $this->assertSame(SocialCards::FONT_LABEL, BrandCanvas::fontFor(SocialCards::FONT_LABEL, 'Text'));
    }

    #[Test]
    public function a_tribute_viewed_under_arabic_serves_its_own_arabic_card(): void
    {
        $tribute = Tribute::factory()->approved()->create(['name' => 'أحمد علي', 'message' => 'كان معلمًا صبورًا وكريمًا.', 'location' => 'الخرطوم']);

        $this->get('/ar/tributes')->assertOk()->assertSee(url('/ar/tributes/'.$tribute->id.'/card'), false);

        $response = $this->get('/ar/tributes/'.$tribute->id.'/card')->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));

        $arabic = Storage::disk(Disks::PUBLIC)->path(TributeCards::path($tribute->id, 'ar'));
        $this->assertFileExists($arabic);
        $this->assertSame([TributeCards::WIDTH, TributeCards::HEIGHT], array_slice(getimagesize($arabic), 0, 2));
        // The English card is untouched until it is asked for, and kept apart.
        $this->assertNull($tribute->fresh()->share_card_path);

        $this->get('/tributes/'.$tribute->id.'/card')->assertOk();
        $english = Storage::disk(Disks::PUBLIC)->path(TributeCards::path($tribute->id));
        $this->assertFileExists($english);
        $this->assertNotSame(md5_file($english), md5_file($arabic));

        // Forgetting a card removes both editions.
        app(TributeShareCardService::class)->forget($tribute->fresh());
        $this->assertFileDoesNotExist($arabic);
        $this->assertFileDoesNotExist($english);
    }
}
