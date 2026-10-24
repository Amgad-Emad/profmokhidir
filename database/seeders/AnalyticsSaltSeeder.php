<?php

namespace Database\Seeders;

use App\Services\VisitorHashService;
use Illuminate\Database\Seeder;

/**
 * Today's analytics salt (and no older one), made the way the first page view
 * of a day makes it. Safe to re-run.
 */
class AnalyticsSaltSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(VisitorHashService $hashes): void
    {
        $hashes->rotate();
    }
}
