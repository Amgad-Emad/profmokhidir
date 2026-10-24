<?php

namespace Tests\Unit\Services;

use App\Constants\Analytics;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeoLocationServiceTest extends TestCase
{
    #[Test]
    public function the_cloudflare_header_is_used_only_when_the_site_is_behind_cloudflare(): void
    {
        $request = Request::create('/', server: ['HTTP_CF_IPCOUNTRY' => 'sd', 'REMOTE_ADDR' => '203.0.113.9']);

        config(['services.analytics.behind_cloudflare' => true]);
        $this->assertSame('SD', (new GeoLocationService)->country($request));
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, (new GeoLocationService)->country(Request::create('/', server: ['HTTP_CF_IPCOUNTRY' => 'XX'])));

        config(['services.analytics.behind_cloudflare' => false]);
        $service = new class extends GeoLocationService
        {
            public function databasePath(): string
            {
                return storage_path('framework/testing/no-such-geoip.mmdb');
            }
        };

        $this->assertFalse($service->hasDatabase());
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, $service->country($request));
    }

    #[Test]
    public function private_or_invalid_addresses_are_unknown(): void
    {
        $service = new GeoLocationService;

        $this->assertSame(Analytics::UNKNOWN_COUNTRY, $service->lookup('127.0.0.1'));
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, $service->lookup('10.0.0.1'));
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, $service->lookup('not an ip'));
    }
}
