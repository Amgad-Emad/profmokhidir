<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ListDensity;
use App\Constants\ListFilterType;
use App\Constants\ListToolbar;
use App\Models\User;
use App\Repositories\Contracts\ListPreferenceRepositoryInterface;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Closure;

/**
 * The logic behind x-admin.list-toolbar: cleaning the state that arrives in
 * the address, telling which view (tab) it matches, the counts of the views
 * and of each filter option, the wording of filter buttons and chips, and
 * each user's remembered view and row density per screen.
 *
 * Screens describe themselves with ListFilter, ListView and ListSort lists and
 * hand over a counter: fn (array $criteria, string $search): int, where
 * criteria are the filters with multi-choice values as lists.
 */
class ListToolbarService
{
    public function __construct(
        private readonly ListPreferenceRepositoryInterface $preferences,
        private readonly LocaleService $locales,
    ) {}

    /**
     * Keep only declared filters with known options, as strings; anything
     * else from the address (arrays, unknown keys or values) is dropped.
     *
     * @param  array<mixed>  $filters
     * @param  list<ListFilter>  $definitions
     * @return array<string, string>
     */
    public function normalise(array $filters, array $definitions): array
    {
        $clean = [];

        foreach ($definitions as $definition) {
            $raw = $filters[$definition->key] ?? null;

            if (! is_scalar($raw)) {
                continue;
            }

            $value = $definition->join($definition->values([$definition->key => mb_substr((string) $raw, 0, ListToolbar::MAX_VALUE_LENGTH * 10)]));

            if ($value !== '') {
                $clean[$definition->key] = $value;
            }
        }

        return $clean;
    }

    /**
     * Filters as the repositories take them: multi-choice values as lists.
     *
     * @param  array<string, string>  $filters
     * @param  list<ListFilter>  $definitions
     * @return array<string, string|list<string>>
     */
    public function criteria(array $filters, array $definitions): array
    {
        $criteria = [];

        foreach ($definitions as $definition) {
            $values = $definition->values($filters);

            if ($values !== []) {
                $criteria[$definition->key] = $definition->type === ListFilterType::Multi ? $values : $values[0];
            }
        }

        return $criteria;
    }

    /**
     * The filters a view sets, cleaned like any other state.
     *
     * @param  list<ListFilter>  $definitions
     * @return array<string, string>
     */
    public function viewFilters(ListView $view, array $definitions): array
    {
        return $this->normalise($view->filters, $definitions);
    }

    /**
     * The key of the view the filters match: the current one when it still
     * matches (two views may set the same filters), else the first that does,
     * else '' (a custom combination highlights no tab).
     *
     * @param  array<string, string>  $filters
     * @param  list<ListView>  $views
     * @param  list<ListFilter>  $definitions
     */
    public function matchingView(array $filters, array $views, array $definitions, string $current = ''): string
    {
        $filters = $this->normalise($filters, $definitions);
        $matches = [];

        foreach ($views as $view) {
            if (! $view->trash && $this->sameFilters($this->viewFilters($view, $definitions), $filters)) {
                $matches[] = $view->key;
            }
        }

        if (in_array($current, $matches, true)) {
            return $current;
        }

        return $matches[0] ?? '';
    }

    /**
     * @param  list<ListView>  $views
     */
    public function view(array $views, string $key): ?ListView
    {
        foreach ($views as $view) {
            if ($view->key === $key) {
                return $view;
            }
        }

        return null;
    }

    /**
     * The filters a screen opens with: the default view's, over each filter's own default.
     *
     * @param  list<ListFilter>  $definitions
     * @return array<string, string>
     */
    public function initialFilters(?ListView $view, array $definitions): array
    {
        $defaults = [];

        foreach ($definitions as $definition) {
            if ($definition->default !== null) {
                $defaults[$definition->key] = $definition->default;
            }
        }

        return $this->normalise($view === null ? $defaults : $view->filters + $defaults, $definitions);
    }

    /**
     * Turn one option of a filter on or off. A single-choice filter takes the
     * value (or clears with null); a multi-choice one adds or removes it; a flag toggles.
     *
     * @param  array<string, string>  $filters
     * @return array<string, string>
     */
    public function toggle(array $filters, ListFilter $definition, ?string $value): array
    {
        $current = $definition->values($filters);

        $next = match (true) {
            $value === null || $value === '' => [],
            $definition->type === ListFilterType::Multi => in_array($value, $current, true) ? array_values(array_diff($current, [$value])) : [...$current, $value],
            $definition->type === ListFilterType::Flag => $current === [] ? [$value] : [],
            default => [$value],
        };

        $joined = $definition->join($next);
        unset($filters[$definition->key]);

        return $joined === '' ? $filters : $filters + [$definition->key => $joined];
    }

    /**
     * Take away one value of a filter (or all of it with null).
     *
     * @param  array<string, string>  $filters
     * @return array<string, string>
     */
    public function remove(array $filters, ListFilter $definition, ?string $value = null): array
    {
        if ($value === null) {
            unset($filters[$definition->key]);

            return $filters;
        }

        $joined = $definition->join(array_values(array_diff($definition->values($filters), [$value])));
        unset($filters[$definition->key]);

        return $joined === '' ? $filters : $filters + [$definition->key => $joined];
    }

    /**
     * The button's wording: "Status" when unset, "Status: Draft" when set,
     * "Type: Book +2" for several choices.
     *
     * @param  list<string>  $values
     */
    public function buttonLabel(ListFilter $definition, array $values): string
    {
        if ($values === []) {
            return $definition->label;
        }

        $first = $definition->type === ListFilterType::Flag ? ($definition->chip ?? $definition->options[$values[0]]) : $definition->options[$values[0]];
        $rest = count($values) - ListToolbar::BUTTON_LABEL_CHOICES;

        return $definition->label.': '.$first.($rest > 0 ? ' +'.$this->locales->digits($rest) : '');
    }

    /**
     * One removable chip per chosen value, in the filters' order.
     *
     * @param  array<string, string>  $filters
     * @param  list<ListFilter>  $definitions
     * @return list<array{key: string, value: string, label: string}>
     */
    public function chips(array $filters, array $definitions): array
    {
        $chips = [];

        foreach ($definitions as $definition) {
            foreach ($definition->values($filters) as $value) {
                $chips[] = [
                    'key' => $definition->key,
                    'value' => $value,
                    'label' => $definition->type === ListFilterType::Flag
                        ? ($definition->chip ?? $definition->options[$value])
                        : $definition->label.': '.$definition->options[$value],
                ];
            }
        }

        return $chips;
    }

    /**
     * How many records each option of a filter would show with the other
     * filters and the search as they are. `grouped`, when given, answers
     * for every option at once (fn (string $key, array $criteria, string $search): array<value, int>).
     *
     * @param  array<string, string>  $filters
     * @param  list<ListFilter>  $definitions
     * @return array<string, int>
     */
    public function facets(ListFilter $definition, array $filters, string $search, array $definitions, Closure $count, ?Closure $grouped = null): array
    {
        $others = $filters;
        unset($others[$definition->key]);
        $criteria = $this->criteria($others, $definitions);

        if ($grouped !== null && $definition->type !== ListFilterType::Flag) {
            $counts = $grouped($definition->key, $criteria, $search);

            if (is_array($counts)) {
                $facets = [];

                foreach (array_keys($definition->options) as $value) {
                    $facets[(string) $value] = (int) ($counts[(string) $value] ?? 0);
                }

                return $facets;
            }
        }

        $facets = [];

        foreach (array_keys($definition->options) as $value) {
            $facets[(string) $value] = (int) $count($criteria + [$definition->key => $definition->type === ListFilterType::Multi ? [(string) $value] : (string) $value], $search);
        }

        return $facets;
    }

    /**
     * Everything the toolbar draws, from the screen's description and state.
     *
     * @param  list<ListFilter>  $definitions
     * @param  list<ListView>  $views
     * @param  list<ListSort>  $sorts
     * @param  array{search: string, filters: array<string, string>, sort: string, direction: string, tab: string, density: ListDensity, shown: int}  $state
     * @param  Closure(array<string, mixed>, string): int  $count
     * @param  Closure(ListView): int  $viewCount
     * @param  (Closure(string, array<string, mixed>, string): ?array<string, int>)|null  $grouped
     * @return array<string, mixed>
     */
    public function describe(array $definitions, array $views, array $sorts, array $state, Closure $count, Closure $viewCount, ?Closure $grouped = null): array
    {
        $filters = $this->normalise($state['filters'], $definitions);
        $current = $this->view($views, $state['tab']);
        $trash = $current?->trash ?? false;
        $describedFilters = [];

        foreach ($definitions as $definition) {
            $values = $definition->values($filters);
            $facets = $trash ? [] : $this->facets($definition, $filters, $state['search'], $definitions, $count, $grouped);
            $options = [];

            foreach ($definition->options as $value => $label) {
                $options[] = ['value' => (string) $value, 'label' => $label, 'count' => $facets[(string) $value] ?? null, 'checked' => in_array((string) $value, $values, true)];
            }

            $describedFilters[] = [
                'key' => $definition->key,
                'label' => $definition->label,
                'type' => $definition->type->value,
                'role' => $definition->type->optionRole(),
                'button' => $this->buttonLabel($definition, $values),
                'active' => $values !== [],
                'toolbar' => $definition->toolbar,
                'options' => $options,
            ];
        }

        $activeSort = null;
        $describedSorts = [];

        foreach ($sorts as $sort) {
            $active = $sort->column === $state['sort'] && ($sort->column === '' || $sort->direction === $state['direction']);
            $activeSort = $active ? $sort : $activeSort;
            $describedSorts[] = ['column' => $sort->column, 'direction' => $sort->direction, 'label' => $sort->label, 'active' => $active];
        }

        $describedViews = [];

        foreach ($views as $view) {
            $describedViews[] = ['key' => $view->key, 'label' => $view->label, 'count' => $viewCount($view), 'active' => $view->key === $state['tab'], 'trash' => $view->trash];
        }

        $all = $this->view($views, ListToolbar::VIEW_ALL);

        return [
            'views' => $describedViews,
            'filters' => $describedFilters,
            'activeFilters' => count(array_filter($describedFilters, fn (array $filter): bool => $filter['active'])),
            'chips' => $trash ? [] : $this->chips($filters, $definitions),
            'sorts' => $describedSorts,
            'sortLabel' => $activeSort?->label,
            'sortActive' => $activeSort !== null && $activeSort->column !== '',
            'search' => $state['search'],
            'density' => $state['density']->value,
            'nextDensity' => $state['density']->other()->value,
            'shown' => $state['shown'],
            'total' => $trash ? $state['shown'] : ($all !== null ? $viewCount($all) : $count([], '')),
            'filtered' => trim($state['search']) !== '' || $filters !== [],
            'trash' => $trash,
        ];
    }

    // -- Remembered views ------------------------------------------------------

    /**
     * Keep the user's view of a screen, as the address holds it.
     *
     * @param  array<string, mixed>  $state
     */
    public function remember(User $user, string $screen, array $state): void
    {
        $this->preferences->save($user, $screen, $this->storable($state));
    }

    /**
     * The view the user last left the screen in, or null.
     *
     * @return array<string, mixed>|null
     */
    public function recall(User $user, string $screen): ?array
    {
        $state = $this->preferences->find($user, $screen)?->state;

        return is_array($state) && $this->storable($state) !== [] ? $this->storable($state) : null;
    }

    public function density(User $user, string $screen): ListDensity
    {
        return $this->preferences->find($user, $screen)?->density ?? ListDensity::DEFAULT;
    }

    public function setDensity(User $user, string $screen, ListDensity $density): void
    {
        $this->preferences->save($user, $screen, null, $density);
    }

    /**
     * The address parameters of a state (empty values left out), for a link or a redirect.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function query(array $state): array
    {
        return $this->storable($state);
    }

    /**
     * Only the address keys, with empty values dropped and lengths capped.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function storable(array $state): array
    {
        $clean = [];
        $search = $state[ListToolbar::PARAM_SEARCH] ?? '';
        $filters = $state[ListToolbar::PARAM_FILTERS] ?? [];

        if (is_string($search) && trim($search) !== '') {
            $clean[ListToolbar::PARAM_SEARCH] = mb_substr($search, 0, ListToolbar::MAX_SEARCH_LENGTH);
        }

        if (is_array($filters)) {
            $filters = array_filter($filters, fn (mixed $value, mixed $key): bool => is_string($key) && is_scalar($value) && (string) $value !== '', ARRAY_FILTER_USE_BOTH);

            if ($filters !== []) {
                $clean[ListToolbar::PARAM_FILTERS] = array_map(fn (mixed $value): string => mb_substr((string) $value, 0, ListToolbar::MAX_VALUE_LENGTH * 10), $filters);
            }
        }

        foreach ([ListToolbar::PARAM_SORT, ListToolbar::PARAM_TAB] as $key) {
            if (is_string($state[$key] ?? null) && $state[$key] !== '') {
                $clean[$key] = mb_substr($state[$key], 0, ListToolbar::MAX_VALUE_LENGTH);
            }
        }

        if (isset($clean[ListToolbar::PARAM_SORT]) && in_array($state[ListToolbar::PARAM_DIRECTION] ?? null, ListToolbar::directions(), true)) {
            $clean[ListToolbar::PARAM_DIRECTION] = $state[ListToolbar::PARAM_DIRECTION];
        }

        return $clean;
    }

    /**
     * @param  array<string, string>  $a
     * @param  array<string, string>  $b
     */
    private function sameFilters(array $a, array $b): bool
    {
        ksort($a);
        ksort($b);

        return $a === $b;
    }
}
