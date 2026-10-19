<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\PageSizes;
use App\Constants\Sections;
use App\Models\Commentary;
use App\Support\RichText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Assembles the Commentary Corner: the tag-filtered archive and the long-form
 * detail page with its sanitised body, tags and previous/next links.
 *
 * @phpstan-type TagOption array{value: string, label: string, url: string, active: bool}
 */
class CommentaryPageService
{
    public function __construct(
        private readonly CommentaryService $commentaries,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     commentaries: LengthAwarePaginator<int, Commentary>,
     *     tags: list<TagOption>,
     *     activeTag: string|null,
     *     clearUrl: string
     * }
     */
    public function index(?string $tag = null): array
    {
        $tags = $this->commentaries->tags();
        $activeTag = $tag !== null ? $this->matchTag($tag, $tags) : null;

        return [
            'section' => $this->navigation->section(Sections::COMMENTARY, Sections::COMMENTARY),
            'commentaries' => $this->commentaries->paginatePublished($activeTag, PageSizes::COMMENTARIES),
            'tags' => $this->tagOptions($tags, $activeTag),
            'activeTag' => $activeTag,
            'clearUrl' => $this->url(),
        ];
    }

    public function find(string $slug): ?Commentary
    {
        return $this->commentaries->findPublishedBySlug($slug);
    }

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     commentary: Commentary,
     *     body: string,
     *     tags: list<TagOption>,
     *     previous: Commentary|null,
     *     next: Commentary|null
     * }
     */
    public function show(Commentary $commentary): array
    {
        $neighbours = $this->commentaries->neighbours($commentary);

        return [
            'section' => $this->navigation->section(Sections::COMMENTARY, Sections::COMMENTARY.'.show'),
            'commentary' => $commentary,
            'body' => RichText::sanitise($commentary->body),
            'tags' => $this->tagOptions($this->commentaries->normaliseTags($commentary->tags), null),
            'previous' => $neighbours['previous'],
            'next' => $neighbours['next'],
        ];
    }

    public function url(?string $tag = null): string
    {
        return route(Sections::definitions()[Sections::COMMENTARY]['route'], array_filter(['tag' => $tag], fn ($value): bool => $value !== null));
    }

    /**
     * @param  list<string>  $tags
     * @return list<TagOption>
     */
    private function tagOptions(array $tags, ?string $activeTag): array
    {
        return array_map(fn (string $tag): array => [
            'value' => $tag,
            'label' => $tag,
            'url' => $this->url($tag),
            'active' => $tag === $activeTag,
        ], $tags);
    }

    /**
     * Case-insensitive match of a requested tag against the published ones.
     *
     * @param  list<string>  $tags
     */
    private function matchTag(string $requested, array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (mb_strtolower($tag) === mb_strtolower(trim($requested))) {
                return $tag;
            }
        }

        return null;
    }
}
