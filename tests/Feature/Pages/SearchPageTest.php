<?php

namespace Tests\Feature\Pages;

use App\Constants\Search;
use App\Constants\Seo;
use App\Constants\TeachingAccess;
use App\Models\Commentary;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The plain /search page (no JavaScript needed) and the JSON the header dialog
 * asks for. Records are committed (DatabaseTruncation) so the fulltext index
 * sees them; the tables are cleared again afterwards.
 */
class SearchPageTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    #[Test]
    public function the_page_shows_a_plain_get_form_and_the_prompt_until_something_is_typed(): void
    {
        $html = $this->get(route(Search::ROUTE))->assertOk()->getContent();

        $this->assertStringContainsString('<title>'.Search::TITLE.Seo::TITLE_SEPARATOR.Seo::SITE_NAME.'</title>', $html);
        $this->assertStringContainsString('<meta name="robots" content="'.Seo::ROBOTS_NOINDEX.'">', $html);
        $this->assertMatchesRegularExpression('/<form method="get" action="'.preg_quote(route(Search::ROUTE), '/').'" role="search" class="search-form mt-12"/', $html);
        $this->assertMatchesRegularExpression('/<input id="search-query" name="'.Search::PARAM.'" type="search" value=""/', $html);
        $this->assertStringContainsString(Search::PROMPT, $html);
        $this->assertStringContainsString(Search::MEMBERS_NOTE, $html);
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertStringNotContainsString('livewire', $html);
    }

    #[Test]
    public function results_are_grouped_by_section_with_the_matched_words_marked_and_drafts_left_out(): void
    {
        Publication::factory()->published()->create(['title' => 'Irrigation in the Gezira', 'slug' => 'irrigation-gezira', 'abstract' => 'Canals and irrigation economics.']);
        Publication::factory()->draft()->create(['title' => 'Irrigation draft']);
        Commentary::factory()->published()->create(['title' => 'On irrigation', 'slug' => 'on-irrigation', 'summary' => 'Why irrigation still matters.']);

        $html = $this->get(route(Search::ROUTE, [Search::PARAM => 'irrigation']))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Search: irrigation'.Seo::TITLE_SEPARATOR.Seo::SITE_NAME.'</title>', $html);
        $this->assertStringContainsString('value="irrigation"', $html);
        $this->assertStringContainsString('2 results in 2 sections', $html);
        $this->assertStringContainsString('<h2 id="search-group-publications"', $html);
        $this->assertStringContainsString('<h2 id="search-group-commentary"', $html);
        $this->assertStringContainsString('<mark class="search-mark">Irrigation</mark> in the Gezira', $html);
        $this->assertStringContainsString('href="'.route('publications').'#publication-irrigation-gezira"', $html);
        $this->assertStringContainsString('href="'.route('commentary.show', 'on-irrigation').'"', $html);
        $this->assertStringContainsString('Why <mark class="search-mark">irrigation</mark> still matters.', $html);
        $this->assertStringNotContainsString('Irrigation draft', $html);
        $this->assertStringContainsString('role="region" aria-live="polite" aria-label="Search results"', $html);
    }

    #[Test]
    public function nothing_found_shows_an_epigraph_and_the_chapters(): void
    {
        $html = $this->get(route(Search::ROUTE, [Search::PARAM => 'zzzz']))->assertOk()->getContent();

        $this->assertStringContainsString('class="empty-state flex flex-col items-start gap-5 py-12 search-results__empty"', $html);
        $this->assertStringContainsString(Search::NONE_FOUND, $html);
        $this->assertStringContainsString(Search::NONE_ATTRIBUTION, $html);
        $this->assertStringContainsString('<nav class="search-results__chapters" aria-label="Chapters">', $html);
        $this->assertStringContainsString('class="search-results__chapter link-draw smallcaps">Publications</a>', $html);
    }

    #[Test]
    public function members_only_teaching_files_appear_only_for_signed_in_members(): void
    {
        TeachingMaterial::factory()->published()->create(['title' => 'Public handout', 'description' => 'On soils.']);
        TeachingMaterial::factory()->published()->membersOnly()->create(['title' => 'Members workbook', 'description' => 'On soils.']);

        $this->get(route(Search::ROUTE, [Search::PARAM => 'soils']))->assertOk()
            ->assertSee('Public handout', false)
            ->assertDontSee('Members workbook', false);

        $this->actingAs(User::factory()->applicant()->create())
            ->get(route(Search::ROUTE, [Search::PARAM => 'soils']))->assertOk()
            ->assertDontSee('Members workbook', false);

        $this->actingAs(User::factory()->member()->create())
            ->get(route(Search::ROUTE, [Search::PARAM => 'soils']))->assertOk()
            ->assertSee('Members workbook', false)
            ->assertSee(TeachingAccess::MEMBERS_NOTE, false)
            ->assertDontSee(Search::MEMBERS_NOTE, false);
    }

    #[Test]
    public function the_dialog_receives_json_with_the_rendered_fragment(): void
    {
        Publication::factory()->published()->create(['title' => 'Cotton yields', 'abstract' => 'A note on cotton.']);

        $response = $this->getJson(route(Search::ROUTE, [Search::PARAM => 'cotton']))->assertOk();

        $response->assertJson(['query' => 'cotton', 'total' => 1, 'summary' => '1 result in 1 section']);
        $html = $response->json('html');
        $this->assertStringContainsString('<mark class="search-mark">Cotton</mark> yields', $html);
        $this->assertStringContainsString('class="search-results__summary t-label text-brass"', $html, 'Evergreen tone in the dialog');
        $this->assertStringNotContainsString('search-results__excerpt', $html, 'The dialog keeps results compact');
        $this->assertStringNotContainsString('<html', $html);

        $this->getJson(route(Search::ROUTE, [Search::PARAM => 'c']))->assertOk()->assertJson(['total' => 0, 'summary' => ''])->assertJsonFragment(['query' => 'c']);
    }

    #[Test]
    public function the_query_is_escaped_and_cut_to_the_maximum_length(): void
    {
        $html = $this->get(route(Search::ROUTE, [Search::PARAM => '<script>alert(1)</script>']))->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('value="&lt;script&gt;alert(1)&lt;/script&gt;"', $html);

        $long = str_repeat('soil ', 60);
        $html = $this->get(route(Search::ROUTE, [Search::PARAM => $long]))->assertOk()->getContent();
        preg_match('/<input id="search-query"[^>]*value="([^"]*)"/', $html, $value);
        $this->assertLessThanOrEqual(Search::MAX_LENGTH, mb_strlen($value[1]));
        $this->assertGreaterThan(Search::MAX_LENGTH - 5, mb_strlen($value[1]));
    }
}
