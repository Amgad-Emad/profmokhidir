<?php

/*
 | Setup for tests/qa/focus.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/focus-setup.php';"
 |
 | Makes sure an editor and an approved member exist with the QA password and no saved
 | theme (so the cookie decides), and prints one JSON line. With QA_TEARDOWN=1 it
 | removes both accounts instead.
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$password = 'qa-focus-2026';
$emails = ['editor' => 'qa.focus.editor@example.com', 'member' => 'qa.focus.member@example.com'];

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->whereIn('email', array_values($emails))->get()->each->delete();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$account = function (string $email, string $name, callable $state) use ($password): User {
    $user = User::query()->where('email', $email)->first() ?? $state(User::factory())->create(['email' => $email, 'name' => $name]);
    $user->forceFill(['password' => Hash::make($password), 'email_verified_at' => $user->email_verified_at ?? now(), 'theme' => null])->save();

    return $user;
};

$account($emails['editor'], 'Focus Editor', fn ($factory) => $factory->editor());
$account($emails['member'], 'Focus Member', fn ($factory) => $factory->member());

echo json_encode(['password' => $password] + $emails), PHP_EOL;
