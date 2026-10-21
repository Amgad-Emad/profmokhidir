<?php

namespace Tests\Unit\Constants;

use App\Constants\CvSection;
use App\Constants\HomeTimeline;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HomeTimelineTest extends TestCase
{
    #[Test]
    public function every_cv_section_and_awards_have_a_rank_and_a_singular_label(): void
    {
        foreach ([...CvSection::values(), HomeTimeline::KIND_AWARD] as $kind) {
            $this->assertIsInt(HomeTimeline::rank($kind));
            $this->assertNotSame('', HomeTimeline::kindLabel($kind));
        }

        $this->assertSame(0, HomeTimeline::rank(CvSection::Appointment->value));
        $this->assertLessThan(HomeTimeline::rank(CvSection::Project->value), HomeTimeline::rank(HomeTimeline::KIND_AWARD));
        $this->assertSame('Appointment', HomeTimeline::kindLabel(CvSection::Appointment->value));
        $this->assertSame('Award', HomeTimeline::kindLabel(HomeTimeline::KIND_AWARD));
    }

    #[Test]
    public function figure_labels_agree_with_their_number(): void
    {
        $this->assertSame([HomeTimeline::STAT_PUBLICATIONS, HomeTimeline::STAT_YEARS_TEACHING, HomeTimeline::STAT_AWARDS, HomeTimeline::STAT_STUDENTS], array_keys(HomeTimeline::stats()));
        $this->assertSame('Publication', HomeTimeline::statLabel(HomeTimeline::STAT_PUBLICATIONS, 1));
        $this->assertSame('Years of teaching', HomeTimeline::statLabel(HomeTimeline::STAT_YEARS_TEACHING, 36));
        $this->assertSame('Students supervised', HomeTimeline::statLabel(HomeTimeline::STAT_STUDENTS, 40));
    }

    #[Test]
    public function unknown_kinds_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HomeTimeline::kindLabel('nope');
    }

    #[Test]
    public function unknown_figures_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HomeTimeline::statLabel('nope', 2);
    }
}
