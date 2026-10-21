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
        Schema::table('media', function (Blueprint $table) {
            // Album photos: a decade override for "Through the decades" (otherwise taken from the year) and a featured flag that gives the photo a larger place on the exhibition wall.
            $table->unsignedSmallInteger('decade')->nullable()->after('custom_properties');
            $table->boolean('is_featured')->default(false)->after('decade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['decade', 'is_featured']);
        });
    }
};
