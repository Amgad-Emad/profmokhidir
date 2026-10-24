<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics rollup per day and page: views, unique visitors, engaged seconds
 * and the sum of the deepest scroll mark of each view (for the average). Kept
 * for 25 months.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_daily_pages', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('path', 255);
            $table->string('title', 160)->nullable();
            $table->string('section', 30);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedBigInteger('engaged_seconds')->default(0);
            $table->unsignedInteger('scroll_depth_total')->default(0);
            $table->timestamps();

            $table->unique(['day', 'path']);
            $table->index(['section', 'day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_pages');
    }
};
