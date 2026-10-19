<?php

use App\Constants\ContentStatus;
use App\Constants\TeachingAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The optional file is a medialibrary collection (`file`), stored on the public
 * disk for public materials and the private disk for members-only ones.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teaching_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('course')->nullable()->index();
            $table->string('topic')->nullable()->index();
            $table->string('type', 32)->index();
            $table->text('description')->nullable();
            $table->string('version', 50)->nullable();
            $table->date('material_date')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('access', 20)->default(TeachingAccess::Public->value)->index();
            $table->string('status', 20)->default(ContentStatus::default()->value)->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->fullText(['title', 'course', 'topic', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_materials');
    }
};
