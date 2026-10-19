<?php

use App\Constants\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->string('slug')->unique();
            $table->string('type', 32)->index();
            $table->text('authors')->nullable();
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->string('venue_publisher')->nullable();
            $table->string('doi')->nullable();
            $table->text('citation')->nullable();
            $table->text('abstract')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('status', 20)->default(ContentStatus::default()->value)->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'type', 'year']);
            $table->fullText(['title', 'authors', 'venue_publisher', 'abstract']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
