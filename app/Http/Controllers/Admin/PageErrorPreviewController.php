<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Constants\Preview;
use App\Http\Controllers\Controller;
use App\Services\PageService;
use App\Support\PreviewContext;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Admin › Pages › Error pages: one error page drawn with the editor's drafts,
 * for the preview beside the form (the real ones are only reached by failing).
 * Answers 200 so the preview frame shows it, and is never cached or indexed.
 */
class PageErrorPreviewController extends Controller
{
    public function __invoke(PageService $pages, PreviewContext $preview, string $page, string $code): Response
    {
        $model = $pages->findByKey($page);
        abort_if($model === null, 404);
        Gate::authorize('view', $model);

        $preview->activate($model::class, (int) $model->getKey());

        try {
            $html = view('errors.'.$code)->render();
        } finally {
            $preview->clear();
        }

        return response($html)
            ->header('X-Robots-Tag', Preview::ROBOTS)
            ->header('Cache-Control', 'no-store, private');
    }
}
