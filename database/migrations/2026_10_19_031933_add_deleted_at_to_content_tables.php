<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft deletes for every content type the admin can trash and restore.
 * The single profile row is never deleted, so it keeps no deleted_at.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'cv_entries', 'publications', 'awards', 'videos', 'albums',
        'teaching_materials', 'commentaries', 'other_items',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->softDeletes()->after('sort_order');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
