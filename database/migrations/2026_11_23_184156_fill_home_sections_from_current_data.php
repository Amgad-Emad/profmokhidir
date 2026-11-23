<?php

use App\Services\HomeSectionsImportService;
use Illuminate\Database\Migrations\Migration;

/**
 * The home page's chapters, figures and cards are now edited in Admin › Pages ›
 * Home instead of being drawn from Awards, the albums, the counts and the
 * section pages. Fill them with what the home page showed, so nothing changes
 * for visitors and the editor opens on the current data.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(HomeSectionsImportService::class)->import();
    }

    /**
     * Nothing to undo: the filled lists are ordinary page content.
     */
    public function down(): void {}
};
