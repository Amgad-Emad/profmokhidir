<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics rollup: one row per day with the site-wide figures (unique
 * visitors, visitors who came back for another visit that day, visits, page
 * views and engaged seconds). Kept for 25 months.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_daily_totals', function (Blueprint $table) {
            $table->id();
            $table->date('day')->unique();
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('returning_visitors')->default(0);
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->unsignedBigInteger('engaged_seconds')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_totals');
    }
};
