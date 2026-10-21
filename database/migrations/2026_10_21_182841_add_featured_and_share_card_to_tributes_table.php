<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The book of remembrance: editors feature one approved tribute as the
 * frontispiece (is_featured / featured_at), and each approved tribute's
 * share card is rendered once and its path cached (share_card_path).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tributes', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('sort_order')->index();
            $table->timestamp('featured_at')->nullable()->after('is_featured');
            $table->string('share_card_path')->nullable()->after('featured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tributes', function (Blueprint $table) {
            $table->dropIndex(['is_featured']);
            $table->dropColumn(['is_featured', 'featured_at', 'share_card_path']);
        });
    }
};
