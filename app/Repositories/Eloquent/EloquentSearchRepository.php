<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

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
use App\Models\Video;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Fulltext search in boolean mode over each table's folded search column
 * (App\Support\SearchText: the listed columns in English and Arabic, with
 * Arabic letter forms, diacritics and the tatweel normalised; the query is
 * folded the same way). Every word must match as a prefix; words the index
 * does not hold (shorter than InnoDB's minimum token size, or on its stopword
 * list) are matched with LIKE instead.
 */
class EloquentSearchRepository implements SearchRepositoryInterface
{
    /**
     * Section key => searched model and the columns its search column folds
     * (the model's $searchColumns).
     *
     * @return array<string, array{model: class-string<Model>, columns: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            Sections::CV => ['model' => CvEntry::class, 'columns' => ['title', 'organisation', 'role_description']],
            Sections::PUBLICATIONS => ['model' => Publication::class, 'columns' => ['title', 'authors', 'venue_publisher', 'abstract']],
            Sections::AWARDS => ['model' => Award::class, 'columns' => ['name', 'organisation', 'description']],
            Sections::VIDEOS => ['model' => Video::class, 'columns' => ['title', 'event', 'location', 'description']],
            Sections::PICTURES => ['model' => Album::class, 'columns' => ['title', 'description']],
            Sections::TEACHING => ['model' => TeachingMaterial::class, 'columns' => ['title', 'course', 'topic', 'description']],
            Sections::COMMENTARY => ['model' => Commentary::class, 'columns' => ['title', 'summary']],
            Sections::OTHER => ['model' => OtherItem::class, 'columns' => ['title', 'summary']],
        ];
    }

    public function search(string $term, bool $includeMembersOnly, int $perSection): array
    {
        $tokens = $this->tokens($term);
        $results = [];

        if ($tokens === []) {
            return $results;
        }

        foreach (Search::sections() as $section) {
            $definition = self::definitions()[$section];
            /** @var Model $model */
            $model = new $definition['model'];

            $query = $model->newQuery()->published();
            $this->matching($query, [SearchText::COLUMN], $tokens);

            if ($section === Sections::TEACHING && ! $includeMembersOnly) {
                $query->where('access', TeachingAccess::Public->value);
            }

            $total = (clone $query)->count();

            if ($total === 0) {
                continue;
            }

            /** @var Collection<int, Model> $items */
            $items = $query->ordered()->limit($perSection)->get();

            $results[$section] = ['total' => $total, 'items' => $items];
        }

        return $results;
    }

    public function tokens(string $term): array
    {
        preg_match_all('/[\p{L}\p{N}]+(?:[\'’][\p{L}]+)?/u', $term, $matches);

        return array_values(array_unique(array_filter(array_map(
            fn (string $word): string => SearchText::normalise($word),
            $matches[0],
        ), fn (string $word): bool => $word !== '')));
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $columns
     * @param  list<string>  $tokens
     */
    private function matching(Builder $query, array $columns, array $tokens): void
    {
        $indexed = array_values(array_filter($tokens, fn (string $token): bool => Search::isIndexed($token)));
        $short = array_values(array_diff($tokens, $indexed));

        $query->where(function (Builder $query) use ($columns, $indexed, $short): void {
            if ($indexed !== []) {
                $query->whereFullText($columns, $this->booleanQuery($indexed), ['mode' => 'boolean']);
            }

            foreach ($short as $token) {
                $query->where(function (Builder $query) use ($columns, $token): void {
                    foreach ($columns as $column) {
                        $query->orWhere($column, 'like', '%'.$this->escapeLike($token).'%');
                    }
                });
            }
        });
    }

    /**
     * "+word* +other*": every word required, matched as a prefix. Apostrophes are
     * dropped because the boolean parser treats them as operators.
     *
     * @param  list<string>  $tokens
     */
    private function booleanQuery(array $tokens): string
    {
        return implode(' ', array_map(
            fn (string $token): string => '+'.str_replace(["'", '’'], '', $token).'*',
            $tokens,
        ));
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
