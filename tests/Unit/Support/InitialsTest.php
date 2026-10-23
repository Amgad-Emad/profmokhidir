<?php

namespace Tests\Unit\Support;

use App\Support\Initials;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The initials in a member's ring avatar: first and last word, upper case, any script.
 */
class InitialsTest extends TestCase
{
    #[Test]
    public function it_takes_the_first_letters_of_the_first_and_last_words(): void
    {
        $this->assertSame('AH', Initials::of('Amina Hassan'));
        $this->assertSame('MK', Initials::of('Mohamed Osman Khidir'));
        $this->assertSame('YA', Initials::of('  yusuf   abdallah '));
    }

    #[Test]
    public function a_single_name_gives_one_letter_and_an_empty_name_none(): void
    {
        $this->assertSame('A', Initials::of('Amina'));
        $this->assertSame('', Initials::of(''));
        $this->assertSame('', Initials::of(null));
    }

    #[Test]
    public function arabic_names_keep_their_own_letters(): void
    {
        $this->assertSame('أخ', Initials::of('أحمد خضر'));
    }
}
