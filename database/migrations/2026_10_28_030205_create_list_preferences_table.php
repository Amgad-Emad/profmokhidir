<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last-used view of each admin list per user (search, filters, sort, tab)
 * and the row density they chose there, restored when they open the screen
 * again without a query string.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('screen', 64);
            $table->json('state')->nullable();
            $table->string('density', 16)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'screen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_preferences');
    }
};
