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
            SettingSeeder::class,
            ProfileSeeder::class,
            CvEntrySeeder::class,
            PublicationSeeder::class,
            AwardSeeder::class,
            VideoSeeder::class,
            AlbumSeeder::class,
            TeachingMaterialSeeder::class,
            CommentarySeeder::class,
            OtherItemSeeder::class,
            HomeQuoteSeeder::class,
            TributeSeeder::class,
            ChatSeeder::class,
            ChatUploadSeeder::class,
            RevisionSeeder::class,
            AuditLogSeeder::class,
            // Local only: 90 days of demo visitor analytics (it calls the salt and event seeders).
            AnalyticsDemoSeeder::class,
        ]);
    }
}
