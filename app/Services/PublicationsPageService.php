<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Catalogue;
use App\Constants\CitationStyle;
use App\Constants\DateFormats;
use App\Constants\PageSizes;
use App\Constants\PublicationType;
use App\Constants\Sections;
use App\Models\Publication;
use App\Support\AuthorList;
use App\Support\PublicationFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

/**
 * Assembles the publications catalogue: the "Selected works" strip, the
 * filtered page of records grouped by decade (each with the decade's full count
 * under the filters), the type / year-range / topic filter options, and for
 * each record its author line and its citation in every style.
 *
 * @phpstan-type TypeOption array{value: string, label: string, active: bool}
 * @phpstan-type DecadeGroup array{decade: int|null, label: string, anchor: string, count: int, items: Collection<int, Publication>}
 */
class PublicationsPageService
{
    public function __construct(
        private readonly PublicationService $publications,
        private readonly CitationFormatterService $citations,
        private readonly NavigationService $navigation,
        private readonly TypesetterService $typesetter,
        private readonly SeoService $seo,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     chapter: int,
     *     publications: LengthAwarePaginator<int, Publication>,
     *     groups: list<DecadeGroup>,
     *     featured: Collection<int, Publication>,
     *     citations: array<int, list<array{style: CitationStyle, label: string, edition: string, text: string, html: string, code: bool}>>,
     *     authors: array<int, HtmlString>,
     *     types: list<TypeOption>,
     *     years: list<int>,
     *     topics: list<string>,
     *     active: array{type: PublicationType|null, from: int|null, to: int|null, topic: string|null},
     *     activeCount: int,
     *     activeSummary: list<string>,
     *     isFiltered: bool,
     *     clearUrl: string,
     *     schema: list<array<string, mixed>>|null
     * }
     */
    public function data(?string $type = null, ?int $from = null, ?int $to = null, ?string $topic = null): array
    {
        $options = $this->publications->filters();
        $filters = $this->normalise($options, $type, $from, $to, $topic);

        $paginator = $this->publications->publishedFiltered($filters, PageSizes::PUBLICATIONS);
        $items = new Collection($paginator->items());

        return [
            'section' => $this->navigation->section(Sections::PUBLICATIONS, Sections::PUBLICATIONS),
            'chapter' => $this->navigation->chapter(Sections::PUBLICATIONS),
            'publications' => $paginator,
            'groups' => $this->groupByDecade($items, $this->publications->decadeCounts($filters)),
            // The strip introduces the whole catalogue, so it stays off filtered views and later pages.
            'featured' => $filters->isEmpty() && $paginator->currentPage() === 1 ? $this->publications->featured() : new Collection,
            'citations' => $items->mapWithKeys(fn (Publication $publication): array => [(int) $publication->getKey() => $this->citations->all($publication)])->all(),
            'authors' => $items->mapWithKeys(fn (Publication $publication): array => [(int) $publication->getKey() => $this->authorsHtml($publication->authors)])->all(),
            'types' => array_map(fn (PublicationType $option): array => [
                'value' => $option->value,
                'label' => $option->label(),
                'active' => $option === $filters->type,
            ], $options['types']),
            'years' => $options['years'],
            'topics' => $options['topics'],
            'active' => ['type' => $filters->type, 'from' => $filters->from, 'to' => $filters->to, 'topic' => $filters->topic],
            'activeCount' => count(array_filter([$filters->type, $filters->from ?? $filters->to, $filters->topic], fn ($value): bool => $value !== null)),
            'activeSummary' => $this->summary($filters),
            'isFiltered' => ! $filters->isEmpty(),
            'clearUrl' => $this->url(),
            'schema' => $this->seo->scholarlyArticles($paginator->items(), $this->url()),
        ];
    }

    /**
     * Keep only filter values that exist in the catalogue; a reversed range is put the right way round.
     *
     * @param  array{years: list<int>, types: list<PublicationType>, topics: list<string>}  $options
     */
    public function normalise(array $options, ?string $type, ?int $from, ?int $to, ?string $topic): PublicationFilters
    {
        $activeType = $type === null ? null : PublicationType::tryFrom($type);
        $activeType = $activeType !== null && in_array($activeType, $options['types'], true) ? $activeType : null;

        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $activeTopic = null;

        foreach ($options['topics'] as $option) {
            if ($topic !== null && mb_strtolower($option) === mb_strtolower(trim($topic))) {
                $activeTopic = $option;
            }
        }

        // A range that lies wholly outside the catalogue's years would only ever be empty: ignore it.
        if ($options['years'] !== [] && (($from !== null && $from > max($options['years'])) || ($to !== null && $to < min($options['years'])))) {
            [$from, $to] = [null, null];
        }

        return new PublicationFilters($activeType, $from, $to, $activeTopic);
    }

    /**
     * Group a page of records under decade headings, newest first, undated last.
     * Each group carries the decade's full count under the filters, which may be
     * more than the records on this page.
     *
     * @param  Collection<int, Publication>  $publications
     * @param  array<int|string, int>  $counts
     * @return list<DecadeGroup>
     */
    public function groupByDecade(Collection $publications, array $counts = []): array
    {
        $groups = [];

        foreach ($publications->groupBy(fn (Publication $publication): string => (string) ($publication->decade() ?? '')) as $decade => $items) {
            $decade = $decade === '' ? null : (int) $decade;

            $groups[] = [
                'decade' => $decade,
                'label' => $decade === null ? Catalogue::UNDATED_LABEL : $decade.Catalogue::DECADE_SUFFIX,
                'anchor' => 'decade-'.($decade ?? 'undated'),
                'count' => $counts[$decade ?? ''] ?? $items->count(),
                'items' => $items->values(),
            ];
        }

        usort($groups, static function (array $a, array $b): int {
            if ($a['decade'] === null || $b['decade'] === null) {
                return ($a['decade'] === null) <=> ($b['decade'] === null);
            }

            return $b['decade'] <=> $a['decade'];
        });

        return $groups;
    }

    /**
     * The author line, typeset and escaped, with the Professor's name in small caps.
     */
    public function authorsHtml(?string $authors): HtmlString
    {
        $names = AuthorList::parse($authors);

        if ($names === []) {
            return new HtmlString('');
        }

        $parts = array_map(function (array $author): string {
            $name = (string) $this->typesetter->inline($author['raw']);

            return AuthorList::isSelf($author) ? '<span class="smallcaps author-self">'.$name.'</span>' : $name;
        }, $names);

        $last = array_pop($parts);

        return new HtmlString($parts === [] ? $last : implode('; ', $parts).'; '.$last);
    }

    /**
     * @param  array<string, string|int>  $query
     */
    public function url(array $query = []): string
    {
        return route(Sections::definitions()[Sections::PUBLICATIONS]['route'], $query);
    }

    /**
     * The active filters in words, for the results line ("Book", "1970–1990", "Agronomy").
     *
     * @return list<string>
     */
    private function summary(PublicationFilters $filters): array
    {
        $range = match (true) {
            $filters->from !== null && $filters->to !== null && $filters->from === $filters->to => (string) $filters->from,
            $filters->from !== null && $filters->to !== null => $filters->from.DateFormats::RANGE_SEPARATOR.$filters->to,
            $filters->from !== null => 'From '.$filters->from,
            $filters->to !== null => 'Until '.$filters->to,
            default => null,
        };

        return array_values(array_filter([$filters->type?->label(), $range, $filters->topic]));
    }
}
