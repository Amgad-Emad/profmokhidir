<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The daily salt of the analytics visitor hash. One row per day: today's row
 * is made on the first page view of the day and every earlier row is deleted
 * at the same moment, so a visitor's hash cannot be linked across days.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_salts', function (Blueprint $table) {
            $table->id();
            $table->date('day')->unique();
            $table->char('salt', 64);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_salts');
    }
};
