<?php

namespace Tests\Feature\Qa;

use App\Constants\Sections;
use App\Models\CvEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\HasMedia;
use Tests\TestCase;

/**
 * The CV is view-only on the page: no download links, no file route and no
 * media attached to CV entries.
 */
class CvFileExposureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_only_cv_route_is_the_page_itself(): void
    {
        $path = ltrim(Sections::definitions()[Sections::CV]['path'], '/');
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route): string => $route->uri())->unique()->values();

        $cvUris = $uris->filter(fn (string $uri): bool => $uri === $path || str_starts_with($uri, $path.'/') || str_starts_with($uri, $path.'.'));

        $this->assertSame([$path], $cvUris->values()->all(), 'No route may serve a CV file');

        foreach ($uris as $uri) {
            $this->assertDoesNotMatchRegularExpression('/\bcv\b.*(download|export|pdf|file)|(download|export|pdf|file).*\bcv\b/i', $uri, "Route {$uri} looks like a CV file route");
        }
    }

    #[Test]
    public function cv_entries_carry_no_media(): void
    {
        $this->assertFalse(is_subclass_of(CvEntry::class, HasMedia::class));
        $this->assertFalse(method_exists(CvEntry::class, 'addMedia'));
        $this->assertFalse(method_exists(CvEntry::class, 'registerMediaCollections'));
    }

    #[Test]
    public function the_cv_page_links_only_to_pages_and_anchors(): void
    {
        CvEntry::factory()->published()->count(5)->create();

        $html = $this->get(route('cv'))->assertOk()->getContent();

        preg_match_all('/<a\s[^>]*href="([^"]*)"[^>]*>/i', $html, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[0] as $index => $anchor) {
            $href = html_entity_decode($matches[1][$index]);

            $this->assertStringNotContainsString(' download', $anchor, 'No link on the CV page carries a download attribute');
            $this->assertDoesNotMatchRegularExpression('/\.(pdf|docx?|rtf|odt|zip)(\?|#|$)/i', $href, "Link {$href} points at a document file");
            $this->assertDoesNotMatchRegularExpression('#^/storage/#', parse_url($href, PHP_URL_PATH) ?? '', "Link {$href} points into storage");
        }

        $this->assertStringNotContainsString('application/pdf', $html);
        $this->assertStringNotContainsString('window.print', $html);
        $this->assertDoesNotMatchRegularExpression('/download the cv|print the cv|export/i', strip_tags($html));
    }

    #[Test]
    public function guessable_cv_file_urls_are_not_found(): void
    {
        foreach (['/cv/download', '/cv/export', '/cv.pdf', '/cv/cv.pdf', '/cv/print', '/downloads/cv.pdf'] as $url) {
            $this->get($url)->assertNotFound();
        }

        // The local storage route refuses paths outside the public disk's files.
        $this->assertContains($this->get('/storage/cv.pdf')->getStatusCode(), [403, 404]);
    }
}
