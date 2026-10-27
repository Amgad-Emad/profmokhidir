<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Search;
use App\Http\Requests\SearchRequest;
use App\Services\NavigationService;
use App\Services\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Site-wide search. The plain page works without JavaScript; the header dialog
 * asks the same route for JSON and receives the rendered results fragment, so
 * both render through one view.
 */
class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, SearchService $search, NavigationService $navigation): View|JsonResponse
    {
        $query = $search->normalise($request->term());
        $dialog = $request->expectsJson();
        $results = $search->search($query, $request->user(), $dialog ? Search::DIALOG_PER_SECTION : Search::PAGE_PER_SECTION);
        $searchable = $search->isSearchable($query);
        $sections = $navigation->all();

        if ($dialog) {
            return response()->json([
                'query' => $results['query'],
                'total' => $results['total'],
                'summary' => $searchable ? $results['summary'] : '',
                'html' => view('search.results', ['results' => $results, 'searchable' => $searchable, 'sections' => $sections, 'tone' => 'ivory', 'compact' => true])->render(),
            ]);
        }

        return view('search.index', [
            'results' => $results,
            'searchable' => $searchable,
            'sections' => $sections,
            'title' => $results['query'] !== '' ? __('Search: :query', ['query' => $results['query']]) : __(Search::TITLE),
            'breadcrumbTitle' => __(Search::TITLE),
            'canSeeMembersOnly' => $search->canSeeMembersOnly($request->user()),
        ]);
    }
}
