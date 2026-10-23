<?php

use App\Console\Commands\PruneChatUploads;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Chat attachments uploaded but never sent are removed after a day.
Schedule::command(PruneChatUploads::class)->hourly()->withoutOverlapping();
