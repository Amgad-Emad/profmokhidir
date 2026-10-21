<?php

namespace Tests\Feature\Components;

use App\Constants\TributeRelationship;
use App\Models\Tribute;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeEntryTest extends TestCase
{
    #[Test]
    public function it_sets_the_tribute_in_italic_with_the_name_in_small_caps_and_the_rest_as_a_label(): void
    {
        $tribute = new Tribute([
            'name' => 'Ahmed El-Sheikh',
            'relationship' => TributeRelationship::Student,
            'location' => 'Khartoum, Sudan',
            'message' => "He said \"look twice\" before answering.\n\nI still do.",
            'email' => 'secret@example.com',
        ]);

        $html = Blade::render('<x-tribute-entry :tribute="$tribute" />', ['tribute' => $tribute]);

        $this->assertStringContainsString('<article class="tribute-entry">', $html);
        $this->assertStringContainsString('<blockquote class="tribute-entry__text">', $html);
        $this->assertStringContainsString("<p><span class=\"hang-quote\">&ldquo;</span>He said \u{201C}look twice\u{201D} before answering.</p>", $html);
        $this->assertStringContainsString('<p>I still do.&rdquo;</p>', $html);
        $this->assertStringContainsString('<h3 class="tribute-entry__author smallcaps">Ahmed El-Sheikh</h3>', $html);
        $this->assertStringContainsString('class="t-label text-bronze tribute-entry__meta">Student · Khartoum, Sudan</p>', $html);
        $this->assertStringNotContainsString('secret@example.com', $html);
        $this->assertStringNotContainsString('tribute-entry__card', $html);
    }

    #[Test]
    public function it_links_to_the_share_card_when_given_one_and_takes_a_heading_level(): void
    {
        $tribute = Tribute::factory()->approved()->make(['name' => 'Leila', 'location' => null, 'relationship' => TributeRelationship::Colleague, 'message' => 'A generous colleague.']);
        $tribute->id = 7;

        $html = Blade::render('<x-tribute-entry :tribute="$tribute" card-url="https://profmokhidir.test/tributes/7/card" heading="2" />', ['tribute' => $tribute]);

        $this->assertStringContainsString('<h2 class="tribute-entry__author smallcaps">Leila</h2>', $html);
        $this->assertStringContainsString('t-label text-bronze tribute-entry__meta">Colleague</p>', $html);
        $this->assertStringContainsString('<a href="https://profmokhidir.test/tributes/7/card" class="tribute-entry__card t-label" download="tribute-7.png">Share card<span class="sr-only"> for the tribute from Leila (PNG image)</span></a>', $html);
    }
}
