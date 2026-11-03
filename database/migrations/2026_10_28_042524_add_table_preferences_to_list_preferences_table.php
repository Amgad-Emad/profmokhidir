<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The data table's own remembered choices per admin list: the columns the
 * user hid (or showed beyond the defaults) and the page size.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('list_preferences', function (Blueprint $table) {
            $table->json('columns')->nullable()->after('density');
            $table->unsignedSmallInteger('per_page')->nullable()->after('columns');
        });
    }

    public function down(): void
    {
        Schema::table('list_preferences', function (Blueprint $table) {
            $table->dropColumn(['columns', 'per_page']);
        });
    }
};
