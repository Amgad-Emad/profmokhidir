<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function it_renders_the_section_header_and_an_empty_state_without_a_published_profile(): void
    {
        Profile::factory()->draft()->create(['short_bio' => 'Draft only']);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('<title>About Prof. Mokhidir · ', false)
            ->assertSeeInOrder(['chapter-opening', '<span class="sr-only">Chapter </span>I</span>', 'About', 'rule-brass', 'id="section-title"', 'About Prof. Mokhidir'], false)
            ->assertSee('The biography is being prepared and will appear here once it is published.')
            ->assertDontSee('Draft only')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_renders_every_populated_field_from_the_database(): void
    {
        Profile::factory()->published()->withPortrait()->withContact()->create([
            'name' => 'Mohamed Osman Khidir',
            'title' => 'Professor Emeritus of Agronomy',
            'affiliation' => 'Faculty of Agriculture, University of Khartoum',
            'full_bio' => '<p>Opening paragraph.</p><h2>Early years</h2><p>More.</p><blockquote>Knowledge is the light we pass on.</blockquote>',
            'expertise' => ['Agronomy', 'Soil fertility'],
            'key_facts' => [['label' => 'Field', 'value' => 'Agronomy'], ['label' => 'Years teaching', 'value' => '40']],
        ]);

        $response = $this->get(route('about'))->assertOk();

        $response
            ->assertSeeInOrder(['srcset="', 'alt="Placeholder portrait of Professor Mohamed Osman Khidir"', 'photo-portrait', '[Placeholder] Portrait', 't-label text-bronze">Khartoum, 1998'], false)
            ->assertSeeInOrder(['Mohamed Osman Khidir</h2>', 'Professor Emeritus of Agronomy', 'Faculty of Agriculture, University of Khartoum'], false)
            ->assertSee('<div class="drop-cap prose-bio"><p>Opening paragraph.</p><h2>Early years</h2><p>More.</p><blockquote>Knowledge is the light we pass on.</blockquote></div>', false)
            ->assertSeeInOrder(['Expertise', 'tag t-label', 'Agronomy', 'Soil fertility'], false)
            ->assertSeeInOrder(['Key facts', '<dl', '<dt class="t-label text-bronze">Field</dt>', '<dd class="figures-lining font-display text-[1.75rem] font-light leading-10 text-evergreen">Agronomy</dd>', 'Years teaching', '40'], false)
            ->assertSeeInOrder(['Contact', 'href="mailto:office@example.com"', 'office@example.com</a><span class="sidenote"', 'class="sidenote__ref"', 'role="note"', 'Correspondence is handled by the family office.'], false)
            ->assertSeeInOrder(['folio--top', 'chapter-opening', 'class="epigraph', e(\App\Constants\Taglines::ABOUT_EPIGRAPH), 'class="sidenote-host', 'ornament--fleuron', 'class="seal"', 'folio--bottom'], false)
            ->assertSeeInOrder(['aria-label="Continue reading"', 'href="'.route('cv').'"', 'CV &amp; Achievements', 'href="'.route('publications').'"', 'Publications', 'href="'.route('awards').'"', 'Awards'], false)
            ->assertDontSee('The biography is being prepared');

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'data-rise-group'));
    }

    #[Test]
    public function it_skips_empty_fields_and_strips_unsafe_markup_from_the_biography(): void
    {
        Profile::factory()->published()->create([
            'title' => null,
            'affiliation' => '',
            'full_bio' => '<p>Safe.</p><script>alert(1)</script><p onclick="x()">Also safe.</p>',
            'expertise' => [],
            'key_facts' => null,
            'contact_email_public' => null,
            'contact_note' => null,
        ]);

        $response = $this->get(route('about'))->assertOk();

        $response
            ->assertSee('<p>Safe.</p>alert(1)<p>Also safe.</p>', false)
            ->assertDontSee('<script>', false)
            ->assertDontSee('alert(1)</script>', false)
            ->assertDontSee('onclick', false)
            ->assertDontSee('photo-portrait', false)
            ->assertDontSee('id="expertise-title"', false)
            ->assertDontSee('id="facts-title"', false)
            ->assertDontSee('id="contact-title"', false)
            ->assertDontSee('mailto:', false);
    }

    #[Test]
    public function the_about_section_is_marked_current_in_the_navigation(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches[1]);
        $this->assertSame([route('about')], array_values(array_unique($matches[1])));
    }
}
