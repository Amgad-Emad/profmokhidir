<?php

namespace Database\Seeders;

use App\Constants\CvSection;
use App\Models\CvEntry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CvEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (CvEntry::query()->exists()) {
            return;
        }

        $entries = [
            [CvSection::Education, 'B.Sc. (Agriculture)', 'University of Khartoum', 1958, 1962],
            [CvSection::Education, 'M.Sc. (Agronomy)', '[Placeholder] University of Reading', 1963, 1965],
            [CvSection::Education, 'Ph.D. (Crop Physiology)', '[Placeholder] University of California, Davis', 1966, 1969],
            [CvSection::Appointment, 'Lecturer in Agronomy', 'University of Khartoum', 1969, 1975],
            [CvSection::Appointment, 'Associate Professor of Agronomy', 'University of Khartoum', 1975, 1982],
            [CvSection::Appointment, 'Professor of Agronomy', 'University of Khartoum', 1982, 2005],
            [CvSection::Appointment, 'Professor Emeritus', 'University of Khartoum', 2005, null],
            [CvSection::Leadership, 'Dean, Faculty of Agriculture', 'University of Khartoum', 1988, 1994],
            [CvSection::Leadership, 'Member, National Council for Research', '[Placeholder] Republic of Sudan', 1990, 1998],
            [CvSection::Project, 'Dryland Sorghum Improvement Programme', '[Placeholder] Agricultural Research Corporation', 1984, 1992],
            [CvSection::Project, 'Soil Fertility Mapping of the Gezira Scheme', '[Placeholder] FAO / Gezira Board', 1996, 2001],
            [CvSection::Achievement, 'Founding editor, Sudan Journal of Agricultural Research', '[Placeholder] Sudanese Society of Agronomy', 1979, 1986],
        ];

        foreach ($entries as $index => [$section, $title, $organisation, $start, $end]) {
            CvEntry::factory()->published()->create([
                'section' => $section,
                'title' => '[Placeholder] '.$title,
                'organisation' => $organisation,
                'role_description' => '[Placeholder] Description of this role or qualification, to be replaced with approved text.',
                'start_year' => $start,
                'end_year' => $end,
                'last_updated_at' => now()->subDays(30)->toDateString(),
                'sort_order' => $index + 1,
            ]);
        }
    }
}
