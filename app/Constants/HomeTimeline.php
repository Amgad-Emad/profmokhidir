<?php

declare(strict_types=1);

namespace App\Constants;

use InvalidArgumentException;

/**
 * The home page's narrative: "A life in chapters" (one chapter per decade, built
 * from published CV entries and awards), the quote interludes between them and
 * the row of figures in "His work in numbers". Nothing here is biography; the
 * chapters only regroup what editors have published.
 */
final class HomeTimeline
{
    public const int DECADE_YEARS = 10;

    /** Each chapter shows its two or three most telling milestones. */
    public const int MILESTONES_PER_CHAPTER = 3;

    /** Interludes are short: one or two sentences. */
    public const int QUOTE_MAX_LENGTH = 280;

    /** Milestone kind for awards; CV entries use their CvSection value. */
    public const string KIND_AWARD = 'award';

    /** CV section whose years count as years of teaching. */
    public const CvSection TEACHING_SECTION = CvSection::Appointment;

    public const string STAT_PUBLICATIONS = 'publications';

    public const string STAT_YEARS_TEACHING = 'years_teaching';

    public const string STAT_AWARDS = 'awards';

    public const string STAT_STUDENTS = 'students_supervised';

    /** Id of the timeline heading, the target of the hero's scroll cue. */
    public const string ANCHOR = 'a-life-in-chapters';

    /**
     * Milestone kinds, most telling first. When a decade holds more milestones
     * than a chapter shows, the earlier kinds are kept.
     *
     * @return list<string>
     */
    public static function priority(): array
    {
        return [
            CvSection::Appointment->value,
            CvSection::Education->value,
            self::KIND_AWARD,
            CvSection::Leadership->value,
            CvSection::Achievement->value,
            CvSection::Project->value,
        ];
    }

    public static function rank(string $kind): int
    {
        $rank = array_search($kind, self::priority(), true);

        if ($rank === false) {
            throw new InvalidArgumentException(sprintf('Unknown milestone kind "%s".', $kind));
        }

        return $rank;
    }

    /**
     * Small-caps label of a milestone kind.
     */
    public static function kindLabel(string $kind): string
    {
        return __(match ($kind) {
            CvSection::Education->value => 'Education',
            CvSection::Appointment->value => 'Appointment',
            CvSection::Leadership->value => 'Leadership',
            CvSection::Project->value => 'Project',
            CvSection::Achievement->value => 'Achievement',
            self::KIND_AWARD => 'Award',
            default => throw new InvalidArgumentException(sprintf('Unknown milestone kind "%s".', $kind)),
        });
    }

    /**
     * Figure key => [singular label, plural label], in display order.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function stats(): array
    {
        return [
            self::STAT_PUBLICATIONS => ['Publication', 'Publications'],
            self::STAT_YEARS_TEACHING => ['Year of teaching', 'Years of teaching'],
            self::STAT_AWARDS => ['Award and honour', 'Awards and honours'],
            self::STAT_STUDENTS => ['Student supervised', 'Students supervised'],
        ];
    }

    public static function statLabel(string $key, int $value): string
    {
        $labels = self::stats()[$key] ?? throw new InvalidArgumentException(sprintf('Unknown figure "%s".', $key));

        return __($value === 1 ? $labels[0] : $labels[1]);
    }
}
