<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminSearchRequest;
use App\Services\AdminSearchService;
use Illuminate\Http\JsonResponse;

/**
 * JSON results for the command palette (Ctrl/Cmd+K): content matching the
 * typed title, with links to edit it.
 */
class AdminSearchController extends Controller
{
    public function __invoke(AdminSearchRequest $request, AdminSearchService $search): JsonResponse
    {
        return response()->json(['results' => $search->search($request->user(), $request->term())]);
    }
}
