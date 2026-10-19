<?php

use App\Constants\TributeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitor tributes. `email` and `ip_hash` are private and never rendered.
 * published_at mirrors approved_at so the shared published() ordering applies.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('relationship', 20)->index();
            $table->string('location')->nullable();
            $table->string('message', 2000);
            $table->string('email')->nullable();
            $table->string('status', 20)->default(TributeStatus::default()->value)->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'sort_order', 'approved_at']);
            $table->fullText(['name', 'message']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tributes');
    }
};
