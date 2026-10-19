<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AboutPageService;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function __invoke(AboutPageService $about): View
    {
        return view('about', $about->data());
    }
}
