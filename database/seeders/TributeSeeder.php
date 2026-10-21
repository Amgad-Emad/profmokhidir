<?php

namespace Database\Seeders;

use App\Constants\TributeRelationship;
use App\Models\Tribute;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Seeder;

class TributeSeeder extends Seeder
{
    /**
     * Six approved tributes (the first of them featured as the frontispiece of
     * the book of remembrance) and three awaiting review. All placeholder copy.
     */
    public function run(UserRepositoryInterface $users): void
    {
        if (Tribute::query()->exists()) {
            return;
        }

        $admin = $users->findByEmail('admin@example.com');

        $approved = [
            ['Ahmed El-Sheikh', TributeRelationship::Student, 'Khartoum, Sudan', '[Placeholder] He never answered a question with an answer. He answered with a better question, and then waited, with that patient half-smile, until you found your own way to it. I still hear him when I teach.', true],
            ['Dr. Leila Mahmoud', TributeRelationship::Colleague, 'Wad Madani, Sudan', '[Placeholder] A generous colleague who read every draft we sent him as if it were his own, and returned it a little clearer than it left.', false],
            ['Sara Khidir', TributeRelationship::Family, 'London, United Kingdom', "[Placeholder] At home he was the same as in the lecture hall: curious, kind and unhurried. The books were everywhere, and so was the welcome.\n\nWe miss him every day.", false],
            ['Prof. John Whitfield', TributeRelationship::Colleague, 'Reading, United Kingdom', '[Placeholder] We met at a conference in 1979 and corresponded for forty years. His letters were lessons in how to disagree gracefully.', false],
            ['Hassan Babiker', TributeRelationship::Student, 'Doha, Qatar', '[Placeholder] He taught me to look twice before answering, and three times before writing it down. This text is placeholder copy.', false],
            ['Mariam Osman', TributeRelationship::Friend, 'Omdurman, Sudan', '[Placeholder] A friend of the family for half a century. His lessons continue in every student he taught, and in all of us who knew him.', false],
        ];

        foreach ($approved as $index => [$name, $relationship, $location, $message, $featured]) {
            $factory = $featured ? Tribute::factory()->featured() : Tribute::factory()->approved($admin);

            $factory->create([
                'name' => $name,
                'relationship' => $relationship,
                'location' => $location,
                'message' => $message,
                'approved_by' => $admin?->getKey(),
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
