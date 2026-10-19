<?php

namespace Database\Seeders;

use App\Models\Commentary;
use App\Services\CommentaryService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommentarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(CommentaryService $commentaries): void
    {
        if (Commentary::query()->exists()) {
            return;
        }

        $items = [
            ['On teaching agronomy in a changing climate', '2010-02-14', ['education', 'climate']],
            ['What the Gezira taught us about patience', '2011-09-03', ['research', 'sudan']],
            ['A letter to new graduates', '2013-06-21', ['mentorship', 'education']],
            ['Why soil deserves a seat at the policy table', '2015-11-09', ['policy', 'agriculture']],
            ['Remembering my own teachers', '2017-04-30', ['mentorship']],
        ];

        foreach ($items as $index => [$title, $date, $tags]) {
            $commentaries->create([
                'title' => '[Placeholder] '.$title,
                'date' => $date,
                'summary' => '[Placeholder] A short summary of this commentary, to be replaced with approved text.',
                'body' => '<p>[Placeholder] Opening paragraph of the commentary. All text on this page is placeholder copy awaiting approval.</p><blockquote>[Placeholder] Knowledge is the light we pass on.</blockquote><p>[Placeholder] Closing paragraph.</p>',
                'external_url' => null,
                'tags' => $tags,
                'byline' => 'Prof. Mohamed Osman Khidir',
                'status' => 'published',
                'sort_order' => $index + 1,
            ]);
        }
    }
}
