<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CollectAnalyticsRequest;
use App\Services\AnalyticsCollectorService;
use Illuminate\Http\Response;

/**
 * POST /a/collect: one analytics beacon. Always an empty 204 (also when the
 * beacon is ignored), so the response says nothing about what was recorded.
 */
class AnalyticsCollectController extends Controller
{
    public function __construct(private readonly AnalyticsCollectorService $collector) {}

    public function __invoke(CollectAnalyticsRequest $request): Response
    {
        $this->collector->collect($request, $request->validated());

        return response()->noContent()->header('Cache-Control', 'no-store');
    }
}
