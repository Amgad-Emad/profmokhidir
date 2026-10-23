<?php

use App\Constants\ThemeSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The theme is Light or Dark only. An account that never chose one (formerly
     * "system") holds NULL: the device preference decides until the person clicks
     * the toggle, and resources/js/theme.js pins the theme the device resolves to
     * on the first visit from a device that still remembers "system".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string(ThemeSettings::COLUMN, 16)->nullable()->default(null)->change();
        });

        DB::table('users')
            ->where(ThemeSettings::COLUMN, ThemeSettings::LEGACY_SYSTEM)
            ->update([ThemeSettings::COLUMN => null]);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNull(ThemeSettings::COLUMN)
            ->update([ThemeSettings::COLUMN => ThemeSettings::LEGACY_SYSTEM]);

        Schema::table('users', function (Blueprint $table) {
            $table->string(ThemeSettings::COLUMN, 16)->default(ThemeSettings::LEGACY_SYSTEM)->nullable(false)->change();
        });
    }
};
