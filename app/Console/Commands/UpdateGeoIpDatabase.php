<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GeoIpDatabaseService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Download the MaxMind GeoLite2 Country database the analytics uses to tell a
 * visitor's country (when the site is not behind Cloudflare). Scheduled weekly
 * in routes/console.php; does nothing without MAXMIND_LICENSE_KEY.
 */
class UpdateGeoIpDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:update-geoip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download the MaxMind GeoLite2 Country database for the analytics country lookup';

    /**
     * Execute the console command.
     */
    public function handle(GeoIpDatabaseService $database): int
    {
        if ($database->licenseKey() === null) {
            $this->warn('MAXMIND_LICENSE_KEY is not set; countries are recorded as Unknown unless Cloudflare sends them.');

            return self::SUCCESS;
        }

        try {
            $path = $database->update();
        } catch (Throwable $exception) {
            $this->error('The GeoLite2 database could not be updated: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('GeoLite2 Country database installed at '.$path.'.');

        return self::SUCCESS;
    }
}
