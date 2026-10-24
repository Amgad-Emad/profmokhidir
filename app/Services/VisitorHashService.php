<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Analytics;
use App\Repositories\Contracts\AnalyticsSaltRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Counts unique visitors without cookies: a visitor is an HMAC of the IP
 * address, the user agent and the site id under a random salt that belongs to
 * one day. The first page view of a day makes that day's salt and deletes every
 * earlier one, so a hash can never be recomputed or linked to the next day's.
 * The IP and user agent are only ever held in memory.
 */
class VisitorHashService
{
    /** @var array<string, string> Salts already read in this process, by day. */
    private array $salts = [];

    public function __construct(private readonly AnalyticsSaltRepositoryInterface $repository) {}

    public function hash(string $ip, string $userAgent, ?CarbonInterface $day = null): string
    {
        return hash_hmac(Analytics::HASH_ALGORITHM, $ip."\n".$userAgent."\n".$this->siteId(), $this->saltFor($day ?? Carbon::now()));
    }

    /**
     * The day's salt, made (and every earlier salt deleted) on first use.
     */
    public function saltFor(CarbonInterface $day): string
    {
        $key = $day->toDateString();

        if (isset($this->salts[$key])) {
            return $this->salts[$key];
        }

        $stored = $this->repository->forDay($day);

        if ($stored === null) {
            $stored = $this->repository->firstOrCreate($day, bin2hex(random_bytes(Analytics::SALT_BYTES)));
            $this->repository->deleteBefore($day);
        }

        $this->salts = [$key => $stored->salt];

        return $stored->salt;
    }

    /**
     * Make today's salt if there is none yet and delete the older ones (nightly).
     * Returns how many old salts were deleted.
     */
    public function rotate(?CarbonInterface $day = null): int
    {
        $day ??= Carbon::now();
        $deleted = $this->repository->deleteBefore($day);
        $this->salts = [];
        $this->saltFor($day);

        return $deleted;
    }

    /**
     * Delete the salts of the days before the given one without making a new one. Returns how many.
     */
    public function forgetBefore(CarbonInterface $day): int
    {
        $this->salts = array_filter($this->salts, fn (string $key): bool => $key >= $day->toDateString(), ARRAY_FILTER_USE_KEY);

        return $this->repository->deleteBefore($day);
    }

    public function siteId(): string
    {
        $configured = config('services.analytics.site_id');

        return is_string($configured) && $configured !== '' ? $configured : (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
