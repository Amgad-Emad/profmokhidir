<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CommentaryIndexRequest;
use App\Services\CommentaryPageService;
use Illuminate\Contracts\View\View;

class CommentaryController extends Controller
{
    public function index(CommentaryIndexRequest $request, CommentaryPageService $page): View
    {
        return view('commentary.index', $page->index($request->tag()));
    }

    public function show(string $slug, CommentaryPageService $page): View
    {
        $commentary = $page->find($slug);

        abort_if($commentary === null, 404);

        return view('commentary.show', $page->show($commentary));
    }
}
