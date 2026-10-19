<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TeachingMaterialIndexRequest;
use App\Services\TeachingMaterialService;
use App\Services\TeachingPageService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeachingMaterialController extends Controller
{
    use AuthorizesRequests;

    public function index(TeachingMaterialIndexRequest $request, TeachingPageService $page): View
    {
        $filters = $request->filters();

        return view('teaching.index', $page->data($request->user(), $filters['topic'], $filters['type']));
    }

    /**
     * Serve a material's file. Every download, public or members-only, passes
     * the policy so private-disk files are never reachable without the check.
     */
    public function download(Request $request, int $material, TeachingMaterialService $materials): Response
    {
        $material = $materials->find($material);

        abort_if($material === null, 404);

        if ($material->isMembersOnly() && ! $request->hasValidSignature()) {
            abort(403, 'This link has expired. Open the material again from the Teaching Materials page.');
        }

        $this->authorize('download', $material);

        $file = $material->file();

        abort_if($file === null, 404);

        return $file->toResponse($request);
    }
}
