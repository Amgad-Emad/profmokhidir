<?php

/*
 | Setup for tests/qa/admin-filters.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/admin-filters-setup.php';"
 |
 | Makes sure two administrators exist with the QA password, no two-factor, the dashboard card
 | dismissed and no saved theme, one working in English and one in Arabic, and prints one JSON
 | line. Their remembered list views are cleared so every run starts from the default view. With
 | QA_TEARDOWN=1 it removes both accounts.
 */

use App\Models\ListPreference;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$password = 'qa-admin-filters-2026';
$accounts = [
    'en' => ['email' => 'qa.filters.en@example.com', 'name' => 'Layla Editor'],
    'ar' => ['email' => 'qa.filters.ar@example.com', 'name' => 'Hassan Editor'],
];

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->whereIn('email', array_column($accounts, 'email'))->get()->each->delete();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$out = ['password' => $password];

foreach ($accounts as $locale => $account) {
    $user = User::query()->where('email', $account['email'])->first()
        ?? User::factory()->admin()->create(['email' => $account['email'], 'name' => $account['name']]);
    $user->forceFill([
        'password' => Hash::make($password),
        'email_verified_at' => $user->email_verified_at ?? now(),
        'locale' => $locale,
        'theme' => null,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_reminder_dismissed_at' => now(),
    ])->save();

    if (class_exists(ListPreference::class)) {
        ListPreference::query()->where('user_id', $user->getKey())->delete();
    }

    $out[$locale] = $account['email'];
}

echo json_encode($out), PHP_EOL;
