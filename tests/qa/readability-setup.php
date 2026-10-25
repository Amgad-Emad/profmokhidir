<?php

/*
 | Setup for tests/qa/readability.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/readability-setup.php';"
 |
 | Makes sure an admin (no two-factor, the dashboard card dismissed) and an approved member
 | exist with the QA password and no saved theme, and prints one JSON line. With
 | QA_TEARDOWN=1 it removes both accounts.
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$password = 'qa-readability-2026';
$accounts = [
    'admin' => ['email' => 'qa.readability.admin@example.com', 'name' => 'Rania Admin', 'state' => 'admin'],
    'member' => ['email' => 'qa.readability.member@example.com', 'name' => 'Samir Member', 'state' => 'member'],
];

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->whereIn('email', array_column($accounts, 'email'))->get()->each->delete();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$out = ['password' => $password];

foreach ($accounts as $role => $account) {
    $user = User::query()->where('email', $account['email'])->first()
        ?? User::factory()->{$account['state']}()->create(['email' => $account['email'], 'name' => $account['name']]);
    $user->forceFill([
        'password' => Hash::make($password),
        'email_verified_at' => $user->email_verified_at ?? now(),
        'theme' => null,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_reminder_dismissed_at' => now(),
    ])->save();
    $out[$role] = $account['email'];
}

echo json_encode($out), PHP_EOL;
