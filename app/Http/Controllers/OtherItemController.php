<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OtherPageService;
use Illuminate\Contracts\View\View;

class OtherItemController extends Controller
{
    public function index(OtherPageService $page): View
    {
        return view('other.index', $page->index());
    }

    public function show(string $slug, OtherPageService $page): View
    {
        $item = $page->find($slug);

        abort_if($item === null, 404);

        return view('other.show', $page->show($item));
    }
}
