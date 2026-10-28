<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\ColourThemeService;
use Illuminate\Database\Seeder;

/**
 * The suggested colour themes (App\Constants\ThemePresets). None is made
 * active, so the site keeps the brand defaults from tokens.css. Safe to re-run.
 */
class ColourThemeSeeder extends Seeder
{
    public function run(ColourThemeService $themes): void
    {
        $themes->installPresets();
    }
}
