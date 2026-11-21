<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\DateFormats;
use App\Constants\HomeTimeline;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Award;
use App\Models\CvEntry;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * "A life in chapters": his dated awards and honours, regrouped by decade (the
 * CV page carries his education and appointments). Each decade with at least
 * one dated honour becomes a chapter with a Roman
 * numeral, its years, its two or three most telling milestones (see
 * HomeTimeline::priority()) and, when an album holds one from those years, an
 * archive photo. It reads the cached published listings of each type, so it
 * never queries on its own and is refreshed whenever those types are saved.
 */
class HomeTimelineService
{
    public function __construct(
        private readonly CvEntryService $cv,
        private readonly AwardService $awards,
        private readonly AlbumService $albums,
        private readonly TypesetterService $typesetter,
    ) {}

    /**
     * Chapters in chronological order.
     *
     * @return list<array{
     *     number: int,
     *     numeral: string,
     *     decade: int,
     *     years: string,
     *     id: string,
     *     milestones: list<array{year: int, years: string, title: string, detail: ?string, kind: string, label: string}>,
     *     photo: Media|null
     * }>
     */
    public function chapters(): array
    {
        $byDecade = [];

        foreach ($this->milestones() as $milestone) {
            $byDecade[$this->decadeOf($milestone['year'])][] = $milestone;
        }

        ksort($byDecade);

        $photos = $this->archivePhotos();
        $used = [];
        $chapters = [];

        foreach ($byDecade as $decade => $milestones) {
            $photo = $this->photoFor($decade, $photos, $used);
            $number = count($chapters) + 1;

            $chapters[] = [
                'number' => $number,
                'numeral' => $this->typesetter->roman($number),
                'decade' => $decade,
                'years' => $decade.DateFormats::RANGE_SEPARATOR.($decade + HomeTimeline::DECADE_YEARS - 1),
                'id' => 'chapter-'.$decade.'s',
                'milestones' => $this->select($milestones),
                'photo' => $photo,
            ];
        }

        return $chapters;
    }

    /**
     * Every dated award, unsorted, by the year given. Undated awards wait for
     * an editor to record their year.
     *
     * @return list<array{year: int, years: string, title: string, detail: ?string, kind: string, label: string}>
     */
    public function milestones(): array
    {
        $milestones = [];

        foreach ($this->awards->published() as $award) {
            /** @var Award $award */
            if ($award->year === null) {
                continue;
            }

            $milestones[] = $this->milestone($award->year, (string) $award->year, $award->name, $award->organisation, HomeTimeline::KIND_AWARD, (string) Locales::attributesFor($award));
        }

        return $milestones;
    }

    /**
     * The photos the chapters draw from, in album order and then photo order.
     * When editors have offered photos to the timeline (MediaProperties::ON_TIMELINE,
     * e.g. his medals and shields) only those are used; otherwise every published
     * album photo with the archive treatment.
     *
     * @return list<Media>
     */
    public function archivePhotos(): array
    {
        $chosen = [];
        $archive = [];

        foreach ($this->albums->published() as $album) {
            foreach ($album->photos() as $photo) {
                if ((bool) $photo->getCustomProperty(MediaProperties::ON_TIMELINE, false)) {
                    $chosen[] = $photo;
                }

                if ($photo->getCustomProperty(MediaProperties::TREATMENT) === MediaTreatment::Archive->value) {
                    $archive[] = $photo;
                }
            }
        }

        return $chosen !== [] ? $chosen : $archive;
    }

    /**
     * Years of teaching: from the first appointment to the last year an
     * appointment records. Open-ended appointments count only to the year they
     * began, so the figure never runs on past what the CV states.
     */
    public function yearsOfTeaching(): ?int
    {
        $first = null;
        $last = null;

        foreach ($this->cv->published() as $entry) {
            /** @var CvEntry $entry */
            if ($entry->section !== HomeTimeline::TEACHING_SECTION || $entry->start_year === null) {
                continue;
            }

            $first = $first === null ? $entry->start_year : min($first, $entry->start_year);
            $last = max($last ?? $entry->start_year, $entry->start_year, $entry->end_year ?? $entry->start_year);
        }

        if ($first === null || $last <= $first) {
            return null;
        }

        return $last - $first;
    }

    public function decadeOf(int $year): int
    {
        return intdiv($year, HomeTimeline::DECADE_YEARS) * HomeTimeline::DECADE_YEARS;
    }

    /**
     * The chapter's milestones: the most telling kinds first (earliest within a
     * kind), cut to MILESTONES_PER_CHAPTER, then shown in date order.
     *
     * @param  list<array{year: int, years: string, title: string, detail: ?string, kind: string, label: string}>  $milestones
     * @return list<array{year: int, years: string, title: string, detail: ?string, kind: string, label: string}>
     */
    private function select(array $milestones): array
    {
        usort($milestones, fn (array $a, array $b): int => [HomeTimeline::rank($a['kind']), $a['year']] <=> [HomeTimeline::rank($b['kind']), $b['year']]);

        $chosen = array_slice($milestones, 0, HomeTimeline::MILESTONES_PER_CHAPTER);

        usort($chosen, fn (array $a, array $b): int => [$a['year'], HomeTimeline::rank($a['kind'])] <=> [$b['year'], HomeTimeline::rank($b['kind'])]);

        return $chosen;
    }

    /**
     * The first unused archive photo whose recorded year falls in the decade.
     *
     * @param  list<Media>  $photos
     * @param  array<int, true>  $used  ids already shown, updated in place
     */
    private function photoFor(int $decade, array $photos, array &$used): ?Media
    {
        foreach ($photos as $photo) {
            $year = $photo->getCustomProperty(MediaProperties::YEAR);

            if (isset($used[$photo->id]) || ! is_numeric($year) || $this->decadeOf((int) $year) !== $decade) {
                continue;
            }

            $used[$photo->id] = true;

            return $photo;
        }

        return null;
    }

    /**
     * `lang` marks a title shown in its English fallback on an Arabic page (Locales::attributesFor).
     *
     * @return array{year: int, years: string, title: string, detail: ?string, kind: string, label: string, lang: string}
     */
    private function milestone(int $year, string $years, string $title, ?string $detail, string $kind, string $lang = ''): array
    {
        return [
            'year' => $year,
            'years' => $years,
            'title' => $title,
            'detail' => filled($detail) ? $detail : null,
            'kind' => $kind,
            'label' => HomeTimeline::kindLabel($kind),
            'lang' => $lang,
        ];
    }
}
