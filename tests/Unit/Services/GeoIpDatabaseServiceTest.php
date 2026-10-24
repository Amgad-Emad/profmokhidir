<?php

namespace Tests\Unit\Services;

use App\Constants\Analytics;
use App\Services\GeoIpDatabaseService;
use App\Services\GeoLocationService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class GeoIpDatabaseServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/geoip-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    private function service(): GeoIpDatabaseService
    {
        $target = $this->directory.'/installed/GeoLite2-Country.mmdb';

        return new GeoIpDatabaseService(new class($target) extends GeoLocationService
        {
            public function __construct(private readonly string $target) {}

            public function databasePath(): string
            {
                return $this->target;
            }
        });
    }

    /**
     * A tar.gz like MaxMind's: one dated folder holding the .mmdb.
     */
    private function archive(): string
    {
        $tar = $this->directory.'/fixture.tar';
        $phar = new PharData($tar);
        $phar->addFromString('GeoLite2-Country_20261021/GeoLite2-Country.mmdb', 'fake database');
        $phar->addFromString('GeoLite2-Country_20261021/LICENSE.txt', 'licence');
        $phar->compress(\Phar::GZ);

        return (string) file_get_contents($tar.'.gz');
    }

    #[Test]
    public function without_a_licence_key_nothing_is_downloaded(): void
    {
        config(['services.maxmind.license_key' => '']);
        Http::fake();

        $this->assertNull($this->service()->licenseKey());
        $this->expectException(RuntimeException::class);

        try {
            $this->service()->update();
        } finally {
            Http::assertNothingSent();
        }
    }

    #[Test]
    public function the_database_is_downloaded_with_the_key_unpacked_and_installed(): void
    {
        config(['services.maxmind.license_key' => 'test-key']);
        Http::fake([Analytics::GEOIP_DOWNLOAD_URL.'*' => Http::response($this->archive())]);

        $path = $this->service()->update();

        $this->assertSame('fake database', file_get_contents($path));
        $this->assertSame([], glob($this->directory.'/installed/download-*') ?: []);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'edition_id=GeoLite2-Country') && str_contains($request->url(), 'license_key=test-key'));
    }
}
