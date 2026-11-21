<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Search;
use App\Constants\Sections;
use App\Constants\TeachingAccess;
use App\Models\Album;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\OtherItem;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Models\Video;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Support\AuthorList;
use App\Support\SearchHighlighter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Site-wide search: normalises the query, asks the repository for published
 * matches, and presents them grouped by section with the matched words marked.
 * Members-only teaching materials appear only for signed-in members (and
 * admins). Results are never cached: every query is its own.
 *
 * @phpstan-type SearchHit array{title: string, excerpt: string, meta: list<string|\DateTimeInterface>, url: string, members: bool}
 * @phpstan-type SearchGroup array{key: string, label: string, url: string, total: int, more: int, items: list<SearchHit>}
 * @phpstan-type SearchResults array{query: string, tokens: list<string>, total: int, sections: int, summary: string, groups: list<SearchGroup>}
 */
class SearchService
{
    public function __construct(
        private readonly SearchRepositoryInterface $repository,
        private readonly NavigationService $navigation,
        private readonly SearchHighlighter $highlighter,
        private readonly SectionVisibilityService $visibility,
    ) {}

    /**
     * Trimmed, single-spaced and cut to the maximum length.
     */
    public function normalise(?string $query): string
    {
        $query = trim(preg_replace('/\s+/u', ' ', (string) $query) ?? '');

        return Str::limit($query, Search::MAX_LENGTH, '');
    }

    public function isSearchable(string $query): bool
    {
        return mb_strlen($this->normalise($query)) >= Search::MIN_LENGTH
            && $this->repository->tokens($query) !== [];
    }

    public function canSeeMembersOnly(?User $user): bool
    {
        return $user instanceof User && $user->canAccessChat();
    }

    /**
     * @return SearchResults
     */
    public function search(?string $query, ?User $user = null, int $perSection = Search::PAGE_PER_SECTION): array
    {
        $query = $this->normalise($query);
        $tokens = $this->repository->tokens($query);
        $groups = [];
        $total = 0;

        if ($this->isSearchable($query)) {
            $found = $this->repository->search($query, $this->canSeeMembersOnly($user), $perSection);

            foreach ($found as $section => $result) {
                if (! $this->visibility->isVisible($section)) {
                    continue;
                }

                $item = $this->navigation->section($section);
                $hits = $result['items']->map(fn (Model $model): array => $this->present($section, $model, $tokens))->all();

                $groups[] = [
                    'key' => $section,
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'total' => $result['total'],
                    'more' => max(0, $result['total'] - count($hits)),
                    'items' => array_values($hits),
                ];
                $total += $result['total'];
            }
        }

        return [
            'query' => $query,
            'tokens' => $tokens,
            'total' => $total,
            'sections' => count($groups),
            'summary' => Search::summary($total, count($groups)),
            'groups' => $groups,
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @return SearchHit
     */
    private function present(string $section, Model $model, array $tokens): array
    {
        $hit = match ($section) {
            Sections::CV => $this->cvEntry($model),
            Sections::PUBLICATIONS => $this->publication($model),
            Sections::AWARDS => $this->award($model),
            Sections::VIDEOS => $this->video($model),
            Sections::PICTURES => $this->album($model),
            Sections::TEACHING => $this->teachingMaterial($model),
            Sections::COMMENTARY => $this->commentary($model),
            default => $this->otherItem($model),
        };

        return [
            'title' => $this->highlighter->highlight($hit['title'], $tokens),
            'excerpt' => $this->highlighter->excerpt($hit['excerpt'], $tokens),
            'meta' => array_values(array_filter($hit['meta'], fn ($part): bool => $part instanceof \DateTimeInterface || trim((string) $part) !== '')),
            'url' => $hit['url'],
            'members' => $hit['members'] ?? false,
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string, members?: bool}
     */
    private function cvEntry(CvEntry $entry): array
    {
        return [
            'title' => $entry->title,
            'excerpt' => $entry->role_description,
            'meta' => [$entry->section->label(), $entry->organisation, $entry->yearRange()],
            'url' => route(Sections::CV).'#cv-'.$entry->section->value,
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string}
     */
    private function publication(Publication $publication): array
    {
        $authors = array_map(fn (array $author): string => AuthorList::givenFirst($author), AuthorList::parse($publication->authors));

        return [
            'title' => $publication->title,
            'excerpt' => $publication->abstract,
            'meta' => [$publication->type->label(), $publication->year, implode(', ', $authors), $publication->venue_publisher],
            'url' => route(Sections::PUBLICATIONS).'#publication-'.$publication->slug,
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string}
     */
    private function award(Award $award): array
    {
        return [
            'title' => $award->name,
            'excerpt' => $award->description,
            'meta' => [$award->organisation, $award->year],
            'url' => route(Sections::AWARDS).'#award-'.$award->getKey(),
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string}
     */
    private function video(Video $video): array
    {
        return [
            'title' => $video->title,
            'excerpt' => $video->description,
            'meta' => [$video->event, $video->location, $video->date],
            'url' => route(Sections::VIDEOS.'.show', $video->slug),
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string}
     */
    private function album(Album $album): array
    {
        return [
            'title' => $album->title,
            'excerpt' => $album->description,
            'meta' => [],
            'url' => route(Sections::PICTURES.'.show', $album),
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string, members: bool}
     */
    private function teachingMaterial(TeachingMaterial $material): array
    {
        $course = trim((string) $material->course);

        return [
            'title' => $material->title,
            'excerpt' => $material->description,
            'meta' => [$material->course, $material->topic, $material->type->label(), $material->material_date],
            'url' => route(Sections::TEACHING).'#course-'.($course === '' ? 'other' : Str::slug($course)),
            'members' => $material->access === TeachingAccess::Members,
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string}
     */
    private function commentary(Commentary $commentary): array
    {
        return [
            'title' => $commentary->title,
            'excerpt' => $commentary->summary,
            'meta' => [$commentary->byline, $commentary->date],
            'url' => route(Sections::COMMENTARY.'.show', $commentary->slug),
        ];
    }

    /**
     * @return array{title: string, excerpt: ?string, meta: list<mixed>, url: string}
     */
    private function otherItem(OtherItem $item): array
    {
        return [
            'title' => $item->title,
            'excerpt' => $item->summary,
            'meta' => [$item->typeLabel(), $item->category, $item->date],
            'url' => route(Sections::OTHER.'.show', $item->slug),
        ];
    }
}
