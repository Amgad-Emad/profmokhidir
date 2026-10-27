<?php

/*
 | Setup for tests/qa/arabic.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/arabic-setup.php';"
 |
 | Makes sure an admin (no two-factor, the dashboard card dismissed) and an approved member exist
 | with the QA password, Arabic as their language and no saved theme, and prints one JSON line with
 | them and the id of a commentary to open in the admin. With QA_TEARDOWN=1 it removes both accounts.
 */

use App\Constants\Locales;
use App\Models\Commentary;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$password = 'qa-arabic-2026';
$accounts = [
    'admin' => ['email' => 'qa.arabic.admin@example.com', 'name' => 'رانيا المشرفة', 'state' => 'admin'],
    'member' => ['email' => 'qa.arabic.member@example.com', 'name' => 'سامر العضو', 'state' => 'member'],
];

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->whereIn('email', array_column($accounts, 'email'))->get()->each->delete();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$out = ['password' => $password, 'commentary' => Commentary::query()->orderBy('id')->value('id')];

foreach ($accounts as $role => $account) {
    $user = User::query()->where('email', $account['email'])->first()
        ?? User::factory()->{$account['state']}()->create(['email' => $account['email'], 'name' => $account['name']]);
    $user->forceFill([
        'password' => Hash::make($password),
        'email_verified_at' => $user->email_verified_at ?? now(),
        'theme' => null,
        'locale' => Locales::AR,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_reminder_dismissed_at' => now(),
    ])->save();
    $out[$role] = $account['email'];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE), PHP_EOL;
