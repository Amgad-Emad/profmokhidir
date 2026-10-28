<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colour themes (Admin › Appearance). `light` and `dark` hold the published
 * colours of each mode (token => hex, only the tokens someone chose; the rest
 * derive), `draft_light` / `draft_dark` the unpublished edits. One theme at a
 * time is active; presets are the read-only suggested templates, `based_on`
 * names the preset a custom theme was duplicated from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_preset')->default(false);
            $table->string('based_on', 100)->nullable();
            $table->json('light');
            $table->json('dark');
            $table->json('draft_light')->nullable();
            $table->json('draft_dark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
