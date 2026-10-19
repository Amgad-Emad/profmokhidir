<?php

namespace Database\Seeders;

use App\Constants\TributeRelationship;
use App\Models\Tribute;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(UserRepositoryInterface $users): void
    {
        if (Tribute::query()->exists()) {
            return;
        }

        $admin = $users->findByEmail('admin@example.com');

        $approved = [
            ['Ahmed El-Sheikh', TributeRelationship::Student, 'Khartoum, Sudan'],
            ['Dr. Leila Mahmoud', TributeRelationship::Colleague, 'Wad Madani, Sudan'],
            ['Sara Khidir', TributeRelationship::Family, 'London, United Kingdom'],
            ['Prof. John Whitfield', TributeRelationship::Colleague, 'Reading, United Kingdom'],
            ['Hassan Babiker', TributeRelationship::Student, 'Doha, Qatar'],
            ['Mariam Osman', TributeRelationship::Friend, 'Omdurman, Sudan'],
        ];

        foreach ($approved as $index => [$name, $relationship, $location]) {
            Tribute::factory()->approved($admin)->create([
                'name' => $name,
                'relationship' => $relationship,
                'location' => $location,
                'message' => '[Placeholder] A tribute message remembering Professor Khidir as a teacher and mentor. This text is placeholder copy.',
                'sort_order' => $index + 1,
            ]);
        }

        $pending = [
            ['Ibrahim Nour', TributeRelationship::Student, 'Kassala, Sudan'],
            ['Dr. Elena Rossi', TributeRelationship::Colleague, 'Rome, Italy'],
            ['Khalid Abbas', TributeRelationship::Other, 'Nairobi, Kenya'],
        ];

        foreach ($pending as [$name, $relationship, $location]) {
            Tribute::factory()->pending()->create([
                'name' => $name,
                'relationship' => $relationship,
                'location' => $location,
                'message' => '[Placeholder] A pending tribute awaiting moderation. This text is placeholder copy.',
            ]);
        }
    }
}
