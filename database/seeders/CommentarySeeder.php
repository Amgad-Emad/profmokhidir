<?php

namespace Database\Seeders;

use App\Models\Commentary;
use App\Services\CommentaryService;
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
                'body' => $index === 0 ? $this->essay() : '<p>[Placeholder] Opening paragraph of the commentary. All text on this page is placeholder copy awaiting approval.</p><blockquote>[Placeholder] Knowledge is the light we pass on.</blockquote><p>[Placeholder] Closing paragraph.</p>',
                'external_url' => null,
                'tags' => $tags,
                'epigraph' => $index === 0 ? '[Placeholder] The field is the first classroom, and the season the first examiner.' : null,
                'epigraph_attribution' => $index === 0 ? '[Placeholder] From a lecture, 1984' : null,
                'byline' => 'Prof. Mohamed Osman Khidir',
                'status' => 'published',
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * A placeholder essay long enough to show the reading layout: headings for the
     * table of contents and footnote markers for sidenotes.
     */
    private function essay(): string
    {
        $paragraph = '[Placeholder] This paragraph stands in for the approved text of the essay. It is long enough to show the reading measure, the rhythm of the lines and the way a paragraph settles on the page, so that the layout can be judged before the real words arrive. All text on this page is placeholder copy awaiting approval.';

        return '<p>'.$paragraph.'[^1]</p><p>'.$paragraph.'</p>'
            .'<h2>[Placeholder] The field as a classroom</h2><p>'.$paragraph.'[^2]</p><p>'.$paragraph.'</p>'
            .'<blockquote>[Placeholder] Knowledge is the light we pass on.</blockquote>'
            .'<h2>[Placeholder] Teaching with the seasons</h2><p>'.$paragraph.'</p>'
            .'<h3>[Placeholder] A note on practicals</h3><p>'.$paragraph.'</p>'
            .'<h2>[Placeholder] What remains</h2><p>'.$paragraph.'</p><p>'.$paragraph.'</p>'
            .'<p>[^1]: [Placeholder] A sidenote: a short aside the reader may take or leave.</p>'
            .'<p>[^2]: [Placeholder] A second sidenote, with a reference to be supplied.</p>';
    }
}
