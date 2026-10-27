<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\Essay;
use App\Models\Commentary;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use App\Services\Concerns\GeneratesSlugs;
use App\Support\RichText;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends AbstractContentService<Commentary>
 */
class CommentaryService extends AbstractContentService
{
    use GeneratesSlugs;

    public function __construct(CommentaryRepositoryInterface $commentaries, AuditLogService $audit, CacheRepository $cache)
    {
        parent::__construct($commentaries, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::COMMENTARIES;
    }

    protected function cacheKeys(): array
    {
        return [CacheKeys::COMMENTARIES, CacheKeys::COMMENTARIES.'.tags'];
    }

    public function findPublishedBySlug(string $slug): ?Commentary
    {
        return $this->commentaries()->findPublishedBySlug($slug);
    }

    /**
     * @return LengthAwarePaginator<int, Commentary>
     */
    public function paginatePublished(?string $tag = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->commentaries()->paginatePublishedTagged($tag, $perPage);
    }

    /**
     * @return array{previous: Commentary|null, next: Commentary|null}
     */
    public function neighbours(Commentary $commentary): array
    {
        return $this->commentaries()->neighbours($commentary);
    }

    /**
     * "Further reading": published commentaries sharing a topic tag with this one.
     *
     * @return Collection<int, Commentary>
     */
    public function related(Commentary $commentary, int $limit = Essay::FURTHER_READING_LIMIT): Collection
    {
        return $this->commentaries()->publishedRelated($commentary, $limit);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return $this->remember(CacheKeys::COMMENTARIES.'.tags', fn (): array => $this->commentaries()->publishedTags());
    }

    protected function prepare(array $data, $existing): array
    {
        if (array_key_exists('body', $data)) {
            $data['body'] = $this->eachLocale($data['body'], fn (mixed $body): ?string => RichText::sanitise(is_string($body) ? $body : null) ?: null);
        }

        foreach (['epigraph', 'epigraph_attribution'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->eachLocale($data[$field], function (mixed $value): ?string {
                    $value = is_string($value) ? trim($value) : '';

                    return $value === '' ? null : $value;
                });
            }
        }

        if (array_key_exists('tags', $data)) {
            $data['tags'] = $this->normaliseTags($data['tags']);
        }

        return $this->withEnglishSlug($data, $existing);
    }

    /**
     * @return list<string>
     */
    public function normaliseTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        if (! is_array($tags)) {
            return [];
        }

        return collect($tags)
            ->map(fn ($tag): string => trim((string) $tag))
            ->filter()
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->values()
            ->all();
    }

    private function commentaries(): CommentaryRepositoryInterface
    {
        /** @var CommentaryRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository;
    }
}
