<?php

namespace Tests\Unit\Support;

use App\Constants\ListFilterType;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The filter definition of the admin list toolbar: reading and writing its values.
 */
class ListFilterTest extends TestCase
{
    #[Test]
    public function a_single_choice_filter_keeps_one_known_value(): void
    {
        $filter = ListFilter::single('status', 'Status', ['draft' => 'Draft', 'published' => 'Published'], 'draft');

        $this->assertSame(ListFilterType::Single, $filter->type);
        $this->assertSame('draft', $filter->default);
        $this->assertSame(['draft'], $filter->values(['status' => 'draft']));
        $this->assertSame([], $filter->values(['status' => 'deleted']));
        $this->assertSame([], $filter->values(['status' => 'draft,published']));
        $this->assertSame([], $filter->values([]));
        $this->assertSame('published', $filter->join(['published', 'draft']));
    }

    #[Test]
    public function a_multi_choice_filter_keeps_known_values_in_the_options_order(): void
    {
        $filter = ListFilter::multi('year', 'Year', [2001 => '2001', 1999 => '1999', 1990 => '1990']);

        $this->assertSame(['2001', '1999', '1990'], array_map('strval', array_keys($filter->options)));
        $this->assertSame(['2001', '1990'], $filter->values(['year' => '1990,1850,2001,1990']));
        $this->assertSame('2001,1990', $filter->join(['1990', '2001', '1850']));
        $this->assertSame('', $filter->join([]));
    }

    #[Test]
    public function a_flag_has_one_option_and_a_chip_wording(): void
    {
        $filter = ListFilter::flag('needs_translation', 'Language', 'Needs Arabic translation', 'Needs Arabic', toolbar: false);

        $this->assertSame(ListFilterType::Flag, $filter->type);
        $this->assertSame(['1' => 'Needs Arabic translation'], $filter->options);
        $this->assertSame('Needs Arabic', $filter->chip);
        $this->assertFalse($filter->toolbar);
        $this->assertSame(['1'], $filter->values(['needs_translation' => '1']));
        $this->assertSame([], $filter->values(['needs_translation' => 'yes']));
    }

    #[Test]
    public function a_sort_choice_has_a_key_unless_it_is_the_screens_own_order(): void
    {
        $this->assertSame('year:desc', (new ListSort('year', 'desc', 'Year'))->key());
        $this->assertSame('', (new ListSort('', 'asc', 'Display order'))->key());
    }
}
