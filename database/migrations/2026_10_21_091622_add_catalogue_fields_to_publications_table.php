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
        Schema::table('publications', function (Blueprint $table) {
            // Editors mark a few works for the "Selected works" strip; the topic is the catalogue subject filter.
            $table->boolean('is_featured')->default(false)->after('url');
            $table->string('topic', 100)->nullable()->after('is_featured');

            $table->index(['status', 'is_featured']);
            $table->index('topic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_featured']);
            $table->dropIndex(['topic']);
            $table->dropColumn(['is_featured', 'topic']);
        });
    }
};
