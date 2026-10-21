<?php

namespace Database\Seeders;

use App\Models\HomeQuote;
use Illuminate\Database\Seeder;

class HomeQuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (HomeQuote::query()->exists()) {
            return;
        }

        $quotes = [
            ['Knowledge is the light we pass on.', null, '[Placeholder] Lecture notes'],
            ['He taught us how to ask the right questions.', '[Placeholder] A former student', 'Tribute'],
            ['A field is read slowly, season by season, like a book.', null, '[Placeholder] Essay'],
        ];

        foreach ($quotes as $index => [$quote, $attribution, $source]) {
            HomeQuote::factory()->published()->create([
                'quote' => '[Placeholder] '.$quote,
                'attribution' => $attribution,
                'source' => $source,
                'sort_order' => $index + 1,
            ]);
        }

        HomeQuote::factory()->draft()->create([
            'quote' => '[Placeholder] A draft quotation that is not yet shown.',
            'sort_order' => count($quotes) + 1,
        ]);
    }
}
