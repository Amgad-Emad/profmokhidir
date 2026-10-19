<?php

namespace Tests\Unit\Constants;

use App\Constants\ErrorPages;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorPagesTest extends TestCase
{
    #[Test]
    public function it_covers_the_five_status_codes_with_a_title_and_a_line_each(): void
    {
        $this->assertSame([403, 404, 419, 500, 503], ErrorPages::codes());

        foreach (ErrorPages::all() as $code => $page) {
            $this->assertTrue(ErrorPages::isValid($code));
            $this->assertNotSame('', $page['title']);
            $this->assertNotSame('', $page['line']);
            $this->assertLessThan(80, strlen($page['line']), 'One short line');
            $this->assertSame($page, ErrorPages::get($code));
        }

        $this->assertFalse(ErrorPages::isValid(418));
        $this->assertSame(ErrorPages::all()[500], ErrorPages::get(418), 'Unknown codes read as a server error');
        $this->assertSame('Return home', ErrorPages::HOME_LABEL);
    }
}
