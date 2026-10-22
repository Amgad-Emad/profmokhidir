<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminUx;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The command palette's content search (GET /admin/search?q=).
 */
class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function editors_get_matching_titles_as_json(): void
    {
        $publication = Publication::factory()->create(['title' => 'Groundnut yields']);

        $this->actingAs(User::factory()->editor()->create())
            ->getJson(route(AdminUx::SEARCH_ROUTE, ['q' => 'groundnut']))
            ->assertOk()
            ->assertJsonPath('results.0.label', 'Groundnut yields')
            ->assertJsonPath('results.0.url', route('admin.content.publications.edit', ['publication' => $publication->getKey()]))
            ->assertJsonPath('results.0.group', 'Publications');
    }

    #[Test]
    public function an_empty_or_overlong_query_is_handled(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->getJson(route(AdminUx::SEARCH_ROUTE))->assertOk()->assertExactJson(['results' => []]);
        $this->actingAs($editor)->getJson(route(AdminUx::SEARCH_ROUTE, ['q' => str_repeat('a', 101)]))->assertUnprocessable();
    }

    #[Test]
    public function guests_and_members_cannot_search(): void
    {
        $this->getJson(route(AdminUx::SEARCH_ROUTE, ['q' => 'soil']))->assertUnauthorized();
        $this->actingAs(User::factory()->member()->create())->getJson(route(AdminUx::SEARCH_ROUTE, ['q' => 'soil']))->assertForbidden();
    }
}
