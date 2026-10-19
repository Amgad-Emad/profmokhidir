<?php

use App\Constants\CommunityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Community applications live on users. Roles are held by spatie/laravel-permission,
 * so there is deliberately no role column.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('community_status', 20)->default(CommunityStatus::default()->value)->after('email')->index();
            $table->string('affiliation')->nullable()->after('community_status');
            $table->string('academic_field')->nullable()->after('affiliation');
            $table->text('reason_for_joining')->nullable()->after('academic_field');
            $table->foreignId('reviewed_by')->nullable()->after('reason_for_joining')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['community_status']);
            $table->dropColumn(['community_status', 'affiliation', 'academic_field', 'reason_for_joining', 'reviewed_at', 'review_note']);
        });
    }
};
