<?php

namespace Database\Seeders;

use App\Models\Album;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlbumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Album::query()->exists()) {
            return;
        }

        Album::factory()->published()->withWall([[1600, 1067, 1972], [800, 1000, 1974], [1600, 800, 1979], [900, 900, 1981], [1200, 900, 1985]], 'Wad Madani')->create([
            'title' => '[Placeholder] In the field',
            'slug' => 'in-the-field',
            'description' => '[Placeholder] Field visits and research stations across the decades.',
            'sort_order' => 1,
        ]);

        Album::factory()->published()->withWall([[1600, 1067, 1990], [1000, 1250, 1996], [1600, 1067, 2004], [1400, 1050, 2011]])->create([
            'title' => '[Placeholder] Lecture halls and students',
            'slug' => 'lecture-halls-and-students',
            'description' => '[Placeholder] Teaching, graduations and gatherings with former students.',
            'sort_order' => 2,
        ]);
    }
}
