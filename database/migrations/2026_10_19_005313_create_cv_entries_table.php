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
        Schema::create('cv_entries', function (Blueprint $table) {
            $table->id();
            $table->string('section', 32)->index();
            $table->string('title');
            $table->string('organisation')->nullable();
            $table->text('role_description')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable()->comment('NULL means present');
            $table->date('last_updated_at')->nullable();
            $table->string('status', 20)->default(ContentStatus::default()->value)->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['section', 'sort_order', 'start_year']);
            $table->fullText(['title', 'organisation', 'role_description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cv_entries');
    }
};
