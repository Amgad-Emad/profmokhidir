<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Site-wide search over the published content of every searchable section
 * (App\Constants\Search::sections()), backed by the MySQL fulltext indexes.
 */
interface SearchRepositoryInterface
{
    /**
     * Published records matching the term, grouped by section key and in each
     * section's display order. `total` counts every match, `items` holds at most
     * `perSection` of them. Members-only teaching materials are included only
     * when `includeMembersOnly` is true.
     *
     * @return array<string, array{total: int, items: Collection<int, Model>}>
     */
    public function search(string $term, bool $includeMembersOnly, int $perSection): array;

    /**
     * The words a term is matched on, in order, with punctuation removed.
     *
     * @return list<string>
     */
    public function tokens(string $term): array;
}
