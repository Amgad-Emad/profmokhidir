<?php

/*
 | Setup for tests/qa/analytics.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/analytics-setup.php';"
 |
 | Makes sure an editor (QA password, no saved theme) exists, seeds the demo analytics when
 | there are no rollups yet, switches analytics on, and prints one JSON line with the number
 | of raw page views so far. With QA_TEARDOWN=1 it removes the account, deletes the page views
 | recorded since QA_SINCE (the live check's own beacons) and switches analytics off again.
 */

use App\Constants\SettingKeys;
use App\Models\User;
use App\Repositories\Contracts\AnalyticsEventRepositoryInterface;
use App\Repositories\Contracts\AnalyticsRollupRepositoryInterface;
use App\Services\SettingService;
use Database\Seeders\AnalyticsDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$password = 'qa-analytics-2026';
$email = 'qa.analytics.editor@example.com';
$settings = app(SettingService::class);

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->where('email', $email)->get()->each->delete();

    $since = getenv('QA_SINCE');
    $removed = $since ? DB::table('analytics_events')->where('created_at', '>=', $since)->delete() : 0;
    $settings->update([SettingKeys::ANALYTICS_ENABLED => false]);
    echo json_encode(['teardown' => true, 'removed' => $removed]), PHP_EOL;

    return;
}

if (getenv('QA_COUNT') === '1') {
    $rows = DB::table('analytics_events')->where('created_at', '>=', (string) getenv('QA_SINCE'))->orderBy('id')->get(['path', 'title', 'section', 'country', 'device', 'browser', 'referrer_domain', 'engaged_seconds', 'scroll_depth', 'visitor_hash']);
    echo json_encode(['events' => app(AnalyticsEventRepositoryInterface::class)->count(), 'rows' => $rows]), PHP_EOL;

    return;
}

$editor = User::query()->where('email', $email)->first() ?? User::factory()->editor()->create(['email' => $email, 'name' => 'Amira Editor']);
$editor->forceFill(['password' => Hash::make($password), 'email_verified_at' => $editor->email_verified_at ?? now(), 'theme' => null])->save();

if (! app(AnalyticsRollupRepositoryInterface::class)->hasAny()) {
    app()->detectEnvironment(fn (): string => 'local');
    (new AnalyticsDemoSeeder)->setContainer(app())->__invoke();
}

$settings->update([SettingKeys::ANALYTICS_ENABLED => true]);

echo json_encode(['email' => $email, 'password' => $password, 'events' => app(AnalyticsEventRepositoryInterface::class)->count(), 'now' => now()->toDateTimeString()]), PHP_EOL;
