<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Constants\AdminNavigation;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Stands in for a dashboard section whose screens are not built yet. The
 * route carries the navigation item key as a default parameter.
 */
class PlaceholderController extends Controller
{
    public function __invoke(Request $request, string $item): View
    {
        $definition = AdminNavigation::get($item);

        return view('admin.placeholder', [
            'key' => $item,
            'item' => $definition,
            'group' => AdminNavigation::groups()[$definition['group']],
        ]);
    }
}
