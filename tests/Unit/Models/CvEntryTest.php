<?php

namespace Tests\Unit\Models;

use App\Models\CvEntry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CvEntryTest extends TestCase
{
    #[Test]
    public function year_range_covers_every_combination_of_start_and_end(): void
    {
        $this->assertSame('1969–1975', (new CvEntry(['start_year' => 1969, 'end_year' => 1975]))->yearRange());
        $this->assertSame('1990–present', (new CvEntry(['start_year' => 1990, 'end_year' => null]))->yearRange());
        $this->assertSame('1980', (new CvEntry(['start_year' => 1980, 'end_year' => 1980]))->yearRange());
        $this->assertSame('2001', (new CvEntry(['start_year' => null, 'end_year' => 2001]))->yearRange());
        $this->assertNull((new CvEntry(['start_year' => null, 'end_year' => null]))->yearRange());
    }
}
