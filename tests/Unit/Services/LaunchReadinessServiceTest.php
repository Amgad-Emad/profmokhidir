<?php

namespace Tests\Unit\Services;

use App\Constants\AdminNavigation;
use App\Constants\Disks;
use App\Constants\LaunchChecklist;
use App\Constants\MediaProperties;
use App\Constants\Placeholders;
use App\Constants\SettingKeys;
use App\Models\Award;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\LaunchReadinessService;
use App\Services\ProfileService;
use App\Services\SettingService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LaunchReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    private LaunchReadinessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(LaunchReadinessService::class);
    }

    /**
     * @return array<string, array{key: string, label: string, done: bool, detail: string, url: ?string, action: string}>
     */
    private function checks(): array
    {
        return collect($this->service->checklist()['checks'])->keyBy('key')->all();
    }

    #[Test]
    public function an_empty_site_has_everything_left_to_do_with_a_link_to_each_fix(): void
    {
        $checks = $this->checks();

        $this->assertFalse($checks[LaunchChecklist::PORTRAIT]['done']);
        $this->assertFalse($checks[LaunchChecklist::CONTACT]['done']);
        $this->assertFalse($checks[LaunchChecklist::PROFILE_PUBLISHED]['done']);
        $this->assertTrue($checks[LaunchChecklist::PLACEHOLDERS]['done']);
        $this->assertStringEndsWith('#section-portrait', (string) $checks[LaunchChecklist::PORTRAIT]['url']);

        foreach (LaunchChecklist::sections() as $nav => $type) {
            $this->assertFalse($checks[LaunchChecklist::PUBLISHED.'-'.$nav]['done']);
            $this->assertNotNull($checks[LaunchChecklist::PUBLISHED.'-'.$nav]['url']);
        }

        // Done on an empty site: no sample text is left and nothing waits for Arabic.
        $this->assertTrue($checks[LaunchChecklist::TRANSLATION]['done']);
        $this->assertSame(2, $this->service->checklist()['done']);
    }

    #[Test]
    public function placeholder_copy_is_listed_per_section_with_a_filtered_link(): void
    {
        Award::factory()->count(2)->create(['name' => Placeholders::MARKER.' Prize']);
        Publication::factory()->create(['title' => 'Real title', 'authors' => 'Khidir, M.', 'venue_publisher' => 'Journal', 'abstract' => null, 'doi' => null, 'topic' => null, 'citation' => null]);

        $checks = $this->checks();

        $this->assertArrayNotHasKey(LaunchChecklist::PLACEHOLDERS, $checks);
        $this->assertFalse($checks[LaunchChecklist::PLACEHOLDERS.'-award']['done']);
        $this->assertStringContainsString('2 awards still hold sample text', $checks[LaunchChecklist::PLACEHOLDERS.'-award']['detail']);
        $this->assertStringContainsString('placeholder=1', (string) $checks[LaunchChecklist::PLACEHOLDERS.'-award']['url']);
        $this->assertArrayNotHasKey(LaunchChecklist::PLACEHOLDERS.'-publication', $checks);
    }

    #[Test]
    public function the_portrait_contact_details_and_published_sections_tick_off(): void
    {
        $profile = Profile::factory()->published()->create(['contact_email_public' => null]);
        $this->app->make(ProfileService::class)->setPortrait($profile, PlaceholderImage::grey(30, 40), [MediaProperties::ALT_TEXT => 'Portrait']);
        $this->app->make(SettingService::class)->update([SettingKeys::CONTACT_EMAIL => 'family@example.com']);
        Award::factory()->published()->create(['name' => 'Real prize', 'organisation' => 'Royal Society', 'description' => null]);

        $checks = $this->checks();

        $this->assertTrue($checks[LaunchChecklist::PORTRAIT]['done']);
        $this->assertTrue($checks[LaunchChecklist::CONTACT]['done']);
        $this->assertTrue($checks[LaunchChecklist::PROFILE_PUBLISHED]['done']);
        $this->assertTrue($checks[LaunchChecklist::PUBLISHED.'-'.AdminNavigation::AWARDS]['done']);
        $this->assertSame('1 published.', $checks[LaunchChecklist::PUBLISHED.'-'.AdminNavigation::AWARDS]['detail']);
    }
}
