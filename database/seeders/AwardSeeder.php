<?php

namespace Database\Seeders;

use App\Models\Award;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AwardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Award::query()->exists()) {
            return;
        }

        $awards = [
            ['Order of Merit in Science', '[Placeholder] Republic of Sudan', 1986],
            ['Distinguished Teaching Award', 'University of Khartoum', 1991],
            ['Fellow', '[Placeholder] African Academy of Sciences', 1995],
            ['Lifetime Achievement in Agronomy', '[Placeholder] Sudanese Society of Agronomy', 2005],
            ['Honorary Doctorate', '[Placeholder] University of Gezira', 2010],
            ['Mentor of the Year', '[Placeholder] Association of Agricultural Graduates', 2014],
        ];

        foreach ($awards as $index => [$name, $organisation, $year]) {
            Award::factory()->published()->create([
                'name' => '[Placeholder] '.$name,
                'organisation' => $organisation,
                'year' => $year,
                'description' => '[Placeholder] Awarded in recognition of contributions to agricultural science and teaching.',
                'source_url' => null,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
