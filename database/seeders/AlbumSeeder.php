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

        Album::factory()->published()->withPhotos(4)->create([
            'title' => '[Placeholder] In the field',
            'slug' => 'in-the-field',
            'description' => '[Placeholder] Field visits and research stations across the decades.',
            'sort_order' => 1,
        ]);

        Album::factory()->published()->withPhotos(3)->create([
            'title' => '[Placeholder] Lecture halls and students',
            'slug' => 'lecture-halls-and-students',
            'description' => '[Placeholder] Teaching, graduations and gatherings with former students.',
            'sort_order' => 2,
        ]);
    }
}
