<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\Analytics;
use App\Constants\ContentTypes;
use App\Constants\ListFilterType;
use App\Livewire\Admin\Analytics\EngagingPages;
use App\Livewire\Admin\Community\Applications;
use App\Livewire\Admin\Community\Reports;
use App\Livewire\Admin\Media\Library;
use App\Livewire\Admin\Pages\Index as PageIndex;
use App\Livewire\Admin\Tributes\Moderate;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Models\Album;
use App\Models\ChatChannel;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use App\Services\PageService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Every admin list screen uses x-admin.list-toolbar: each filter of each
 * screen goes into the address and comes back from it (a bookmark, a reload,
 * the Back button), each view shows its count, and the toolbar is the only
 * filter markup on the screen.
 */
class ListToolbarScreensTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->app->make(PageService::class)->install();
        Album::factory()->create(['title' => 'Field trips']);
        ChatChannel::factory()->general()->create();
        Publication::factory()->published()->featured()->create(['year' => 1990]);
        Tribute::factory()->pending()->create();
    }

    /**
     * Screen => [component, route name, mount parameters].
     *
     * @return array<string, array{class-string, string|null, array<string, mixed>}>
     */
    public static function screens(): array
    {
        $screens = [];

        foreach (array_keys(ContentTypes::all()) as $type) {
            if ($type === ContentTypes::PROFILE) {
                continue;
            }

            $screens[$type] = [null, ContentTypes::indexRoute($type), []];
        }

        return $screens + [
            'tributes' => [Moderate::class, AdminNavigation::routeFor(AdminNavigation::TRIBUTES), []],
            'users' => [UserIndex::class, AdminNavigation::routeFor(AdminNavigation::USERS), []],
            'media library' => [Library::class, AdminNavigation::routeFor(AdminNavigation::MEDIA_LIBRARY), []],
            'applications' => [Applications::class, AdminNavigation::routeFor(AdminNavigation::APPLICATIONS), []],
            'chat reports' => [Reports::class, AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS), []],
            'pages' => [PageIndex::class, AdminNavigation::routeFor(AdminNavigation::PAGES), []],
            'analytics pages' => [EngagingPages::class, null, ['from' => '2026-10-01', 'to' => '2026-10-28']],
        ];
    }

    /**
     * @param  class-string|null  $component
     * @param  array<string, mixed>  $parameters
     */
    #[Test]
    #[DataProvider('screens')]
    public function every_filter_round_trips_through_the_address(?string $component, ?string $route, array $parameters): void
    {
        $component ??= $this->contentComponent($route);
        $filters = $this->invokeOn($component, 'listFilters', $parameters);
        $this->assertNotEmpty($filters);

        foreach ($filters as $filter) {
            /** @var ListFilter $filter */
            $values = array_map('strval', array_keys($filter->options));

            if ($values === []) {
                continue;
            }

            $value = $filter->type === ListFilterType::Multi && count($values) > 1 ? $values[0].','.$values[1] : $values[0];

            // From the address into the component...
            $test = Livewire::actingAs($this->admin)->withQueryParams(['filters' => [$filter->key => $value]])->test($component, $parameters)
                ->assertSet('filters', [$filter->key => $value])
                ->assertSee('data-filter-chip="'.$filter->key.'"', false)
                ->assertSee('data-result-count', false);

            // ...and from a click back into it (the address follows the property).
            $test->call('clearFilters')->assertSet('filters', [])
                ->call('toggleFilter', $filter->key, $values[0])
                ->assertSet('filters', [$filter->key => $values[0]]);

            // Values the screen does not know are dropped rather than trusted.
            Livewire::actingAs($this->admin)->withQueryParams(['filters' => [$filter->key => 'not-a-value', 'password' => 'x']])->test($component, $parameters)
                ->assertSet('filters', $this->defaultFilters($component, $parameters));
        }

        if ($route !== null) {
            $first = $filters[0];
            $this->actingAs($this->admin)->get(route($route, ['filters' => [$first->key => array_key_first($first->options)], 'q' => 'a', 'sort' => 'nonsense']))
                ->assertOk()
                ->assertSee('data-list-toolbar', false)
                ->assertSee('data-filter-chip="'.$first->key.'"', false)
                ->assertDontSee('filter-chips', false);
        }
    }

    /**
     * @param  class-string|null  $component
     * @param  array<string, mixed>  $parameters
     */
    #[Test]
    #[DataProvider('screens')]
    public function every_view_shows_its_count_and_applies_its_filters(?string $component, ?string $route, array $parameters): void
    {
        $component ??= $this->contentComponent($route);
        $views = $this->invokeOn($component, 'listViews', $parameters);
        $test = Livewire::actingAs($this->admin)->test($component, $parameters);

        $html = $test->html();

        foreach ($views as $view) {
            /** @var ListView $view */
            $count = $this->invokeOn($component, 'listViewCount', $parameters, [$view]);
            $this->assertMatchesRegularExpression('/data-view="'.preg_quote($view->key, '/').'">\s*'.preg_quote(e($view->label), '/').'<span class="list-toolbar__view-count">'.$count.'<\/span>/', $html);
        }

        foreach ($views as $view) {
            $test->call('applyView', $view->key);
            $this->assertSame($view->key, $test->instance()->activeView());
            $this->assertSame($view->trash, $test->instance()->listToolbar(0)['trash']);
        }
    }

    #[Test]
    public function list_routes_keep_their_permissions(): void
    {
        $editor = User::factory()->editor()->create();
        $member = User::factory()->member()->create();

        foreach ([AdminNavigation::USERS, AdminNavigation::APPLICATIONS, AdminNavigation::MEMBERS, AdminNavigation::CHAT_REPORTS] as $item) {
            $this->actingAs($editor)->get(route(AdminNavigation::routeFor($item), ['filters' => ['status' => 'x']]))->assertForbidden();
        }

        foreach ([AdminNavigation::PUBLICATIONS, AdminNavigation::TRIBUTES, AdminNavigation::MEDIA_LIBRARY, AdminNavigation::PAGES] as $item) {
            $this->actingAs($editor)->get(route(AdminNavigation::routeFor($item)))->assertOk();
            $this->actingAs($member)->get(route(AdminNavigation::routeFor($item), ['q' => 'a']))->assertForbidden();
        }

        // The toolbar's actions run on the component, which still checks the user on mount.
        Livewire::actingAs($editor)->test(UserIndex::class)->assertForbidden();
        Livewire::actingAs($member)->test(Moderate::class)->assertForbidden();
        Livewire::actingAs($member)->test(EngagingPages::class, ['from' => '2026-10-01', 'to' => '2026-10-28'])->assertForbidden();
        $this->actingAs($editor)->get(route(Analytics::ADMIN_ROUTE))->assertOk()->assertSee('data-list-toolbar', false);
    }

    /**
     * @param  class-string  $component
     * @param  array<string, mixed>  $parameters
     * @param  list<mixed>  $arguments
     */
    private function invokeOn(string $component, string $method, array $parameters, array $arguments = []): mixed
    {
        $instance = Livewire::actingAs($this->admin)->test($component, $parameters)->instance();

        return (new ReflectionMethod($instance, $method))->invoke($instance, ...$arguments);
    }

    /**
     * The filters a screen opens with (its default view's).
     *
     * @param  class-string  $component
     * @param  array<string, mixed>  $parameters
     * @return array<string, string>
     */
    private function defaultFilters(string $component, array $parameters): array
    {
        return Livewire::actingAs($this->admin)->test($component, $parameters)->get('filters');
    }

    /**
     * @return class-string
     */
    private function contentComponent(string $route): string
    {
        return Str::before((string) $this->app['router']->getRoutes()->getByName($route)->getAction('uses'), '@');
    }
}
