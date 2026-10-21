<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Editors may file a recording under another decade than its date gives, and feature one at the top of Videos.
            $table->unsignedSmallInteger('decade')->nullable()->after('date');
            $table->boolean('is_featured')->default(false)->after('decade');

            $table->index(['status', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_featured']);
            $table->dropColumn(['decade', 'is_featured']);
        });
    }
};
