<?php

namespace Tests\Unit\Support;

use App\Support\FileSize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FileSizeTest extends TestCase
{
    /**
     * @return iterable<string, array{int, string}>
     */
    public static function sizes(): iterable
    {
        yield 'zero' => [0, '0 B'];
        yield 'bytes' => [512, '512 B'];
        yield 'small kilobytes keep one decimal' => [1536, '1.5 KB'];
        yield 'whole kilobytes drop the decimal' => [12 * 1024, '12 KB'];
        yield 'hundreds of kilobytes keep trailing zero digits' => [640 * 1024, '640 KB'];
        yield 'megabytes' => [2150 * 1024, '2.1 MB'];
        yield 'exact megabytes' => [3 * 1024 * 1024, '3 MB'];
        yield 'large megabytes' => [250 * 1024 * 1024, '250 MB'];
        yield 'gigabytes' => [(int) (1.5 * 1024 * 1024 * 1024), '1.5 GB'];
        yield 'negative is treated as zero' => [-5, '0 B'];
    }

    #[Test]
    #[DataProvider('sizes')]
    public function it_formats_bytes_for_file_links(int $bytes, string $expected): void
    {
        $this->assertSame($expected, FileSize::format($bytes));
    }
}
