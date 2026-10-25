<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\Preview;
use App\Support\PreviewContext;
use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed preview links (see PreviewService): when a GET request carries
 * `preview=<type>.<id>` and a valid, unexpired signature, the named draft is
 * let through the published() scope for this request only. The cached public
 * listings are bypassed (an in-memory store for the request) so the draft
 * appears without touching what visitors are served, and the response is
 * never cached or indexed. Runs as global middleware, before any controller
 * or page service is built.
 */
class EnablePreview
{
    /** The in-memory store a preview renders with, so it never reads or warms the shared cache (config/cache.php). */
    private const string PREVIEW_STORE = 'preview';

    public function __construct(
        private readonly PreviewContext $context,
        private readonly CacheManager $cache,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $preview = Preview::parse(is_string($request->query(Preview::PARAM)) ? $request->query(Preview::PARAM) : null);

        if ($preview === null || ! $request->isMethod('GET')) {
            return $next($request);
        }

        abort_unless($request->hasValidSignature(), 403, 'This preview link has expired. Open Preview again from the edit screen for a fresh one.');

        $this->context->activate(Preview::modelFor($preview['type']), $preview['id']);
        $previousStore = $this->useCacheStore(self::PREVIEW_STORE);
        $request->attributes->set(Preview::ATTRIBUTE, $preview);

        try {
            $response = $next($request);
        } finally {
            // Long-lived workers (and tests) reuse the application: leave nothing behind.
            $this->context->clear();
            $this->cache->store(self::PREVIEW_STORE)->flush();
            $this->useCacheStore($previousStore);
        }

        $response->headers->set('X-Robots-Tag', Preview::ROBOTS);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    /**
     * Switch the default cache store, returning the one it replaces.
     */
    private function useCacheStore(string $store): string
    {
        $previous = $this->cache->getDefaultDriver();
        $this->cache->setDefaultDriver($store);
        app()->forgetInstance('cache.store');

        return $previous;
    }
}
