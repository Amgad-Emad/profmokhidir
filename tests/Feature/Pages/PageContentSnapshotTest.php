<?php

namespace Tests\Feature\Pages;

use App\Constants\ErrorPages;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Pages;
use App\Constants\SettingKeys;
use App\Services\HomeSectionsImportService;
use App\Services\PageService;
use App\Services\SettingService;
use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every public page renders the same words from the database as it did from
 * the hardcoded copy. The snapshots in tests/Fixtures/page-text were recorded
 * from the views before page content became editable (UPDATE_PAGE_SNAPSHOTS=1
 * records them again); here the pages are rendered with PageContentSeeder's
 * defaults in the database and compared line for line: the head metadata, then
 * the visible text of the body.
 */
class PageContentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private const string FIXTURES = 'tests/Fixtures/page-text';

    /**
     * @return iterable<string, array{string}>
     */
    public static function pages(): iterable
    {
        foreach (['/', '/about', '/cv', '/publications', '/awards', '/videos', '/pictures', '/teaching', '/commentary', '/community', '/other', '/tributes', '/tributes/new', '/privacy', '/tributes/thank-you'] as $path) {
            yield $path => [$path];
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageContentSeeder::class);
    }

    #[Test]
    #[DataProvider('pages')]
    public function each_page_renders_the_same_text_as_before(string $path): void
    {
        if ($path === '/privacy') {
            $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);
        }

        // The home sections are filled from the records, as the migration did when they moved into Admin › Pages › Home.
        // Empty first: the migration already filled them when the test database was built, before this test's records existed.
        $pages = app(PageService::class);
        $pages->install();
        $pages->publishBlocks($pages->findByKey(Pages::HOME), [HomeBlocks::CHAPTER_ITEMS => [], HomeBlocks::FIGURES => [], HomeBlocks::STORY_CARDS => []]);
        app(HomeSectionsImportService::class)->import();

        $response = $this->get($path)->assertOk();

        $this->assertMatchesSnapshot(trim($path, '/') ?: 'home', (string) $response->getContent());
    }

    #[Test]
    public function each_error_page_renders_the_same_text_as_before(): void
    {
        foreach (ErrorPages::codes() as $code) {
            $this->assertMatchesSnapshot('error-'.$code, $this->view('errors.'.$code)->__toString());
        }
    }

    private function assertMatchesSnapshot(string $name, string $html): void
    {
        $file = base_path(self::FIXTURES.'/'.str_replace('/', '-', $name).'.txt');
        $text = self::text($html);

        if (getenv('UPDATE_PAGE_SNAPSHOTS') === '1') {
            @mkdir(dirname($file), 0755, true);
            file_put_contents($file, $text);
        }

        $this->assertFileExists($file);
        $this->assertSame(file_get_contents($file), $text, sprintf('The text of %s changed.', $name));
    }

    /**
     * The title, description and share image, then the visible text one line per text node.
     */
    public static function text(string $html): string
    {
        $lines = [];

        foreach (['/<title>(.*?)<\/title>/s', '/<meta name="description" content="([^"]*)"/', '/<meta property="og:image" content="([^"]*)"/', '/<meta property="og:image:alt" content="([^"]*)"/'] as $pattern) {
            $lines[] = preg_match($pattern, $html, $match) ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5) : '';
        }

        $body = preg_match('/<body[^>]*>(.*)<\/body>/s', $html, $match) ? $match[1] : $html;
        $body = (string) preg_replace(['/<script\b.*?<\/script>/s', '/<style\b.*?<\/style>/s', '/<svg\b.*?<\/svg>/s', '/<template\b.*?<\/template>/s'], '', $body);

        foreach (preg_split('/<[^>]+>/', $body) ?: [] as $node) {
            $node = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($node, ENT_QUOTES | ENT_HTML5)));

            if ($node !== '') {
                $lines[] = $node;
            }
        }

        return implode("\n", $lines)."\n";
    }
}
