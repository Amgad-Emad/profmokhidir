<?php

/*
 | Setup for tests/qa/danger-forms.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/danger-forms-setup.php';"
 |
 | Makes sure an admin (no two-factor, the dashboard card dismissed) exists with the QA
 | password and no saved theme, one live and one trashed QA publication for the trash and
 | dialog screenshots, and prints one JSON line. With QA_TEARDOWN=1 it removes the account
 | and deletes both publications for good.
 */

use App\Models\Publication;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$password = 'qa-danger-forms-2026';
$email = 'qa.danger.admin@example.com';
$live = 'QA danger sample: Soil moisture under gum arabic';
$trashed = 'QA danger sample: Rainfall records of the Butana';

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->where('email', $email)->get()->each->delete();
    Publication::withTrashed()->whereIn('title', [$live, $trashed])->get()->each->forceDelete();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$admin = User::query()->where('email', $email)->first() ?? User::factory()->admin()->create(['email' => $email, 'name' => 'Dana Admin']);
$admin->forceFill([
    'password' => Hash::make($password),
    'email_verified_at' => $admin->email_verified_at ?? now(),
    'theme' => null,
    'two_factor_secret' => null,
    'two_factor_recovery_codes' => null,
    'two_factor_confirmed_at' => null,
    'two_factor_reminder_dismissed_at' => now(),
])->save();

$livePublication = Publication::withTrashed()->where('title', $live)->first() ?? Publication::factory()->draft()->create(['title' => $live, 'sort_order' => 0]);
$livePublication->restore();

$trashedPublication = Publication::withTrashed()->where('title', $trashed)->first() ?? Publication::factory()->draft()->create(['title' => $trashed]);
if (! $trashedPublication->trashed()) {
    $trashedPublication->delete();
}

echo json_encode(['email' => $email, 'password' => $password, 'live' => $live, 'liveId' => $livePublication->getKey(), 'trashed' => $trashed, 'trashedId' => $trashedPublication->getKey()]), PHP_EOL;
