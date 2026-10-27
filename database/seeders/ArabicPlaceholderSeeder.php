<?php

namespace Database\Seeders;

use App\Services\ContentTranslationService;
use Illuminate\Database\Seeder;

/**
 * Arabic placeholder copy, marked "[نص مؤقت]", for every seeded content item
 * and image that has English but no Arabic yet. Runs after the content
 * seeders; text already written in Arabic is left alone.
 */
class ArabicPlaceholderSeeder extends Seeder
{
    public function run(ContentTranslationService $translations): void
    {
        $translations->seedArabicPlaceholders();
    }
}
