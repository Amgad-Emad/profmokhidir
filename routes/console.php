<?php

use App\Console\Commands\PruneChatUploads;
use App\Console\Commands\UpdateGeoIpDatabase;
use App\Jobs\RollUpAnalytics;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Chat attachments uploaded but never sent are removed after a day.
Schedule::command(PruneChatUploads::class)->hourly()->withoutOverlapping();

// Analytics: roll up yesterday's page views and apply the retention rules every night,
// and refresh the GeoLite2 country database once a week.
Schedule::job(new RollUpAnalytics)->dailyAt('00:30')->withoutOverlapping();
Schedule::command(UpdateGeoIpDatabase::class)->weekly()->wednesdays()->at('03:15')->withoutOverlapping();
