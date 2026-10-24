<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics rollup per day and referring domain (an empty string for direct
 * visits or a referrer the browser withheld): unique visitors and page views.
 * Kept for 25 months.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_daily_referrers', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('referrer_domain', 120);
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->timestamps();

            $table->unique(['day', 'referrer_domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_referrers');
    }
};
