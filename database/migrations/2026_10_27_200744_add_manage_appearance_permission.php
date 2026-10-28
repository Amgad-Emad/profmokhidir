<?php

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers the "manage appearance" permission (App\Constants\Permissions)
 * and gives it to administrators on databases seeded before it existed. The
 * suggested themes are installed by ColourThemeSeeder and, on a database that
 * has none, when the Appearance screen first opens.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(RoleService::class)->syncRolesAndPermissions();
    }

    public function down(): void
    {
        // The permission is harmless to keep; Permissions::all() no longer lists it once removed from the code.
    }
};
