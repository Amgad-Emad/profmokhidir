<?php

namespace Database\Seeders;

use App\Services\PageService;
use Illuminate\Database\Seeder;

/**
 * The editable page copy (Admin › Pages): one published row per page and one
 * block per slot, each holding exactly the words the views were built with,
 * so the site reads the same before and after editors change anything.
 * Blocks already stored are left untouched, so seeding again never overwrites
 * an editor's words.
 */
class PageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(PageService $pages): void
    {
        $pages->install();
    }
}
