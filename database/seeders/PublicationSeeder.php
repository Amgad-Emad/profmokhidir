<?php

namespace Database\Seeders;

use App\Constants\PublicationType;
use App\Models\Publication;
use App\Services\PublicationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(PublicationService $publications): void
    {
        if (Publication::query()->exists()) {
            return;
        }

        $items = [
            [PublicationType::Book, 1978, 'Principles of Dryland Agronomy', '[Placeholder] Khartoum University Press'],
            [PublicationType::Book, 1995, 'Soil Fertility in the Sudan: A Field Guide', '[Placeholder] Khartoum University Press'],
            [PublicationType::JournalArticle, 1971, 'Water use efficiency of sorghum under rain-fed conditions', '[Placeholder] Experimental Agriculture'],
            [PublicationType::JournalArticle, 1976, 'Nitrogen response of irrigated wheat in the Gezira', '[Placeholder] Journal of Agricultural Science'],
            [PublicationType::JournalArticle, 1983, 'Photoperiod sensitivity in Sudanese sorghum landraces', '[Placeholder] Field Crops Research'],
            [PublicationType::JournalArticle, 1990, 'Salinity tolerance of forage legumes in the Nile valley', '[Placeholder] Plant and Soil'],
            [PublicationType::JournalArticle, 2002, 'Long-term fertility trends under continuous cotton', '[Placeholder] Soil Use and Management'],
            [PublicationType::Chapter, 1986, 'Agronomy of the Sudanese savanna', '[Placeholder] In: Agriculture in Semi-Arid Africa'],
            [PublicationType::Chapter, 1999, 'Teaching crop science in resource-limited settings', '[Placeholder] In: Agricultural Education in Africa'],
            [PublicationType::ConferencePaper, 1974, 'Sowing date and sorghum yield in central Sudan', '[Placeholder] Proc. Sudan Agricultural Society'],
            [PublicationType::ConferencePaper, 1988, 'Drought escape strategies in millet', '[Placeholder] Proc. International Dryland Congress'],
            [PublicationType::ConferencePaper, 2008, 'Fifty years of agronomic research in the Gezira', '[Placeholder] Proc. FAO Regional Workshop'],
            [PublicationType::Report, 1992, 'Report on the Dryland Sorghum Improvement Programme', '[Placeholder] Agricultural Research Corporation'],
            [PublicationType::Report, 2001, 'Soil fertility map of the Gezira Scheme', '[Placeholder] FAO'],
            [PublicationType::Other, 2012, 'Reflections on a life in agronomy', '[Placeholder] Sudan Journal of Agricultural Research (essay)'],
        ];

        foreach ($items as $index => [$type, $year, $title, $venue]) {
            $publications->create([
                'title' => '[Placeholder] '.$title,
                'type' => $type,
                'authors' => 'Khidir, M. O.',
                'year' => $year,
                'venue_publisher' => $venue,
                'doi' => sprintf('10.1000/placeholder.%03d', $index + 1),
                'citation' => sprintf('[Placeholder] Khidir, M. O. (%d). %s. %s.', $year, $title, $venue),
                'abstract' => '[Placeholder] Abstract text to be replaced with the approved summary of this work.',
                'url' => null,
                'status' => 'published',
                'sort_order' => $index + 1,
            ]);
        }
    }
}
