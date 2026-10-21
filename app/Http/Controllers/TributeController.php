<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\TributeLimitReachedException;
use App\Http\Requests\PreviewTributeRequest;
use App\Http\Requests\StoreTributeRequest;
use App\Http\Requests\TributeIndexRequest;
use App\Models\Tribute;
use App\Services\TributeService;
use App\Services\TributesPageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TributeController extends Controller
{
    /** Error bag key for the per-address submission limit. */
    public const string LIMIT_ERROR = 'limit';

    public function index(TributeIndexRequest $request, TributesPageService $page): View
    {
        return view('tributes.index', $page->index($request->relationship()));
    }

    public function create(TributesPageService $page): View
    {
        return view('tributes.create', $page->create());
    }

    /**
     * Step two: the tribute as it will appear. Reached by posting the form, or
     * by returning (GET) after a failed send, in which case the flashed input is used.
     */
    public function preview(PreviewTributeRequest $request, TributesPageService $page): View
    {
        return view('tributes.preview', $page->preview($request->tribute()));
    }

    public function previewAgain(Request $request, TributesPageService $page): View|RedirectResponse
    {
        $old = $request->session()->getOldInput();

        if (! filled($old['message'] ?? null) || ! filled($old['name'] ?? null) || ! filled($old['relationship'] ?? null)) {
            return redirect()->route(TributesPageService::ROUTE_CREATE);
        }

        return view('tributes.preview', $page->preview([
            'name' => (string) $old['name'],
            'relationship' => (string) $old['relationship'],
            'location' => $old['location'] ?? null,
            'message' => (string) $old['message'],
            'email' => $old['email'] ?? null,
        ]));
    }

    /**
     * "Change something" on the preview: back to the form with the fields filled in.
     */
    public function edit(Request $request): RedirectResponse
    {
        return redirect()->route(TributesPageService::ROUTE_CREATE)
            ->withInput($request->only(['name', 'relationship', 'location', 'message', 'email']));
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

    /**
     * The tribute's share card (drawn once, then served from the public disk).
     * Only approved tributes have one.
     */
    public function card(Tribute $tribute, TributeService $tributes): BinaryFileResponse
    {
        $path = $tributes->shareCardPath($tribute);

        abort_if($path === null, 404);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$tributes->shareCardFilename($tribute).'"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
