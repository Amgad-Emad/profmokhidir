<?php

namespace Database\Seeders;

use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeachingMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (TeachingMaterial::query()->exists()) {
            return;
        }

        $materials = [
            ['AGR 205 Principles of Agronomy', 'Course outline', TeachingMaterialType::Syllabus, 'Syllabus 2004/05', TeachingAccess::Public],
            ['AGR 205 Principles of Agronomy', 'Crop growth and development', TeachingMaterialType::Slides, 'Lecture 3 slides', TeachingAccess::Public],
            ['AGR 205 Principles of Agronomy', 'Field practical: seed rate trials', TeachingMaterialType::Exercise, 'Practical 2', TeachingAccess::Members],
            ['AGR 301 Crop Physiology', 'Photosynthesis in C4 cereals', TeachingMaterialType::Notes, 'Lecture notes', TeachingAccess::Public],
            ['AGR 301 Crop Physiology', 'Water relations reading list', TeachingMaterialType::Reading, 'Reading list', TeachingAccess::Public],
            ['AGR 301 Crop Physiology', 'Problem set: stomatal conductance', TeachingMaterialType::Exercise, 'Problem set 4', TeachingAccess::Members],
            ['AGR 412 Soil Fertility', 'Nitrogen cycle in irrigated vertisols', TeachingMaterialType::Slides, 'Lecture 6 slides', TeachingAccess::Public],
            ['AGR 412 Soil Fertility', 'Past examination papers', TeachingMaterialType::Other, 'Exam archive', TeachingAccess::Members],
        ];

        foreach ($materials as $index => [$course, $topic, $type, $title, $access]) {
            TeachingMaterial::factory()->published()->create([
                'title' => '[Placeholder] '.$title,
                'course' => $course,
                'topic' => $topic,
                'type' => $type,
                'description' => '[Placeholder] Description of this material, to be replaced with approved text.',
                'version' => 'v1.0',
                'material_date' => now()->subYears(15 + $index)->toDateString(),
                'external_url' => null,
                'access' => $access,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
