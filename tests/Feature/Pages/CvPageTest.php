<?php

namespace Tests\Feature\Pages;

use App\Constants\CvSection;
use App\Models\CvEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CvPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_the_section_header_and_an_empty_state_without_published_entries(): void
    {
        CvEntry::factory()->draft()->create(['title' => 'Draft lecturer post']);

        $this->get(route('cv'))
            ->assertOk()
            ->assertSee('<title>CV and Achievements · ', false)
            ->assertSeeInOrder(['section-header', 'CV &amp; Achievements', 'rule-brass', 'id="section-title"', 'CV and Achievements'], false)
            ->assertSee('The curriculum vitae is being compiled and will appear here once it is published.')
            ->assertDontSee('Draft lecturer post')
            ->assertDontSee('Last updated')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_groups_entries_by_section_with_a_summary_strip_and_last_updated_date(): void
    {
        CvEntry::factory()->published()->section(CvSection::Achievement)->create([
            'title' => 'Fellow of the Academy', 'organisation' => 'Sudanese Academy of Sciences', 'role_description' => null,
            'start_year' => 2005, 'end_year' => 2005, 'last_updated_at' => '2024-03-12', 'sort_order' => 1,
        ]);
        CvEntry::factory()->published()->section(CvSection::Education)->create([
            'title' => 'PhD in Agronomy', 'organisation' => 'University of Reading', 'role_description' => 'Thesis on sorghum water use.',
            'start_year' => 1969, 'end_year' => 1972, 'last_updated_at' => '2023-01-01', 'sort_order' => 1,
        ]);
        CvEntry::factory()->published()->section(CvSection::Education)->create([
            'title' => 'BSc in Agriculture', 'organisation' => '', 'role_description' => '',
            'start_year' => 1962, 'end_year' => 1966, 'last_updated_at' => null, 'sort_order' => 2,
        ]);
        CvEntry::factory()->published()->section(CvSection::Appointment)->current()->create([
            'title' => 'Professor Emeritus', 'organisation' => 'University of Khartoum', 'start_year' => 1998, 'last_updated_at' => '2022-06-30', 'sort_order' => 1,
        ]);
        CvEntry::factory()->draft()->section(CvSection::Leadership)->create(['title' => 'Draft dean post', 'last_updated_at' => '2025-01-01']);

        $response = $this->get(route('cv'))->assertOk();

        $response
            ->assertSeeInOrder(['Last updated', '<time datetime="2024-03-12">12 March 2024</time>'], false)
            ->assertSeeInOrder(['aria-label="CV sections"', 'href="#cv-education"', '>2<', 'Education', 'href="#cv-appointment"', '>1<', 'Appointments', 'href="#cv-achievement"', '>1<', 'Achievements'], false)
            ->assertSeeInOrder([
                'id="cv-education"', 'Education',
                '1969 – 1972', 'PhD in Agronomy', 'University of Reading', 'Thesis on sorghum water use.',
                '1962 – 1966', 'BSc in Agriculture',
                'id="cv-appointment"', 'Appointments', '1998 – present', 'Professor Emeritus', 'University of Khartoum',
                'id="cv-achievement"', 'Achievements', '>2005<', 'Fellow of the Academy', 'Sudanese Academy of Sciences',
            ], false)
            ->assertDontSee('cv-leadership', false)
            ->assertDontSee('cv-project', false)
            ->assertDontSee('Draft dean post')
            ->assertDontSee('The curriculum vitae is being compiled');

        $html = $response->getContent();
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'data-rise-group'));
        $this->assertSame(0, preg_match('/<p class="font-display text-xl font-light italic text-ash"><\/p>/', $html), 'Empty organisation is not rendered');
    }

    #[Test]
    public function it_never_offers_the_cv_as_a_download_or_print(): void
    {
        CvEntry::factory()->published()->count(3)->create();

        $html = $this->get(route('cv'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/download|print|\.pdf|\.docx/i', strip_tags($html));
        $this->assertStringNotContainsString(' download', $html);
        $this->assertStringNotContainsString('window.print', $html);
    }

    #[Test]
    public function the_cv_section_is_marked_current_in_the_navigation(): void
    {
        $html = $this->get(route('cv'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertSame([route('cv')], array_values(array_unique($matches[1])));
    }
}
