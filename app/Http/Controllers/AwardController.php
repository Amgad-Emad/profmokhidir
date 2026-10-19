<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AwardsPageService;
use Illuminate\Contracts\View\View;

class AwardController extends Controller
{
    public function __invoke(AwardsPageService $page): View
    {
        return view('awards', $page->data());
    }
}
