<?php

use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Database\Migrations\Migration;

/**
 * Some media items stored their Arabic alt text, caption and place under literal
 * "translations.ar.caption" keys beside the nested translations. The flat copy
 * shadowed the nested one on every read, so Arabic edits looked lost. Fold the
 * flat keys into the nested form (a filled nested value wins).
 */
return new class extends Migration
{
    public function up(): void
    {
        app(MediaRepositoryInterface::class)->foldTranslationKeys();
    }

    /**
     * Nothing to undo: the nested form is the only one the code reads and writes.
     */
    public function down(): void {}
};
