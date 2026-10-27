<?php

namespace Tests\Unit\Constants;

use App\Constants\Analytics;
use App\Constants\AnalyticsRange;
use App\Constants\Sections;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    #[Test]
    public function paths_belong_to_their_section_and_the_rest_to_home_or_other_pages(): void
    {
        $this->assertSame(Analytics::SECTION_HOME, Analytics::sectionFor('/'));
        $this->assertSame(Sections::PUBLICATIONS, Analytics::sectionFor('/publications'));
        $this->assertSame(Sections::COMMENTARY, Analytics::sectionFor('/commentary/on-soil'));
        $this->assertSame(Analytics::SECTION_OTHER_PAGES, Analytics::sectionFor('/search'));
        $this->assertSame(Analytics::SECTION_OTHER_PAGES, Analytics::sectionFor('/publicationsx'));
        $this->assertTrue(Analytics::isValidSection(Sections::ABOUT));
        $this->assertSame('Home', array_values(Analytics::sections())[0]);
    }

    #[Test]
    public function the_dashboard_chat_account_and_sign_in_paths_are_excluded(): void
    {
        foreach (['/admin', '/admin/analytics', '/community/chat', '/community/chat/x', '/login', '/account', '/two-factor-challenge', '/forgot-password', '/a/collect'] as $path) {
            $this->assertTrue(Analytics::isExcludedPath($path), $path);
        }

        foreach (['/', '/community', '/about', '/administration-notes', '/loginx'] as $path) {
            $this->assertFalse(Analytics::isExcludedPath($path), $path);
        }
    }

    #[Test]
    public function range_presets_end_today_and_custom_ranges_are_ordered(): void
    {
        $today = CarbonImmutable::parse('2026-10-24');

        $this->assertSame(['2026-10-24', '2026-10-24'], $this->dates(AnalyticsRange::Today->bounds($today)));
        $this->assertSame(['2026-10-18', '2026-10-24'], $this->dates(AnalyticsRange::Week->bounds($today)));
        $this->assertSame(['2026-09-25', '2026-10-24'], $this->dates(AnalyticsRange::Month->bounds($today)));
        $this->assertSame(['2025-10-25', '2026-10-24'], $this->dates(AnalyticsRange::Year->bounds($today)));
        $this->assertSame(['2026-10-01', '2026-10-10'], $this->dates(AnalyticsRange::Custom->bounds($today, CarbonImmutable::parse('2026-10-10'), CarbonImmutable::parse('2026-10-01'))));
        $this->assertSame(AnalyticsRange::Month, AnalyticsRange::DEFAULT);
    }

    /**
     * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $bounds
     * @return list<string>
     */
    private function dates(array $bounds): array
    {
        return [$bounds[0]->toDateString(), $bounds[1]->toDateString()];
    }
}
