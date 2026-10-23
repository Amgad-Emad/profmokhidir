<?php

namespace Tests\Feature\Theme;

use App\Constants\Theme;
use App\Constants\ThemeSettings;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The 2026_10_23 migration that retires "system": the column becomes nullable and
 * every "system" account becomes NULL (the device decides until the person clicks).
 * Schema changes commit in MySQL, so this runs on real rows (DatabaseTruncation).
 */
class ResolveSystemThemeMigrationTest extends TestCase
{
    use DatabaseTruncation;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_23_080928_resolve_system_theme_on_users_table.php');
    }

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    #[Test]
    public function system_accounts_become_undecided_and_explicit_choices_survive(): void
    {
        $system = User::factory()->create();
        $dark = User::factory()->darkTheme()->create();
        $migration = $this->migration();

        // Back to the old schema, where an account without a choice held "system".
        $migration->down();

        try {
            $this->assertSame(ThemeSettings::LEGACY_SYSTEM, DB::table('users')->where('id', $system->id)->value(ThemeSettings::COLUMN));
        } finally {
            $migration->up();
        }

        $this->assertNull(DB::table('users')->where('id', $system->id)->value(ThemeSettings::COLUMN));
        $this->assertSame('dark', DB::table('users')->where('id', $dark->id)->value(ThemeSettings::COLUMN));
        $this->assertNull($system->fresh()->theme);
        $this->assertSame(Theme::Dark, $dark->fresh()->theme);
    }

    #[Test]
    public function new_accounts_start_without_a_theme(): void
    {
        $this->assertTrue(Schema::hasColumn('users', ThemeSettings::COLUMN));

        $id = DB::table('users')->insertGetId(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);

        $this->assertNull(DB::table('users')->where('id', $id)->value(ThemeSettings::COLUMN));
    }
}
