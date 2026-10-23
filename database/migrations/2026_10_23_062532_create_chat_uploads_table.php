<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The temporary area for chat attachments. A member's file is staged here
 * (its media row hangs off the upload, on the private disk) and is moved onto
 * the message inside the send transaction, which sets message_id and
 * claimed_at. Unclaimed uploads older than a day are pruned by the scheduler.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('original_name');
            $table->string('extension', 10);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->foreignId('message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'claimed_at']);
            $table->index(['claimed_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_uploads');
    }
};
