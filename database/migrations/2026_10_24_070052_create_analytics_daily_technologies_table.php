<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics rollup per day for the small lists: device type, browser family
 * and UTM source (App\Constants\AnalyticsDimension), with unique visitors and
 * page views. Kept for 25 months.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_daily_technologies', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('dimension', 20);
            $table->string('value', 60);
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->timestamps();

            $table->unique(['day', 'dimension', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_technologies');
    }
};
