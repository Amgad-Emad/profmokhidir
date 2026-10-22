<?php

namespace Tests\Feature\Errors;

use App\Constants\ErrorPages;
use App\Constants\Search;
use App\Constants\Sections;
use App\Constants\Seo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The error pages are evergreen with the MK mark, one Cormorant line, a search
 * box that needs no script, the chapters of the site and a pill button home. 404 and 403 are reached through real requests; 419, 500
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
        $this->assertMatchesRegularExpression('/<body class="[^"]*glow-brass[^"]*bg-surface[^"]*text-ink[^"]*">/', $html);
        $this->assertStringContainsString('data-error-code="'.$code.'"', $html);
        $this->assertStringContainsString('class="brand-mark brand-mark--ring"', $html, 'The MK mark in its ring');
        $this->assertStringContainsString('<p class="t-label text-accent-text">'.ErrorPages::LABEL_PREFIX.' '.$code.'</p>', $html);
        $this->assertStringContainsString('<h1 class="t-heading text-ink-strong">'.e($page['title']).'</h1>', $html);
        $this->assertStringContainsString('<p class="t-quote max-w-xl">'.e($page['line']).'</p>', $html);
        $this->assertStringContainsString('<a href="'.route('home').'" class="btn-pill-filled mt-4">'.ErrorPages::HOME_LABEL.'</a>', $html);

        // The search box is a plain GET form to /search: it works on a page that loads no script.
        $this->assertMatchesRegularExpression('/<form method="get" action="'.preg_quote(route(Search::ROUTE), '/').'" role="search"/', $html);
        $this->assertStringContainsString('<label id="error-search-label" for="error-search" class="t-label form-label">'.ErrorPages::SEARCH_LABEL.'</label>', $html);
        $this->assertMatchesRegularExpression('/<input class="form-control__input" id="error-search" name="'.Search::PARAM.'"[^>]*type="search"/', $html);

        // Every chapter is linked, in navigation order.
        $this->assertStringContainsString('<nav class="error-page__chapters w-full max-w-xl" aria-label="Chapters">', $html);
        $this->assertStringContainsString(ErrorPages::CHAPTERS_LABEL, $html);
        preg_match_all('/class="error-page__chapter link-draw smallcaps">([^<]+)<\/a>/', $html, $chapters);
        $this->assertSame(array_map(fn (array $section): string => e($section['label']), array_values(Sections::definitions())), $chapters[1]);

        $this->assertStringNotContainsString('livewire', $html);
        // The only script is the inline theme boot in the head, which settles the scheme before paint.
        $this->assertSame(1, substr_count($html, '<script'));
        $this->assertStringContainsString('<script data-theme-boot>', $html);
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
    }

    #[Test]
    public function the_missing_page_line_says_it_was_misplaced_in_the_archive(): void
    {
        $this->assertSame('This page has been misplaced in the archive.', ErrorPages::get(ErrorPages::NOT_FOUND)['line']);
    }
}
