<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics rollup per day and country (ISO 3166 alpha-2, "ZZ" when unknown):
 * unique visitors and page views. Kept for 25 months.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_daily_countries', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->char('country', 2);
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->timestamps();

            $table->unique(['day', 'country']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_countries');
    }
};
