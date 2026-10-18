<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StyleguideService;
use Illuminate\Contracts\View\View;

class StyleguideController extends Controller
{
    public function __invoke(StyleguideService $styleguide): View
    {
        return view('styleguide.index', $styleguide->sections());
    }
}
