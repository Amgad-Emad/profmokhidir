<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raw page views of the first-party, cookieless analytics (App\Constants\Analytics).
 * One row per page view, updated by its engagement beacons. Nothing personal is
 * kept: visitor_hash is a SHA-256 of IP + user agent + site id under a salt that
 * changes every day (and is then deleted), the IP and user agent themselves are
 * never stored, and view_id is a random id the page makes for itself. Rows are
 * rolled up nightly into the analytics_daily_* tables and deleted after 30 days.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('view_id')->unique();
            $table->char('visitor_hash', 64);
            $table->timestamp('occurred_at');
            $table->string('path', 255);
            $table->string('title', 160)->nullable();
            $table->string('section', 30);
            $table->string('referrer_domain', 120)->nullable();
            $table->char('country', 2);
            $table->string('device', 10);
            $table->string('browser', 30);
            $table->string('utm_source', 60)->nullable();
            $table->unsignedInteger('engaged_seconds')->default(0);
            $table->unsignedTinyInteger('scroll_depth')->default(0);
            $table->timestamps();

            $table->index('occurred_at');
            $table->index(['visitor_hash', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
