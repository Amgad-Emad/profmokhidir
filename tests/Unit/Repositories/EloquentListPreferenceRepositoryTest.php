<?php

namespace Tests\Unit\Repositories;

use App\Constants\AdminNavigation;
use App\Constants\ListDensity;
use App\Models\ListPreference;
use App\Models\User;
use App\Repositories\Contracts\ListPreferenceRepositoryInterface;
use Database\Seeders\ListPreferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Each user's last-used view and density of each admin list.
 */
class EloquentListPreferenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ListPreferenceRepositoryInterface $preferences;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preferences = $this->app->make(ListPreferenceRepositoryInterface::class);
    }

    #[Test]
    public function one_row_per_user_and_screen_keeps_the_state_and_density_apart(): void
    {
        $user = User::factory()->editor()->create();

        $this->assertNull($this->preferences->find($user, AdminNavigation::PUBLICATIONS));

        $this->preferences->save($user, AdminNavigation::PUBLICATIONS, ['filters' => ['status' => 'draft']]);
        $this->preferences->save($user, AdminNavigation::PUBLICATIONS, null, ListDensity::Compact);
        $this->preferences->save($user, AdminNavigation::TRIBUTES, ['q' => 'salma']);

        $publications = $this->preferences->find($user, AdminNavigation::PUBLICATIONS);
        $this->assertSame(['filters' => ['status' => 'draft']], $publications->state);
        $this->assertSame(ListDensity::Compact, $publications->density);
        $this->assertSame(['q' => 'salma'], $this->preferences->find($user, AdminNavigation::TRIBUTES)->state);
        $this->assertNull($this->preferences->find($user, AdminNavigation::TRIBUTES)->density);
        $this->assertSame(2, ListPreference::query()->count());

        $this->preferences->forget($user, AdminNavigation::TRIBUTES);
        $this->assertNull($this->preferences->find($user, AdminNavigation::TRIBUTES));

        // Deleting the account takes its preferences with it.
        $user->delete();
        $this->assertSame(0, ListPreference::query()->count());
    }

    #[Test]
    public function the_factory_and_seeder_give_useful_preferences(): void
    {
        $preference = ListPreference::factory()->forScreen(AdminNavigation::AWARDS)->drafts()->compact()->create();

        $this->assertSame(AdminNavigation::AWARDS, $preference->screen);
        $this->assertSame(['status' => 'draft'], $preference->state['filters']);
        $this->assertSame(ListDensity::Compact, $preference->density);
        $this->assertInstanceOf(User::class, $preference->user);

        $editor = User::factory()->editor()->create(['email' => 'editor@example.com']);
        $this->seed(ListPreferenceSeeder::class);
        $this->seed(ListPreferenceSeeder::class);

        $seeded = $this->preferences->find($editor, AdminNavigation::PUBLICATIONS);
        $this->assertSame('draft', $seeded->state['filters']['status']);
        $this->assertSame(1, ListPreference::query()->where('user_id', $editor->getKey())->count());
    }

    #[Test]
    public function the_table_choices_are_kept_beside_the_view_and_density(): void
    {
        $user = User::factory()->editor()->create();

        $this->preferences->save($user, AdminNavigation::PUBLICATIONS, ['q' => 'soil'], ListDensity::Compact);
        $this->preferences->saveTable($user, AdminNavigation::PUBLICATIONS, ['authors' => true]);
        $this->preferences->saveTable($user, AdminNavigation::PUBLICATIONS, null, 50);

        $preference = $this->preferences->find($user, AdminNavigation::PUBLICATIONS);
        $this->assertSame(['authors' => true], $preference->columns);
        $this->assertSame(50, $preference->per_page);
        $this->assertSame(['q' => 'soil'], $preference->state);
        $this->assertSame(ListDensity::Compact, $preference->density);

        $this->preferences->saveTable($user, AdminNavigation::PUBLICATIONS, []);
        $this->assertSame([], $this->preferences->find($user, AdminNavigation::PUBLICATIONS)->columns);
        $this->assertSame(1, ListPreference::query()->count());

        $factory = ListPreference::factory()->forScreen(AdminNavigation::AWARDS)->tableChoices(['updated_at'], 100)->create();
        $this->assertSame(['updated_at' => false], $factory->columns);
    }
}
