<?php

/*
 | Setup for tests/qa/buttons-auth.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/buttons-auth-setup.php';"
 |
 | Makes sure an approved member, an editor and an admin (without two-factor and with
 | the dashboard card not dismissed) exist with the QA password and no saved theme, and
 | prints one JSON line. With QA_TEARDOWN=1 it removes the three accounts instead.
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$password = 'qa-buttons-2026';
$emails = ['member' => 'qa.buttons.member@example.com', 'editor' => 'qa.buttons.editor@example.com', 'admin' => 'qa.buttons.admin@example.com'];

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->whereIn('email', array_values($emails))->get()->each->delete();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$account = function (string $email, string $name, callable $state) use ($password): User {
    $user = User::query()->where('email', $email)->first() ?? $state(User::factory())->create(['email' => $email, 'name' => $name]);
    $user->forceFill([
        'password' => Hash::make($password),
        'email_verified_at' => $user->email_verified_at ?? now(),
        'theme' => null,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_reminder_dismissed_at' => null,
    ])->save();

    return $user;
};

$account($emails['member'], 'Amina Osman', fn ($factory) => $factory->member());
$account($emails['editor'], 'Elias Editor', fn ($factory) => $factory->editor());
$account($emails['admin'], 'Adela Admin', fn ($factory) => $factory->admin());

echo json_encode(['password' => $password] + $emails), PHP_EOL;
