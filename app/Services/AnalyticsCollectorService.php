<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Analytics;
use App\Constants\AnalyticsEventType;
use App\Constants\Seo;
use App\Repositories\Contracts\AnalyticsEventRepositoryInterface;
use App\Support\ReferrerDomain;
use App\Support\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Records one analytics beacon: a page view (path, title, section, referrer
 * domain, country, device type, browser family, UTM source) or that view's
 * engagement. Before anything is stored the request is checked: analytics on,
 * no Do Not Track or Global Privacy Control, not signed-in staff, not a bot,
 * not an excluded page. The IP address and user agent are used in memory for
 * the daily visitor hash, the country and the device, then discarded.
 */
class AnalyticsCollectorService
{
    public const string IGNORED_DISABLED = 'disabled';

    public const string IGNORED_OPTED_OUT = 'opted-out';

    public const string IGNORED_STAFF = 'staff';

    public const string IGNORED_BOT = 'bot';

    public const string IGNORED_PATH = 'excluded-path';

    public const string IGNORED_DUPLICATE = 'duplicate';

    public const string IGNORED_UNKNOWN_VIEW = 'unknown-view';

    public const int TITLE_MAX = 160;

    public const int UTM_MAX = 60;

    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly VisitorHashService $hashes,
        private readonly GeoLocationService $geo,
        private readonly AnalyticsEventRepositoryInterface $events,
    ) {}

    /**
     * Why the request must not be recorded, or null when it may be.
     */
    public function ignoreReason(Request $request): ?string
    {
        return match (true) {
            ! $this->analytics->enabled() => self::IGNORED_DISABLED,
            $this->analytics->optedOut($request) => self::IGNORED_OPTED_OUT,
            $this->analytics->isStaff($request->user()) => self::IGNORED_STAFF,
            UserAgent::isBot($request->userAgent()) => self::IGNORED_BOT,
            default => null,
        };
    }

    /**
     * Record a validated beacon. Returns null when it was stored, or why it was not.
     *
     * @param  array<string, mixed>  $payload
     */
    public function collect(Request $request, array $payload): ?string
    {
        if (($reason = $this->ignoreReason($request)) !== null) {
            return $reason;
        }

        $visitor = $this->hashes->hash((string) $request->ip(), (string) $request->userAgent());

        if (AnalyticsEventType::from((string) $payload['t']) === AnalyticsEventType::Engagement) {
            $recorded = $this->events->recordEngagement(
                (string) $payload['v'],
                $visitor,
                min((int) ($payload['e'] ?? 0), Analytics::MAX_ENGAGED_SECONDS),
                (int) ($payload['s'] ?? 0),
            );

            return $recorded ? null : self::IGNORED_UNKNOWN_VIEW;
        }

        $path = self::path((string) $payload['p']);

        if (Analytics::isExcludedPath($path)) {
            return self::IGNORED_PATH;
        }

        if ($this->events->findByView((string) $payload['v']) !== null) {
            return self::IGNORED_DUPLICATE;
        }

        $userAgent = (string) $request->userAgent();

        $this->events->create([
            'view_id' => (string) $payload['v'],
            'visitor_hash' => $visitor,
            'occurred_at' => Carbon::now(),
            'path' => $path,
            'title' => self::title($payload['ti'] ?? null),
            'section' => Analytics::sectionFor($path),
            'referrer_domain' => ReferrerDomain::from($payload['r'] ?? null, $request->getHost()),
            'country' => $this->geo->country($request),
            'device' => UserAgent::device($userAgent),
            'browser' => UserAgent::browser($userAgent),
            'utm_source' => self::utmSource($payload['u'] ?? null),
        ]);

        return null;
    }

    /**
     * The path without a trailing slash (except the home page), lower-cased.
     */
    public static function path(string $path): string
    {
        $path = '/'.trim(mb_strtolower($path), '/');

        return mb_substr($path, 0, 255);
    }

    /**
     * The page title without the site name, or null.
     */
    public static function title(mixed $title): ?string
    {
        if (! is_string($title)) {
            return null;
        }

        $title = trim(Str::before(strip_tags($title), Seo::TITLE_SEPARATOR.Seo::SITE_NAME));
        $title = $title === Seo::SITE_NAME ? 'Home' : $title;

        return $title === '' ? null : Str::limit($title, self::TITLE_MAX - 1, '…');
    }

    /**
     * The UTM source, lower-cased and limited to letters, digits, dot, dash and underscore.
     */
    public static function utmSource(mixed $source): ?string
    {
        if (! is_string($source)) {
            return null;
        }

        $source = preg_replace('/[^a-z0-9._-]+/', '-', mb_strtolower(trim($source))) ?? '';
        $source = trim(mb_substr($source, 0, self::UTM_MAX), '-');

        return $source === '' ? null : $source;
    }
}
