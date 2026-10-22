<?php

use App\Constants\Theme;
use App\Constants\ThemeSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The colour theme a signed-in user chose (App\Constants\Theme), kept with the account.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string(ThemeSettings::COLUMN, 16)->default(Theme::DEFAULT->value)->after('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(ThemeSettings::COLUMN);
        });
    }
};
