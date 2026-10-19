<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Placeholder dataset for local development. Every text field is marked
 * "[Placeholder]" so nothing here is mistaken for approved content.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            ProfileSeeder::class,
            CvEntrySeeder::class,
            PublicationSeeder::class,
            AwardSeeder::class,
            VideoSeeder::class,
            AlbumSeeder::class,
            TeachingMaterialSeeder::class,
            CommentarySeeder::class,
            OtherItemSeeder::class,
            TributeSeeder::class,
            ChatSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
