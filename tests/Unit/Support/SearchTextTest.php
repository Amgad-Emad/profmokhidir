<?php

namespace Tests\Unit\Support;

use App\Support\SearchText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SearchTextTest extends TestCase
{
    #[Test]
    public function arabic_letter_forms_diacritics_and_the_tatweel_are_folded(): void
    {
        $this->assertSame('الاراضي', SearchText::normalise('الأَرَاضِي'));
        $this->assertSame('اسلام', SearchText::normalise('إسـلام'));
        $this->assertSame('امال', SearchText::normalise('آمال'));
        $this->assertSame('مكتبه', SearchText::normalise('مكتبة'));
        $this->assertSame('علي', SearchText::normalise('على'));
        $this->assertSame('محمد', SearchText::normalise('مُحَمَّد'));
    }

    #[Test]
    public function latin_text_is_lower_cased_and_markup_dropped(): void
    {
        $this->assertSame('soil fertility in the sudan', SearchText::normalise('<p>Soil <em>Fertility</em></p>  in the   Sudan'));
    }

    #[Test]
    public function every_language_of_every_value_is_joined(): void
    {
        $this->assertSame('on water عن الماء khidir', SearchText::from([['en' => 'On water', 'ar' => 'عن الماء'], 'Khidir', null, '']));
    }
}
