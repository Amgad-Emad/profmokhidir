<?php

namespace Database\Seeders;

use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use App\Services\TeachingMaterialService;
use App\Support\PlaceholderDocument;
use Illuminate\Database\Seeder;

/**
 * Placeholder materials with a one-page placeholder PDF each, attached through
 * the service so members-only files land on the private disk.
 */
class TeachingMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(TeachingMaterialService $materials): void
    {
        if (TeachingMaterial::query()->exists()) {
            return;
        }

        $rows = [
            ['AGR 205 Principles of Agronomy', 'Course outline', TeachingMaterialType::Syllabus, 'Syllabus 2004/05', TeachingAccess::Public],
            ['AGR 205 Principles of Agronomy', 'Crop growth and development', TeachingMaterialType::Slides, 'Lecture 3 slides', TeachingAccess::Public],
            ['AGR 205 Principles of Agronomy', 'Field practical: seed rate trials', TeachingMaterialType::Exercise, 'Practical 2', TeachingAccess::Members],
            ['AGR 301 Crop Physiology', 'Photosynthesis in C4 cereals', TeachingMaterialType::Notes, 'Lecture notes', TeachingAccess::Public],
            ['AGR 301 Crop Physiology', 'Water relations reading list', TeachingMaterialType::Reading, 'Reading list', TeachingAccess::Public],
            ['AGR 301 Crop Physiology', 'Problem set: stomatal conductance', TeachingMaterialType::Exercise, 'Problem set 4', TeachingAccess::Members],
            ['AGR 412 Soil Fertility', 'Nitrogen cycle in irrigated vertisols', TeachingMaterialType::Slides, 'Lecture 6 slides', TeachingAccess::Public],
            ['AGR 412 Soil Fertility', 'Past examination papers', TeachingMaterialType::Other, 'Exam archive', TeachingAccess::Members],
        ];

        $descriptions = [
            'AGR 205 Principles of Agronomy' => '[Placeholder] An introduction to field crops, their growth and management, for second-year students.',
            'AGR 301 Crop Physiology' => '[Placeholder] How crops capture light, water and nutrients, and how the season shapes yield.',
            'AGR 412 Soil Fertility' => '[Placeholder] Nutrient cycles in irrigated and rain-fed soils, with practical fertility assessment.',
        ];

        foreach ($rows as $index => [$course, $topic, $type, $title, $access]) {
            $material = $materials->create([
                'title' => '[Placeholder] '.$title,
                'course' => $course,
                'course_description' => $descriptions[$course] ?? null,
                'topic' => $topic,
                'type' => $type,
                'description' => '[Placeholder] Description of this material, to be replaced with approved text.',
                'version' => 'v1.0',
                'material_date' => now()->subYears(15 + $index)->toDateString(),
                'external_url' => $index === 4 ? 'https://example.com/placeholder-reading-list' : null,
                'access' => $access,
                'status' => 'published',
                'sort_order' => $index + 1,
            ]);

            if ($index !== 4) {
                $materials->attachFile($material, PlaceholderDocument::pdf('[Placeholder] '.$title));
            }
        }
    }
}
