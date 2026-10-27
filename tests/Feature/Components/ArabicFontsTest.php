<?php

namespace Tests\Feature\Components;

use App\Constants\Typography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * <x-fonts> adds Amiri and IBM Plex Sans Arabic from Google Fonts on Arabic pages only (the documented
 * exception in App\Constants\Typography); English pages still request nothing from a third party.
 */
class ArabicFontsTest extends TestCase
{
    use RefreshDatabase;

    private function arabicLinks(): string
    {
        // Loaded without blocking the first paint: preloaded, then applied on load (plain link without script).
        return '<link rel="preload" href="'.e(Typography::ARABIC_STYLESHEET).'" as="style">'."\n"
            .'<link rel="stylesheet" href="'.e(Typography::ARABIC_STYLESHEET).'" media="print" onload="this.media=\'all\'">'."\n"
            .'<noscript><link rel="stylesheet" href="'.e(Typography::ARABIC_STYLESHEET).'"></noscript>';
    }

    #[Test]
    public function the_component_prints_the_google_fonts_link_for_arabic_only(): void
    {
        app()->setLocale('ar');
        $arabic = Blade::render('<x-fonts />');

        $this->assertStringContainsString($this->arabicLinks(), $arabic);
        $this->assertStringContainsString('family=Amiri:wght@400;700&amp;family=IBM+Plex+Sans+Arabic:wght@400;500&amp;display=swap', $arabic);
        $this->assertStringContainsString('rel="preload" href="'.asset(Typography::path(Typography::PRELOAD_FILE)).'"', $arabic);

        app()->setLocale('en');
        $english = Blade::render('<x-fonts />');

        $this->assertStringNotContainsString('fonts.googleapis.com', $english);
        $this->assertStringNotContainsString('fonts.gstatic.com', $english);
        $this->assertStringContainsString('rel="preload" href="'.asset(Typography::path(Typography::PRELOAD_FILE)).'"', $english);
    }

    #[Test]
    public function arabic_pages_load_the_arabic_faces_once_and_english_pages_never(): void
    {
        $arabic = $this->get('/ar')->assertOk()->assertSee('lang="ar" dir="rtl"', false)->getContent();

        $this->assertSame(1, substr_count($arabic, $this->arabicLinks()));

        $this->get('/')->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertDontSee('fonts.googleapis.com', false)
            ->assertDontSee('fonts.gstatic.com', false);
    }
}
