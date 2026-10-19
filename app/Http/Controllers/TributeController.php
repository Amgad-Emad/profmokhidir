<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\TributeLimitReachedException;
use App\Http\Requests\StoreTributeRequest;
use App\Services\TributeService;
use App\Services\TributesPageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TributeController extends Controller
{
    /** Error bag key for the per-address submission limit. */
    public const string LIMIT_ERROR = 'limit';

    public function index(TributesPageService $page): View
    {
        return view('tributes.index', $page->index());
    }

    public function create(TributesPageService $page): View
    {
        return view('tributes.create', $page->create());
    }

    public function store(StoreTributeRequest $request, TributeService $tributes): RedirectResponse
    {
        if (! $request->isSpam()) {
            try {
                $tributes->submit($request->tribute(), $request->ip());
            } catch (TributeLimitReachedException $exception) {
                return back()->withInput()->withErrors([self::LIMIT_ERROR => $exception->getMessage()]);
            }
        }

        return redirect()->route(TributesPageService::ROUTE_THANKS);
    }

    public function thanks(TributesPageService $page): View
    {
        return view('tributes.thanks', $page->thanks());
    }
}
