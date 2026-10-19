<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CvPageService;
use Illuminate\Contracts\View\View;

/**
 * View-only CV. There is deliberately no download, print or file route.
 */
class CvController extends Controller
{
    public function __invoke(CvPageService $cv): View
    {
        return view('cv', $cv->data());
    }
}
