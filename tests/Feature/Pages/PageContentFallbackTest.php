<?php

namespace Tests\Feature\Pages;

use App\Constants\ContentStatus;
use App\Constants\Pages;
use App\Constants\SocialCards;
use App\Models\User;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Services\PageContentService;
use App\Services\PageService;
use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The public pages read their words from the database, fall back to the
 * built-in copy whenever a block (or the page) is missing or unpublished, and
 * take each page's search title, description and share image.
 */
class PageContentFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageContentSeeder::class);
    }

    #[Test]
    public function the_pages_read_their_words_from_the_database(): void
    {
        $this->publish(Pages::AWARDS, ['title' => 'Honours', 'intro' => 'Prizes he received.', 'empty' => 'No honours yet.']);
        $this->publish(Pages::NAVIGATION, ['utility_line' => 'Remembering Professor Khidir']);

        $this->get('/awards')->assertOk()
            ->assertSee('<title>Honours · Professor Mohamed Osman Khidir</title>', false)
            ->assertSee('<meta name="description" content="Prizes he received.">', false)
            ->assertSee('No honours yet.')
            ->assertSee('Remembering Professor Khidir');

        // The heading and intro travel with the section: breadcrumbs, menus and the home cards.
        $this->get('/')->assertSee('Remembering Professor Khidir');
    }

    #[Test]
    public function a_deleted_block_falls_back_to_the_built_in_copy(): void
    {
        $this->publish(Pages::AWARDS, ['intro' => 'An edited introduction.']);
        $this->get('/awards')->assertSee('An edited introduction.');

        app(PageRepositoryInterface::class)->deleteBlock(app(PageService::class)->findByKey(Pages::AWARDS), 'intro');
        app(PageContentService::class)->flush(Pages::AWARDS);

        $this->get('/awards')->assertOk()->assertDontSee('An edited introduction.');
    }

    #[Test]
    public function a_deleted_home_block_shows_nothing_instead_of_the_built_in_copy(): void
    {
        $this->publish(Pages::HOME, ['hero_tagline' => 'An edited tagline.']);
        $this->get('/')->assertSee('An edited tagline.');

        app(PageRepositoryInterface::class)->deleteBlock(app(PageService::class)->findByKey(Pages::HOME), 'hero_tagline');
        app(PageContentService::class)->flush(Pages::HOME);

        // Only the head keeps the built-in description; the page itself shows no tagline.
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('/<main.*<\/main>/s', $html, $main);
        $this->assertStringContainsString('<meta name="description" content="Teacher, scholar and mentor.', $html);
        $this->assertStringNotContainsString('Teacher, scholar and mentor.', $main[0]);
        $this->assertStringNotContainsString('An edited tagline.', $html);
    }

    #[Test]
    public function every_page_renders_with_no_page_rows_at_all(): void
    {
        app(PageRepositoryInterface::class)->all()->each->delete();
        app(PageContentService::class)->flush();

        foreach (['/', '/about', '/community', '/tributes', '/tributes/new'] as $path) {
            $this->get($path)->assertOk();
        }

        // The home page shows only what an editor entered: with no rows, none of its built-in copy.
        $this->get('/')->assertDontSee('A life in chapters')->assertDontSee('<section class="hero', false);
        $this->get('/does-not-exist')->assertNotFound()->assertSee('This page has been misplaced in the archive.');
    }

    #[Test]
    public function a_draft_page_shows_visitors_the_built_in_copy(): void
    {
        $this->publish(Pages::CV, ['empty' => 'Coming soon.']);
        $page = app(PageService::class)->findByKey(Pages::CV);
        app(PageService::class)->unpublish($page);

        $this->assertSame(ContentStatus::Draft, $page->refresh()->status);
        $this->get('/cv')->assertDontSee('Coming soon.')->assertSee('The curriculum vitae is being compiled');
    }

    #[Test]
    public function the_copyright_year_and_privacy_numbers_are_filled_in(): void
    {
        $this->publish(Pages::NAVIGATION, ['copyright' => '© :year The family of Professor Khidir.']);

        $this->get('/about')->assertSee('© '.date('Y').' The family of Professor Khidir.');
    }

    #[Test]
    public function the_search_and_sharing_block_sets_the_head_of_its_page_only(): void
    {
        $this->publish(Pages::PUBLICATIONS, ['seo' => ['title' => 'His papers', 'description' => 'Every paper he wrote.', 'media_id' => null, 'alt_text' => null]]);

        $this->get('/publications')
            ->assertSee('<title>His papers · Professor Mohamed Osman Khidir</title>', false)
            ->assertSee('<meta property="og:description" content="Every paper he wrote.">', false)
            // The page heading is unchanged: the search title lives in the head only.
            ->assertSee('data-rise>Publications</h1>', false);

        $this->get('/awards')->assertSee('<title>Awards · Professor Mohamed Osman Khidir</title>', false)
            ->assertSee('<meta property="og:image" content="'.asset(SocialCards::SITE).'">', false);
        $this->get('/tributes')->assertSee('<meta property="og:image" content="'.asset(SocialCards::TRIBUTES).'">', false);
    }

    #[Test]
    public function the_standing_messages_use_the_community_copy(): void
    {
        $this->publish(Pages::COMMUNITY, ['chat_intro' => 'Only members read these messages.', 'approved' => 'You are in.', 'pending' => 'We are reading it.']);

        $this->actingAs(User::factory()->admin()->create())->get(route('community'))->assertSee('Only members read these messages.');
        $this->actingAs(User::factory()->member()->create())->get(route('community'))->assertSee('You are in.');
        $this->actingAs(User::factory()->applicant()->create())->get(route('community'))->assertSee('We are reading it.');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function publish(string $page, array $values): void
    {
        app(PageService::class)->publish(app(PageService::class)->findByKey($page), $values);
    }
}
