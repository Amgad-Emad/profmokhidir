<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Analytics;
use App\Support\Countries;
use GeoIp2\Database\Reader;
use Illuminate\Http\Request;
use Throwable;

/**
 * The visitor's country for analytics, looked up in memory: Cloudflare's
 * CF-IPCountry header when the site is behind Cloudflare
 * (ANALYTICS_BEHIND_CLOUDFLARE=true), otherwise the local MaxMind GeoLite2
 * Country database, otherwise "ZZ" (Unknown). The IP address is never kept.
 */
class GeoLocationService
{
    private ?Reader $reader = null;

    private bool $readerChecked = false;

    public function country(Request $request): string
    {
        if ((bool) config('services.analytics.behind_cloudflare') && $request->headers->has(Analytics::CLOUDFLARE_COUNTRY_HEADER)) {
            return Countries::normalise($request->header(Analytics::CLOUDFLARE_COUNTRY_HEADER));
        }

        return $this->lookup((string) $request->ip());
    }

    public function lookup(string $ip): string
    {
        $reader = $this->reader();

        if ($reader === null || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return Analytics::UNKNOWN_COUNTRY;
        }

        try {
            return Countries::normalise($reader->country($ip)->country->isoCode);
        } catch (Throwable) {
            return Analytics::UNKNOWN_COUNTRY;
        }
    }

    public function databasePath(): string
    {
        return storage_path('app/'.Analytics::GEOIP_DATABASE);
    }

    public function hasDatabase(): bool
    {
        return is_file($this->databasePath());
    }

    private function reader(): ?Reader
    {
        if (! $this->readerChecked) {
            $this->readerChecked = true;

            try {
                $this->reader = $this->hasDatabase() ? new Reader($this->databasePath()) : null;
            } catch (Throwable) {
                $this->reader = null;
            }
        }

        return $this->reader;
    }
}
