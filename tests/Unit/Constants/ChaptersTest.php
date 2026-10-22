<?php

namespace Tests\Unit\Constants;

use App\Constants\Chapters;
use App\Constants\Sections;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChaptersTest extends TestCase
{
    #[Test]
    public function there_are_five_chapters_in_masthead_order(): void
    {
        $this->assertSame([Chapters::LIFE, Chapters::WORK, Chapters::ARCHIVE, Chapters::COMMUNITY, Chapters::TRIBUTES], Chapters::all());
        $this->assertSame(['Life', 'Work', 'Archive', 'Community', 'Tributes'], array_column(Chapters::definitions(), 'label'));
        $this->assertTrue(Chapters::isValid(Chapters::ARCHIVE));
        $this->assertFalse(Chapters::isValid('more'));
    }

    #[Test]
    public function every_section_belongs_to_exactly_one_chapter_and_has_a_description(): void
    {
        $placed = array_merge(...array_column(Chapters::definitions(), 'sections'));

        sort($placed);
        $expected = Sections::all();
        sort($expected);

        $this->assertSame($expected, $placed, 'Each of the eleven sections appears once');
        $described = array_keys(Chapters::descriptions());
        sort($described);
        $this->assertSame($expected, $described, 'Every section has a one-line description');

        foreach (Chapters::descriptions() as $section => $description) {
            $this->assertTrue(Sections::isValid($section), $section);
            $this->assertStringEndsWith('.', $description, $section);
            $this->assertLessThan(70, mb_strlen($description), "{$section}: one line");
        }
    }

    #[Test]
    public function the_groups_follow_the_brief(): void
    {
        $definitions = Chapters::definitions();

        $this->assertSame([Sections::ABOUT, Sections::CV, Sections::AWARDS, Sections::OTHER], $definitions[Chapters::LIFE]['sections'], 'Other Information lives in Life');
        $this->assertSame([Sections::PUBLICATIONS, Sections::TEACHING, Sections::COMMENTARY], $definitions[Chapters::WORK]['sections']);
        $this->assertSame([Sections::PICTURES, Sections::VIDEOS], $definitions[Chapters::ARCHIVE]['sections']);
        $this->assertSame([Sections::COMMUNITY], $definitions[Chapters::COMMUNITY]['sections']);
        $this->assertSame([Sections::TRIBUTES], $definitions[Chapters::TRIBUTES]['sections']);
    }

    #[Test]
    public function featured_kinds_actions_and_their_routes_are_consistent(): void
    {
        foreach (Chapters::definitions() as $key => $chapter) {
            $this->assertSame(['label', 'strap', 'sections', 'actions', 'featured'], array_keys($chapter), $key);
            $this->assertNotSame('', $chapter['strap'], $key);

            if ($chapter['featured'] !== null) {
                $this->assertContains($chapter['featured'], Chapters::featuredKinds(), $key);
                $this->assertArrayHasKey($chapter['featured'], Chapters::featuredLabels(), $key);
            }

            foreach ($chapter['actions'] as $action) {
                $this->assertArrayHasKey($action, Chapters::actions(), "{$key}: {$action}");
            }
        }

        $this->assertSame(Chapters::featuredKinds(), array_keys(Chapters::featuredLabels()));

        foreach (Chapters::actions() as $key => $action) {
            $this->assertTrue(Route::has($action['route']), "{$key} links to a named route");
            $this->assertContains($action['audience'], [Chapters::AUDIENCE_ALL, Chapters::AUDIENCE_GUEST, Chapters::AUDIENCE_MEMBER], $key);
        }

        $this->assertSame(Chapters::AUDIENCE_GUEST, Chapters::actions()[Chapters::ACTION_SIGN_IN]['audience']);
        $this->assertSame(Chapters::AUDIENCE_MEMBER, Chapters::actions()[Chapters::ACTION_OPEN_CHAT]['audience']);
    }
}
