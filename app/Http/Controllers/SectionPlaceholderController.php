<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NavigationService;
use Illuminate\Contracts\View\View;

/**
 * Temporary page for a section whose content type has not been built yet, so
 * navigation works end to end. Each section replaces this with its own controller.
 */
class SectionPlaceholderController extends Controller
{
    public function __invoke(string $section, NavigationService $navigation): View
    {
        return view('sections.placeholder', [
            'section' => $navigation->section($section),
        ]);
    }
}
