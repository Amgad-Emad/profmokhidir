<?php

/*
 | Setup for tests/qa/appearance.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/appearance-setup.php';"
 |
 | QA_APPLY=<preset slug> makes that suggested theme live and prints it. Without it: makes sure a QA
 | admin (no two-factor) and an approved QA member exist with the QA password, installs the presets,
 | creates a QA custom theme ("QA failing pair", a draft whose light labels fail the contrast check)
 | and prints one JSON line. QA_TEARDOWN=1 restores Evergreen & Ivory and removes the accounts and
 | the QA theme.
 */

use App\Constants\ThemePresets;
use App\Models\ColourTheme;
use App\Models\User;
use App\Services\ColourThemeService;
use Illuminate\Support\Facades\Hash;

$password = 'qa-appearance-2026';
$adminEmail = 'qa.appearance.admin@example.com';
$memberEmail = 'qa.appearance.member@example.com';
$themes = app(ColourThemeService::class);

if (($slug = getenv('QA_APPLY')) !== false && $slug !== '') {
    $themes->installPresets();
    $theme = ColourTheme::query()->where('slug', $slug)->firstOrFail();
    $themes->publish($theme, null);
    echo json_encode(['applied' => $slug]), PHP_EOL;

    return;
}

if (getenv('QA_TEARDOWN') === '1') {
    $themes->restoreBrand(null);
    ColourTheme::query()->where('name', 'like', 'QA %')->get()->each(fn (ColourTheme $theme) => $themes->delete($theme, null));
    User::query()->whereIn('email', [$adminEmail, $memberEmail])->get()->each->delete();
    $themes->endPreview();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$themes->installPresets();

$fresh = ['password' => Hash::make($password), 'theme' => null, 'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null, 'two_factor_reminder_dismissed_at' => now()];
$admin = User::query()->where('email', $adminEmail)->first() ?? User::factory()->admin()->create(['email' => $adminEmail, 'name' => 'Ada Appearance']);
$admin->forceFill([...$fresh, 'email_verified_at' => $admin->email_verified_at ?? now()])->save();
$member = User::query()->where('email', $memberEmail)->first() ?? User::factory()->member()->create(['email' => $memberEmail, 'name' => 'Mo Member']);
$member->forceFill([...$fresh, 'email_verified_at' => $member->email_verified_at ?? now()])->save();

$failing = ColourTheme::query()->where('name', 'QA failing pair')->first();
if ($failing === null) {
    $failing = $themes->duplicate($themes->find(ColourTheme::query()->where('slug', ThemePresets::OXFORD)->value('id')), $admin);
    $themes->rename($failing, 'QA failing pair', $admin);
}
$themes->saveDraft($failing, [...(array) $failing->light, 'accent-text' => '#c9ad7a', 'ink-quiet' => '#b9b2a2'], (array) $failing->dark, $admin, acceptFailures: true);

echo json_encode(['admin' => $adminEmail, 'member' => $memberEmail, 'password' => $password, 'failing' => $failing->id, 'presets' => ThemePresets::slugs()]), PHP_EOL;
