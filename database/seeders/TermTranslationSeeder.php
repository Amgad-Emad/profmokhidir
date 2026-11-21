<?php

namespace Database\Seeders;

use App\Constants\Locales;
use App\Constants\TermVocabulary;
use App\Services\TermTranslationService;
use Illuminate\Database\Seeder;

/**
 * Arabic names for the categories, types, courses, topics and tags used by
 * the placeholder dataset, so its Arabic pages read in Arabic throughout.
 */
class TermTranslationSeeder extends Seeder
{
    public function run(TermTranslationService $terms): void
    {
        $terms->save([
            TermVocabulary::OtherCategory->value => [
                'Announcements' => 'إعلانات',
                'Archive' => 'الأرشيف',
                'In the press' => 'في الصحافة',
            ],
            TermVocabulary::OtherType->value => [
                'announcement' => 'إعلان',
                'press' => 'صحافة',
                'resource' => 'مصدر',
            ],
            TermVocabulary::CommentaryTag->value => [
                'agriculture' => 'الزراعة',
                'climate' => 'المناخ',
                'education' => 'التعليم',
                'mentorship' => 'الإرشاد الأكاديمي',
                'policy' => 'السياسات',
                'research' => 'البحث العلمي',
                'sudan' => 'السودان',
            ],
            TermVocabulary::TeachingCourse->value => [
                'AGR 205 Principles of Agronomy' => 'زرع 205 مبادئ علم المحاصيل',
                'AGR 301 Crop Physiology' => 'زرع 301 فسيولوجيا المحاصيل',
                'AGR 412 Soil Fertility' => 'زرع 412 خصوبة التربة',
            ],
            TermVocabulary::TeachingTopic->value => [
                'Course outline' => 'وصف المقرر',
                'Crop growth and development' => 'نمو المحاصيل وتطورها',
                'Field practical: seed rate trials' => 'تطبيق حقلي: تجارب معدلات البذور',
                'Photosynthesis in C4 cereals' => 'البناء الضوئي في محاصيل الحبوب رباعية الكربون',
                'Water relations reading list' => 'قائمة قراءات العلاقات المائية',
                'Problem set: stomatal conductance' => 'مسائل: التوصيل الثغري',
                'Nitrogen cycle in irrigated vertisols' => 'دورة النيتروجين في الأتربة الطينية المروية',
                'Past examination papers' => 'امتحانات سابقة',
            ],
            TermVocabulary::PublicationTopic->value => [
                'Agronomy' => 'علم المحاصيل',
                'Crop physiology' => 'فسيولوجيا المحاصيل',
                'Soil fertility' => 'خصوبة التربة',
                'Agricultural education' => 'التعليم الزراعي',
            ],
        ], Locales::AR);
    }
}
