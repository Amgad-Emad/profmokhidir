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
        Schema::table('teaching_materials', function (Blueprint $table) {
            // Short description of the course, shown once under its heading (the first filled one in display order).
            $table->text('course_description')->nullable()->after('course');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teaching_materials', function (Blueprint $table) {
            $table->dropColumn('course_description');
        });
    }
};
