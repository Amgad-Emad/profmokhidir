<?php

namespace Database\Seeders;

use App\Constants\ContentStatus;
use App\Constants\MediaProperties;
use App\Services\ProfileService;
use App\Support\PlaceholderImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProfileService $profiles): void
    {
        if ($profiles->current() !== null) {
            return;
        }

        $profile = $profiles->save([
            'name' => 'Mohamed Osman Khidir',
            'title' => 'Professor Emeritus of Agronomy',
            'affiliation' => '[Placeholder] Faculty of Agriculture, University of Khartoum',
            'short_bio' => '[Placeholder] Teacher, scholar and mentor whose work on crop science and soil fertility shaped a generation of agronomists. This summary is placeholder text awaiting approved copy.',
            'full_bio' => '<p>[Placeholder] Professor Mohamed Osman Khidir devoted his life to agricultural science and to the students who sought his guidance. This biography is placeholder text and must be replaced with approved copy before launch.</p>'
                .'<h2>Early years</h2><p>[Placeholder] Born in Sudan, he studied agriculture before continuing his training abroad, returning to teach and to build research programmes at home.</p>'
                .'<h2>Teaching and research</h2><p>[Placeholder] Over several decades he supervised graduate students, published widely on crop physiology and soil fertility, and served on national and international scientific bodies.</p>'
                .'<blockquote>[Placeholder] Knowledge is the light we pass on.</blockquote>'
                .'<h2>Legacy</h2><p>[Placeholder] His lessons continue in every student he taught.</p>',
            'expertise' => ['Agronomy', 'Crop physiology', 'Soil fertility', 'Dryland farming', 'Agricultural education'],
            'key_facts' => [
                ['label' => 'Field', 'value' => 'Agronomy'],
                ['label' => 'Affiliation', 'value' => '[Placeholder] University of Khartoum'],
                ['label' => 'Students supervised', 'value' => '[Placeholder] 60+'],
                ['label' => 'Years teaching', 'value' => '[Placeholder] 40'],
            ],
            'contact_email_public' => null,
            'contact_note' => '[Placeholder] Enquiries about the archive may be sent through the Scientific Community.',
            'status' => ContentStatus::Published,
            'sort_order' => 1,
        ]);

        $profiles->setPortrait($profile, PlaceholderImage::grey(800, 1000, 'Portrait'), [
            MediaProperties::ALT_TEXT => 'Placeholder portrait of Professor Mohamed Osman Khidir',
            MediaProperties::CAPTION => '[Placeholder] Portrait',
            MediaProperties::PLACE => 'Khartoum',
            MediaProperties::YEAR => 1998,
            MediaProperties::CREDIT => '[Placeholder] Family archive',
        ]);
    }
}
