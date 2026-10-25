<?php

/*
 | Setup for tools/showcase/capture.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tools/showcase/setup.php';"
 |
 | Gives the seeded admin, editor and member accounts a one-run password (their
 | original password hashes are kept in storage/qa/showcase/state.json and put back
 | by the teardown), makes a rejected and a suspended applicant for the persona
 | pages, writes the sample files the journeys upload, and prints one JSON line.
 |
 | With SHOWCASE_TEARDOWN=1 it removes everything the capture run created (showcase
 | users, the showcase tribute, chat messages and reports made during the run, the
 | showcase publication, the admin's two-factor set-up, the contact phone change)
 | and restores the original passwords.
 |
 | Chat messages left by earlier manual testing (anything not seeded as
 | "[Placeholder]") are moved to a holding channel for the run so they never appear
 | in a client screenshot, and moved back by the teardown.
 */

use App\Constants\SettingKeys;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use App\Services\SettingService;
use App\Support\ChatSampleFiles;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$dir = storage_path('qa/showcase');
$statePath = $dir.'/state.json';
@mkdir($dir.'/samples', 0775, true);

$seeded = ['admin@example.com', 'editor@example.com', 'member1@example.com', 'member2@example.com'];
$publicationTitle = 'Soil moisture and sorghum yields in the Gezira Scheme';
$tributeEmail = 'showcase.tribute@example.com';
$holdSlug = 'showcase-hold';

if (getenv('SHOWCASE_TEARDOWN') === '1') {
    $state = json_decode((string) @file_get_contents($statePath), true) ?: [];
    $since = $state['startedAt'] ?? now()->toDateTimeString();

    ChatReport::query()->where('created_at', '>=', $since)->delete();
    ChatMessage::withTrashed()->where('created_at', '>=', $since)->orWhereIn('id', $state['insertedMessages'] ?? [])->get()->each->forceDelete();
    User::query()->where('email', 'like', 'showcase.%@example.com')->get()->each->delete();
    Tribute::query()->where('email', $tributeEmail)->get()->each->delete();
    Publication::withTrashed()->where('title', $publicationTitle)->get()->each->forceDelete();

    foreach ($state['passwords'] ?? [] as $email => $hash) {
        User::query()->where('email', $email)->update(['password' => $hash]);
    }
    foreach ($state['themes'] ?? [] as $email => $theme) {
        DB::table('users')->where('email', $email)->update(['theme' => $theme]);
    }

    User::query()->where('email', 'admin@example.com')->update([
        'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
    ]);

    $hold = DB::table('chat_channels')->where('slug', $holdSlug)->value('id');
    if ($hold !== null) {
        DB::table('chat_messages')->where('channel_id', $hold)->update(['channel_id' => $state['channelId']]);
        DB::table('chat_channels')->where('id', $hold)->delete();
    }
    if (isset($state['channelId'])) {
        $last = DB::table('chat_messages')->where('channel_id', $state['channelId'])->max('id');
        DB::table('chat_channels')->where('id', $state['channelId'])->update(['last_message_id' => $last]);
    }

    // The run's page edits and actions are not part of the site's history.
    DB::table('revisions')->where('created_at', '>=', $since)->delete();
    DB::table('audit_logs')->where('created_at', '>=', $since)->delete();

    if (array_key_exists('contactPhone', $state)) {
        app(SettingService::class)->update([SettingKeys::CONTACT_PHONE => $state['contactPhone']]);
    }

    Artisan::call('cache:clear');
    @unlink($statePath);
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$password = Str::password(20, symbols: false);
$channel = DB::table('chat_channels')->where('slug', '!=', $holdSlug)->orderBy('id')->first();
$state = [
    'channelId' => $channel->id,
    'lastMessageId' => $channel->last_message_id,
    'startedAt' => now()->toDateTimeString(),
    'passwords' => User::query()->whereIn('email', $seeded)->pluck('password', 'email')->all(),
    'themes' => DB::table('users')->whereIn('email', $seeded)->pluck('theme', 'email')->all(),
    'contactPhone' => app(SettingService::class)->get(SettingKeys::CONTACT_PHONE),
];

if (is_file($statePath)) {
    // A run that never reached its teardown: keep the hashes it saved, they are the originals.
    $previous = json_decode((string) file_get_contents($statePath), true) ?: [];
    $state['passwords'] = $previous['passwords'] ?? $state['passwords'];
    $state['themes'] = $previous['themes'] ?? $state['themes'];
    $state['contactPhone'] = array_key_exists('contactPhone', $previous) ? $previous['contactPhone'] : $state['contactPhone'];
    $state['startedAt'] = $previous['startedAt'] ?? $state['startedAt'];
    $state['lastMessageId'] = array_key_exists('lastMessageId', $previous) ? $previous['lastMessageId'] : $state['lastMessageId'];
}

file_put_contents($statePath, json_encode($state, JSON_PRETTY_PRINT));

User::query()->whereIn('email', $seeded)->update(['password' => Hash::make($password)]);
// Screens are captured in the light theme; the report adds its dark examples on purpose.
DB::table('users')->whereIn('email', $seeded)->update(['theme' => 'light']);
User::query()->where('email', 'admin@example.com')->update([
    'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
    'two_factor_reminder_dismissed_at' => null,
]);

User::query()->where('email', 'like', 'showcase.%@example.com')->get()->each->delete();
Tribute::query()->where('email', $tributeEmail)->get()->each->delete();
Publication::withTrashed()->where('title', $publicationTitle)->get()->each->forceDelete();

$admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
User::factory()->rejectedApplicant()->reviewedBy($admin)->create([
    'name' => 'Omar Siddig', 'email' => 'showcase.rejected@example.com', 'password' => Hash::make($password),
]);
User::factory()->suspendedMember()->reviewedBy($admin)->create([
    'name' => 'Huda Babiker', 'email' => 'showcase.suspended@example.com', 'password' => Hash::make($password),
]);

$hold = DB::table('chat_channels')->where('slug', $holdSlug)->value('id')
    ?? DB::table('chat_channels')->insertGetId(['name' => 'Showcase hold', 'slug' => $holdSlug, 'created_at' => now(), 'updated_at' => now()]);
DB::table('chat_messages')->where('channel_id', $channel->id)->where('created_at', '<', $state['startedAt'])
    ->where(fn ($query) => $query->whereNull('body')->orWhere('body', 'not like', '[Placeholder]%')
        ->orWhereIn('id', DB::table('media')->where('model_type', ChatMessage::class)->select('model_id')))
    ->update(['channel_id' => $hold]);

// Two messages from the second member for the journey to answer and to report.
$yusuf = User::query()->where('email', 'member2@example.com')->value('id');
$inserted = [];
foreach ([
    [24, 'Does anyone still have the rainfall series for the 1988 season? I would like to compare it with the trial plot yields.'],
    [6, 'Posting the full yield tables from a colleague\'s unpublished thesis here, before he has agreed to share them.'],
] as [$minutes, $body]) {
    $inserted[] = DB::table('chat_messages')->insertGetId([
        'channel_id' => $channel->id, 'user_id' => $yusuf, 'client_uuid' => (string) Str::uuid(), 'body' => $body, 'version' => 1,
        'created_at' => now()->subMinutes($minutes), 'updated_at' => now()->subMinutes($minutes),
    ]);
}
$state['insertedMessages'] = array_merge(json_decode((string) file_get_contents($statePath), true)['insertedMessages'] ?? [], $inserted);
file_put_contents($statePath, json_encode($state, JSON_PRETTY_PRINT));
DB::table('chat_channels')->where('id', $channel->id)->update(['last_message_id' => max($inserted)]);

app(SettingService::class)->update([SettingKeys::CHAT_UPLOADS_ENABLED => true]);

$photo = collect(glob(storage_path('app/public/*/placeholder-*.png')))->first();
copy($photo ?: ChatSampleFiles::png(1200, 800), $dir.'/samples/field-visit.png');
copy(ChatSampleFiles::pdf(), $dir.'/samples/irrigation-notes.pdf');

echo json_encode([
    'password' => $password,
    'publicationTitle' => $publicationTitle,
    'tributeEmail' => $tributeEmail,
    'samples' => ['image' => $dir.'/samples/field-visit.png', 'pdf' => $dir.'/samples/irrigation-notes.pdf'],
    'log' => storage_path('logs/laravel.log'),
]), PHP_EOL;
