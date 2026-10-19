<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\ContentTypes;
use App\Models\Award;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every admin content screen renders for editors and is forbidden to members.
 */
class ContentScreensTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function listTypes(): array
    {
        $types = [];

        foreach (array_keys(ContentTypes::all()) as $key) {
            if ($key !== ContentTypes::PROFILE) {
                $types[$key] = [$key];
            }
        }

        return $types;
    }

    #[Test]
    #[DataProvider('listTypes')]
    public function the_index_create_and_edit_screens_render_for_editors(string $type): void
    {
        $editor = User::factory()->editor()->create();
        $definition = ContentTypes::get($type);
        $record = $definition['model']::factory()->published()->create();
        $parameter = ContentTypes::routeParameter($type);

        $this->actingAs($editor)->get(route(ContentTypes::indexRoute($type)))
            ->assertOk()
            ->assertSee($definition['plural'])
            ->assertSee('data-tab="trash"', false)
            ->assertSee(route(ContentTypes::editRoute($type), [$parameter => $record->getKey()]), false)
            ->assertSee('View on site');

        $this->actingAs($editor)->get(route(ContentTypes::createRoute($type)))
            ->assertOk()
            ->assertSee('New '.strtolower($definition['singular']))
            ->assertSee('data-status-field', false);

        $this->actingAs($editor)->get(route(ContentTypes::editRoute($type), [$parameter => $record->getKey()]))
            ->assertOk()
            ->assertSee('Edit '.strtolower($definition['singular']))
            ->assertSee('data-view-on-site', false);
    }

    #[Test]
    #[DataProvider('listTypes')]
    public function members_may_not_open_the_content_screens(string $type): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get(route(ContentTypes::indexRoute($type)))->assertForbidden();
        $this->actingAs($member)->get(route(ContentTypes::createRoute($type)))->assertForbidden();
    }

    #[Test]
    public function the_profile_media_library_and_tribute_screens_render(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get(route(ContentTypes::indexRoute(ContentTypes::PROFILE)))->assertOk()->assertSee('About & profile')->assertSee('Portrait');
        $this->actingAs($editor)->get(route(AdminNavigation::routeFor(AdminNavigation::MEDIA_LIBRARY)))->assertOk()->assertSee('Media library')->assertSee('Upload images');
        $this->actingAs($editor)->get(route(AdminNavigation::routeFor(AdminNavigation::TRIBUTES)))->assertOk()->assertSee('Review tributes')->assertSee('data-tab="pending"', false);
    }

    #[Test]
    public function trashed_records_cannot_be_edited(): void
    {
        $editor = User::factory()->editor()->create();
        $award = Award::factory()->create();
        $award->delete();

        $this->actingAs($editor)->get(route(ContentTypes::editRoute(ContentTypes::AWARD), ['award' => $award->getKey()]))->assertNotFound();
    }

    #[Test]
    public function built_sections_no_longer_use_the_placeholder(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (AdminNavigation::built() as $key) {
            $this->actingAs($admin)->get(route(AdminNavigation::routeFor($key)))->assertOk()->assertDontSee('This section is on its way.');
        }

        $this->assertArrayNotHasKey(AdminNavigation::TRIBUTES, AdminNavigation::placeholders());
        $this->assertArrayHasKey(AdminNavigation::USERS, AdminNavigation::placeholders());
    }
}
