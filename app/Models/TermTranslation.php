<?php

namespace App\Models;

use App\Constants\TermVocabulary;
use Database\Factories\TermTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A grouping term's name in another language: the Arabic for an Other
 * Information category, a teaching course or topic, or a commentary tag. The
 * term itself stays the English key. Read and written only through
 * TermTranslationRepository and TermTranslationService.
 */
#[Fillable(['vocabulary', 'term', 'locale', 'label'])]
class TermTranslation extends Model
{
    /** @use HasFactory<TermTranslationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['vocabulary' => TermVocabulary::class];
    }
}
