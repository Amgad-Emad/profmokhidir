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
        Schema::table('commentaries', function (Blueprint $table) {
            $table->text('epigraph')->nullable()->after('summary');
            $table->string('epigraph_attribution')->nullable()->after('epigraph');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commentaries', function (Blueprint $table) {
            $table->dropColumn(['epigraph', 'epigraph_attribution']);
        });
    }
};
