<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\Sections;
use App\Models\Profile;
use App\Services\AboutPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AboutPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private AboutPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(AboutPageService::class);
    }

    #[Test]
    public function it_describes_the_about_section_as_current_and_links_on_to_cv_publications_and_awards(): void
    {
        $data = $this->service->data();

        $this->assertSame(Sections::ABOUT, $data['section']['key']);
        $this->assertSame('About Prof. Mokhidir', $data['section']['title']);
        $this->assertTrue($data['section']['active']);

        $this->assertSame(Sections::aboutLinks(), array_column($data['links'], 'key'));
        $this->assertSame([route('cv'), route('publications'), route('awards')], array_column($data['links'], 'url'));
    }

    #[Test]
    public function it_returns_empty_parts_without_a_published_profile(): void
    {
        Profile::factory()->draft()->withContact()->create();

        $data = $this->service->data();

        $this->assertNull($data['profile']);
        $this->assertNull($data['portrait']);
        $this->assertSame('', $data['bio']);
        $this->assertSame([], $data['expertise']);
        $this->assertSame([], $data['facts']);
        $this->assertSame(['email' => null, 'note' => null, 'phone' => null, 'address' => null], $data['contact']);
    }

    #[Test]
    public function it_sanitises_the_biography_and_normalises_expertise_facts_and_contact(): void
    {
        $profile = Profile::factory()->published()->withPortrait()->create([
            'full_bio' => '<p onclick="x()">Hello</p><script>bad()</script><h2>Years</h2><blockquote>Light</blockquote>',
            'expertise' => ['Agronomy', '  ', null, ' Soil fertility '],
            'key_facts' => [
                ['label' => 'Field', 'value' => 'Agronomy'],
                ['label' => '', 'value' => 'dropped'],
                ['label' => 'No value'],
                'not an array',
            ],
            'contact_email_public' => 'office@example.com',
            'contact_note' => 'Via the office.',
        ]);

        $data = $this->service->data();

        $this->assertTrue($data['profile']->is($profile));
        $this->assertTrue($data['portrait']->is($profile->portrait()));
        $this->assertSame('<p>Hello</p><h2>Years</h2><blockquote>Light</blockquote>', $data['bio']);
        $this->assertSame(['Agronomy', 'Soil fertility'], $data['expertise']);
        $this->assertSame([['label' => 'Field', 'value' => 'Agronomy', 'index' => 0]], $data['facts']);
        $this->assertSame(['email' => 'office@example.com', 'note' => 'Via the office.', 'phone' => null, 'address' => null], $data['contact']);
    }

    #[Test]
    public function blank_contact_fields_are_null_so_the_page_can_skip_them(): void
    {
        Profile::factory()->published()->create(['contact_email_public' => null, 'contact_note' => '   ']);

        $this->assertSame(['email' => null, 'note' => null, 'phone' => null, 'address' => null], $this->service->data()['contact']);
    }
}
