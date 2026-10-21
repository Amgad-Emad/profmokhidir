<?php

namespace Tests\Unit\Support;

use App\Support\AuthorList;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthorListTest extends TestCase
{
    #[Test]
    public function it_splits_an_author_field_on_semicolons_and_and(): void
    {
        $this->assertSame([
            ['raw' => 'Khidir, M. O.', 'family' => 'Khidir', 'given' => 'M. O.'],
            ['raw' => 'John Smith', 'family' => 'Smith', 'given' => 'John'],
            ['raw' => 'Jones, A.', 'family' => 'Jones', 'given' => 'A.'],
        ], AuthorList::parse('Khidir, M. O.; John Smith and Jones, A.'));

        $this->assertCount(2, AuthorList::parse('Smith, J. & Khidir, M.'));
        $this->assertSame([], AuthorList::parse(null));
        $this->assertSame([], AuthorList::parse(' ; '));
        $this->assertSame([['raw' => 'Plato', 'family' => 'Plato', 'given' => '']], AuthorList::parse('Plato'));
    }

    #[Test]
    public function it_writes_initials_and_both_name_orders(): void
    {
        $this->assertSame('M. O.', AuthorList::initials('Mohamed Osman'));
        $this->assertSame('M. O.', AuthorList::initials('M.O.'));
        $this->assertSame('J.-P.', AuthorList::initials('Jean-Pierre'));
        $this->assertSame('', AuthorList::initials(''));

        $author = ['family' => 'Khidir', 'given' => 'Mohamed'];
        $this->assertSame('Mohamed Khidir', AuthorList::givenFirst($author));
        $this->assertSame('Khidir, Mohamed', AuthorList::familyFirst($author));
        $this->assertSame('Plato', AuthorList::familyFirst(['family' => 'Plato', 'given' => '']));
    }

    #[Test]
    public function it_recognises_the_professor_by_family_name(): void
    {
        $this->assertTrue(AuthorList::isSelf(['family' => 'Khidir', 'given' => 'M. O.']));
        $this->assertTrue(AuthorList::isSelf(['family' => 'KHIDIR', 'given' => '']));
        $this->assertFalse(AuthorList::isSelf(['family' => 'Smith', 'given' => 'Khidir']));
    }
}
