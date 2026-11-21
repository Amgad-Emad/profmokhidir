<?php

namespace Tests\Unit\Services;

use App\Constants\Sections;
use App\Constants\SettingKeys;
use App\Models\User;
use App\Services\SectionVisibilityService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionVisibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectionVisibilityService $sections;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sections = app(SectionVisibilityService::class);
    }

    #[Test]
    public function every_section_shows_until_one_is_hidden(): void
    {
        $this->assertSame([], $this->sections->hidden());
        $this->assertTrue($this->sections->isVisible(Sections::AWARDS));
    }

    #[Test]
    public function hiding_stores_known_keys_in_section_order_and_filters_lists(): void
    {
        $this->sections->hide([Sections::TEACHING, 'nonsense', Sections::AWARDS]);

        $this->assertSame(Sections::AWARDS.','.Sections::TEACHING, app(SettingService::class)->get(SettingKeys::HIDDEN_SECTIONS));
        $this->assertSame([Sections::AWARDS, Sections::TEACHING], $this->sections->hidden());
        $this->assertFalse($this->sections->isVisible(Sections::AWARDS));
        $this->assertSame([Sections::CV, Sections::VIDEOS], $this->sections->visible([Sections::CV, Sections::AWARDS, Sections::VIDEOS]));

        $this->sections->hide([]);
        $this->assertSame([], $this->sections->hidden());
    }

    #[Test]
    public function staff_who_manage_content_may_still_open_a_hidden_section(): void
    {
        $this->sections->hide([Sections::AWARDS]);

        $this->assertFalse($this->sections->canView(Sections::AWARDS, null));
        $this->assertFalse($this->sections->canView(Sections::AWARDS, User::factory()->member()->create()));
        $this->assertTrue($this->sections->canView(Sections::AWARDS, User::factory()->editor()->create()));
        $this->assertTrue($this->sections->canView(Sections::CV, null));
    }

    #[Test]
    public function routes_are_matched_to_their_section_in_every_language(): void
    {
        $this->assertSame(Sections::VIDEOS, $this->sections->sectionForRoute('videos.show'));
        $this->assertSame(Sections::VIDEOS, $this->sections->sectionForRoute('ar.videos'));
        $this->assertSame(Sections::TRIBUTES, $this->sections->sectionForRoute('tributes.create'));
        $this->assertNull($this->sections->sectionForRoute('home'));
        $this->assertNull($this->sections->sectionForRoute(null));
    }
}
