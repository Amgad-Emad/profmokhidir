<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\HomePageService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(HomePageService $home): View
    {
        return view('home', $home->data());
    }
}
