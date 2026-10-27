<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminUx;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\Permissions;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Content search for the command palette: records of every type whose title
 * (or other search columns) match, a few per type, each with a link to its
 * edit screen and its status. Through the content services, so the
 * repositories' search columns apply.
 *
 * @phpstan-type Result array{group: string, label: string, hint: string, url: string, icon: string, status: string}
 */
class AdminSearchService
{
    /**
     * @return list<Result>
     */
    public function search(User $user, string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < AdminUx::SEARCH_MIN_LENGTH || ! $user->can(Permissions::MANAGE_CONTENT)) {
            return [];
        }

        $results = [];

        foreach (ContentTypes::services() as $type => $serviceClass) {
            $definition = ContentTypes::get($type);
            $matches = app($serviceClass)->search($term, [], AdminUx::PALETTE_RESULTS_PER_TYPE);

            foreach ($matches as $record) {
                $label = $record->getAttribute('title') ?? $record->getAttribute('name') ?? $record->getAttribute('quote') ?? __($definition['singular']);
                $status = $record->getAttribute('status');

                $results[] = [
                    'group' => __($definition['plural']),
                    'label' => Str::limit((string) $label, 90),
                    'hint' => __($definition['singular']),
                    'url' => $type === ContentTypes::PROFILE
                        ? route(ContentTypes::editRoute($type))
                        : route(ContentTypes::editRoute($type), [ContentTypes::routeParameter($type) => $record->getKey()]),
                    'icon' => AdminUx::icon($definition['nav']),
                    'status' => $status instanceof ContentStatus ? $status->label() : '',
                ];
            }
        }

        return $results;
    }
}
