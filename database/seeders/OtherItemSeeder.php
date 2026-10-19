<?php

namespace Database\Seeders;

use App\Models\OtherItem;
use App\Services\OtherItemService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OtherItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(OtherItemService $items): void
    {
        if (OtherItem::query()->exists()) {
            return;
        }

        $rows = [
            ['Memorial lecture series announced', 'announcement', 'Announcements', '2024-01-15', null],
            ['Profile in the university alumni magazine', 'press', 'In the press', '2016-10-02', 'https://example.com/placeholder-profile'],
            ['Archive of correspondence donated to the library', 'resource', 'Archive', '2021-05-20', null],
        ];

        foreach ($rows as $index => [$title, $type, $category, $date, $url]) {
            $items->create([
                'title' => '[Placeholder] '.$title,
                'item_type' => $type,
                'summary' => '[Placeholder] Summary of this item, to be replaced with approved text.',
                'body' => '<p>[Placeholder] Body text for this item.</p>',
                'date' => $date,
                'category' => $category,
                'destination_url' => $url,
                'status' => 'published',
                'sort_order' => $index + 1,
            ]);
        }
    }
}
