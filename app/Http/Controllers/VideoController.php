<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\VideosPageService;
use Illuminate\Contracts\View\View;

class VideoController extends Controller
{
    public function index(VideosPageService $page): View
    {
        return view('videos.index', $page->index());
    }

    public function show(string $slug, VideosPageService $page): View
    {
        $video = $page->find($slug);

        abort_if($video === null, 404);

        return view('videos.show', $page->show($video));
    }
}
