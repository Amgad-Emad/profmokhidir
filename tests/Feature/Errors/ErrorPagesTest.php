<?php

namespace Tests\Feature\Errors;

use App\Constants\ErrorPages;
use App\Constants\Seo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The error pages are evergreen with the MK mark, one Cormorant line and a
 * pill button home. 404 and 403 are reached through real requests; 419, 500
 * and 503 are rendered directly because the test client never trips them.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return iterable<string, array{int}>
     */
    public static function codes(): iterable
    {
        foreach (ErrorPages::codes() as $code) {
            yield (string) $code => [$code];
        }
    }

    #[Test]
    #[DataProvider('codes')]
    public function each_error_view_is_branded(int $code): void
    {
        $html = $this->view('errors.'.$code)->__toString();

        $this->assertBranded($html, $code);
    }

    #[Test]
    public function a_missing_page_shows_the_branded_404(): void
    {
        $response = $this->get('/this-page-does-not-exist')->assertNotFound();

        $this->assertBranded($response->getContent(), ErrorPages::NOT_FOUND);
    }

    #[Test]
    public function a_forbidden_page_shows_the_branded_403(): void
    {
        $response = $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.community.applications'))
            ->assertForbidden();

        $this->assertBranded($response->getContent(), ErrorPages::FORBIDDEN);
    }

    private function assertBranded(string $html, int $code): void
    {
        $page = ErrorPages::get($code);

        $this->assertStringContainsString('<title>'.e($page['title']).Seo::TITLE_SEPARATOR.Seo::SITE_NAME.'</title>', $html);
        $this->assertStringContainsString('<meta name="robots" content="'.Seo::ROBOTS_NOINDEX.'">', $html);
        $this->assertMatchesRegularExpression('/<body class="[^"]*glow-brass[^"]*bg-evergreen[^"]*text-champagne[^"]*">/', $html);
        $this->assertStringContainsString('data-error-code="'.$code.'"', $html);
        $this->assertStringContainsString('class="brand-mark brand-mark--ring"', $html, 'The MK mark in its ring');
        $this->assertStringContainsString('<p class="t-label text-brass">'.ErrorPages::LABEL_PREFIX.' '.$code.'</p>', $html);
        $this->assertStringContainsString('<h1 class="t-heading text-ivory">'.e($page['title']).'</h1>', $html);
        $this->assertStringContainsString('<p class="t-quote max-w-xl">'.e($page['line']).'</p>', $html);
        $this->assertStringContainsString('<a href="'.route('home').'" class="btn-pill-filled mt-4">'.ErrorPages::HOME_LABEL.'</a>', $html);
        $this->assertStringNotContainsString('livewire', $html);
        $this->assertStringNotContainsString('<nav', $html);
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
    }
}
