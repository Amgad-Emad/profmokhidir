<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\CommunityCopy;
use App\Http\Requests\CommunitySignupRequest;
use App\Models\User;
use App\Services\CommunityPageService;
use App\Services\CommunityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The public Scientific Community page: explanation, conduct rules, privacy
 * note and the application form (or the signed-in user's standing).
 */
class CommunityController extends Controller
{
    public const string STATUS_KEY = 'community.status';

    public function index(Request $request, CommunityPageService $page): View
    {
        return view('community.index', $page->index($request->user()));
    }

    public function store(CommunitySignupRequest $request, CommunityService $community): RedirectResponse
    {
        if ($request->isSpam()) {
            return redirect()->route(CommunityPageService::ROUTE_INDEX);
        }

        $user = $request->user();

        if ($user instanceof User) {
            $community->apply($user, $request->application());

            return redirect()->route(CommunityPageService::ROUTE_INDEX);
        }

        $user = $community->register($request->application());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route(CommunityPageService::ROUTE_INDEX)->with(self::STATUS_KEY, CommunityCopy::SUBMITTED);
    }
}
