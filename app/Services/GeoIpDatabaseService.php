<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Analytics;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use RuntimeException;

/**
 * Downloads the free MaxMind GeoLite2 Country database (needs
 * MAXMIND_LICENSE_KEY) into storage/app/geoip, replacing the old file only
 * once the new one has been unpacked. Run weekly by analytics:update-geoip.
 */
class GeoIpDatabaseService
{
    public const int TIMEOUT_SECONDS = 120;

    public function __construct(private readonly GeoLocationService $geo) {}

    public function licenseKey(): ?string
    {
        $key = config('services.maxmind.license_key');

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    /**
     * Fetch and install the database. Returns the installed file's path.
     */
    public function update(): string
    {
        $key = $this->licenseKey() ?? throw new RuntimeException('Set MAXMIND_LICENSE_KEY in .env to download the GeoLite2 Country database.');
        $target = $this->geo->databasePath();
        $work = storage_path('app/geoip/download-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($work);

        try {
            $archive = $work.'/'.Analytics::GEOIP_EDITION.'.tar.gz';

            Http::timeout(self::TIMEOUT_SECONDS)
                ->sink($archive)
                ->get(Analytics::GEOIP_DOWNLOAD_URL, ['edition_id' => Analytics::GEOIP_EDITION, 'license_key' => $key, 'suffix' => 'tar.gz'])
                ->throw();

            (new PharData($archive))->extractTo($work, null, true);
            $database = collect(File::allFiles($work))->first(fn (\SplFileInfo $file): bool => $file->getExtension() === 'mmdb');

            if ($database === null) {
                throw new RuntimeException('The download did not contain a GeoLite2 database.');
            }

            File::ensureDirectoryExists(dirname($target));
            File::move($database->getPathname(), $target);

            return $target;
        } finally {
            File::deleteDirectory($work);
        }
    }
}
