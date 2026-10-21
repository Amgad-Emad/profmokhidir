<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Archive;
use App\Constants\PictureView;
use App\Services\PicturesPageService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class PictureController extends Controller
{
    public function index(Request $request, PicturesPageService $page): View
    {
        return view('pictures.index', $page->index(PictureView::fromQuery($request->query(Archive::QUERY_VIEW))));
    }

    public function show(string $album, PicturesPageService $page): View
    {
        $album = $page->find($album);

        abort_if($album === null, 404);

        return view('pictures.show', $page->show($album));
    }
}
