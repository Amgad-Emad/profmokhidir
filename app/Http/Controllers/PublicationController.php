<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PublicationIndexRequest;
use App\Services\PublicationsPageService;
use Illuminate\Contracts\View\View;

class PublicationController extends Controller
{
    public function __invoke(PublicationIndexRequest $request, PublicationsPageService $page): View
    {
        $filters = $request->filters();

        return view('publications.index', $page->data($filters['type'], $filters['year']));
    }
}
