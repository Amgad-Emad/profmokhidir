<?php

/*
 | Setup for tests/qa/chat-fixes.mjs, run through tinker against the dev database:
 |
 |   php artisan tinker --execute="require 'tests/qa/chat-fixes-setup.php';"
 |
 | Makes sure the QA accounts exist with the QA password (two approved members, an
 | administrator with fresh recovery codes, a pending applicant and a throwaway member
 | the administrator may suspend), switches chat uploads on, writes one sample file of
 | every accepted type to storage/qa/chat-fixes/samples, and prints one JSON line.
 | With QA_TEARDOWN=1 it removes the applicant and the throwaway member instead.
 */

use App\Constants\CommunityStatus;
use App\Constants\SettingKeys;
use App\Models\User;
use App\Services\SettingService;
use App\Support\ChatSampleFiles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\RecoveryCode;

$password = 'qa-chat-2026';

if (getenv('QA_TEARDOWN') === '1') {
    User::query()->whereIn('email', ['qa.pending@example.com', 'qa.throwaway@example.com'])->get()->each->delete();
    echo json_encode(['teardown' => true]), PHP_EOL;

    return;
}

$account = function (string $email, string $name, callable $state) use ($password): User {
    $user = User::query()->where('email', $email)->first() ?? $state(User::factory())->create(['email' => $email, 'name' => $name]);
    $user->forceFill(['password' => Hash::make($password), 'email_verified_at' => $user->email_verified_at ?? now()])->save();

    return $user;
};

$account('qa.amina@example.com', 'Amina Hassan', fn ($factory) => $factory->member());
$account('qa.yusuf@example.com', 'Yusuf Abdallah', fn ($factory) => $factory->member());
$admin = $account('qa.admin@example.com', 'QA Administrator', fn ($factory) => $factory->admin());
$account('qa.pending@example.com', 'Pending Applicant', fn ($factory) => $factory->applicant());
$throwaway = $account('qa.throwaway@example.com', 'Throwaway Member', fn ($factory) => $factory->member());

if ($throwaway->community_status !== CommunityStatus::Approved) {
    $throwaway->delete();
    $account('qa.throwaway@example.com', 'Throwaway Member', fn ($factory) => $factory->member());
}

$codes = Collection::times(8, fn () => RecoveryCode::generate())->all();
$admin->forceFill(['two_factor_recovery_codes' => encrypt(json_encode($codes))])->save();

app(SettingService::class)->update([SettingKeys::CHAT_UPLOADS_ENABLED => true]);

$dir = storage_path('qa/chat-fixes/samples');
@mkdir($dir, 0775, true);
$copy = function (string $from, string $name) use ($dir): string {
    copy($from, $dir.'/'.$name);

    return $dir.'/'.$name;
};

$jpeg = $copy(ChatSampleFiles::jpegWithLocation(1200, 800, 1), 'field-trip-with-gps.jpg');
$webp = $dir.'/herbarium-sheet.webp';
$image = imagecreatetruecolor(900, 1200);
imagefilledrectangle($image, 0, 0, 900, 1200, imagecolorallocate($image, 0xE7, 0xE5, 0xDC));
imagewebp($image, $webp, 85);
$heic = $dir.'/iphone-photo.heic';
@unlink($heic);
exec('/usr/bin/sips -s format heic '.escapeshellarg($jpeg).' --out '.escapeshellarg($heic).' 2>/dev/null');
$bigPdf = $copy(ChatSampleFiles::pdf(), 'agr-301-reader.pdf');
file_put_contents($bigPdf, str_repeat("% padding so the progress bar has something to show\n", 60000), FILE_APPEND);

echo json_encode([
    'adminRecovery' => $codes[0],
    'spareRecovery' => array_slice($codes, 1, 3),
    'samples' => [
        'jpg' => $jpeg,
        'png' => $copy(ChatSampleFiles::png(1000, 700), 'soil-profile.png'),
        'webp' => $webp,
        'heic' => is_file($heic) ? $heic : null,
        'pdf' => $bigPdf,
        'docx' => $copy(ChatSampleFiles::ooxml('docx'), 'thesis-chapter.docx'),
        'xlsx' => $copy(ChatSampleFiles::ooxml('xlsx'), 'yield-trials.xlsx'),
        'pptx' => $copy(ChatSampleFiles::ooxml('pptx'), 'lecture-12.pptx'),
        'csv' => $copy(ChatSampleFiles::csv(), 'rainfall.csv'),
        'zip' => $copy(ChatSampleFiles::zip(), 'problem-sets.zip'),
        'exe' => $copy(ChatSampleFiles::executable(), 'not-really.pdf'),
    ],
]), PHP_EOL;
