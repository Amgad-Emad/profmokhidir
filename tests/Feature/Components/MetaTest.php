<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MetaTest extends TestCase
{
    #[Test]
    public function it_joins_populated_parts_with_a_middle_dot_and_renders_dates_as_time(): void
    {
        $html = Blade::render('<x-meta :items="[\'Book\', null, \'\', 1978, $date, \'  Khartoum \']" class="mt-2" />', ['date' => Carbon::parse('2021-03-12')]);

        $this->assertStringContainsString('<p class="t-label text-bronze mt-2">Book · 1978 · <time datetime="2021-03-12">12 March 2021</time> · Khartoum</p>', $html);
    }

    #[Test]
    public function it_escapes_text_renders_nothing_when_empty_and_switches_ink_on_dark(): void
    {
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', Blade::render('<x-meta :items="[\'<b>x</b>\']" />'));
        $this->assertSame('', trim(Blade::render('<x-meta :items="[null, \'\']" />')));
        $this->assertStringContainsString('class="t-label text-brass"', Blade::render('<x-meta :items="[\'A\']" tone="ivory" />'));
    }

    #[Test]
    public function the_date_component_writes_a_time_element_in_the_long_format(): void
    {
        $html = Blade::render('<x-date :date="$date" class="t-label text-bronze" />', ['date' => Carbon::parse('2019-09-01')]);

        $this->assertStringContainsString('<time datetime="2019-09-01" class="t-label text-bronze">1 September 2019</time>', $html);
        $this->assertSame('', trim(Blade::render('<x-date :date="null" />')));
        $this->assertStringContainsString('>September 2019<', Blade::render('<x-date :date="$date" :format="\App\Constants\DateFormats::MONTH_YEAR" />', ['date' => Carbon::parse('2019-09-01')]));
    }
}
