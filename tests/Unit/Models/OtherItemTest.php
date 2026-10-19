<?php

namespace Tests\Unit\Models;

use App\Models\OtherItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OtherItemTest extends TestCase
{
    #[Test]
    public function an_item_has_a_detail_page_only_with_a_body_and_no_destination(): void
    {
        $this->assertTrue((new OtherItem(['body' => '<p>Text</p>', 'destination_url' => null]))->hasDetail());
        $this->assertFalse((new OtherItem(['body' => '<p>Text</p>', 'destination_url' => 'https://example.org']))->hasDetail());
        $this->assertFalse((new OtherItem(['body' => '', 'destination_url' => null]))->hasDetail());
        $this->assertFalse((new OtherItem(['body' => null, 'destination_url' => null]))->hasDetail());
    }

    #[Test]
    public function the_type_label_is_a_headline_of_the_stored_type(): void
    {
        $this->assertSame('Press', (new OtherItem(['item_type' => 'press']))->typeLabel());
        $this->assertSame('In Memoriam', (new OtherItem(['item_type' => 'in_memoriam']))->typeLabel());
        $this->assertNull((new OtherItem(['item_type' => null]))->typeLabel());
        $this->assertNull((new OtherItem(['item_type' => '']))->typeLabel());
    }
}
