<?php

namespace Tests\Unit\Services;

use App\Constants\ChatAttachments;
use App\Constants\ChatSettings;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\RateLimits;
use App\Constants\SettingKeys;
use App\Exceptions\ChatRateLimitException;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatUpload;
use App\Models\User;
use App\Services\ChatAttachmentService;
use App\Services\ChatService;
use App\Services\SettingService;
use App\Support\ChatSampleFiles;
use App\Support\HeicConverter;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ChatAttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatAttachmentService $service;

    private ChatChannel $room;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PRIVATE);
        $this->service = $this->app->make(ChatAttachmentService::class);
        $this->room = ChatChannel::factory()->room()->create();
        $this->member = User::factory()->member()->create();
    }

    private function file(string $path, string $name): UploadedFile
    {
        return new UploadedFile($path, $name, null, null, true);
    }

    private function send(User $author, array $attachments, string $body = 'With files', ?string $uuid = null): ChatMessage
    {
        return $this->app->make(ChatService::class)->send($this->room, $author, $body, $uuid ?? (string) Str::uuid(), null, $attachments);
    }

    #[Test]
    public function staging_a_photo_strips_its_location_and_keeps_it_on_the_private_disk(): void
    {
        $upload = $this->service->stage($this->member, $this->file(ChatSampleFiles::jpegWithLocation(), 'Field trip.jpg'));

        $this->assertSame('Field trip.jpg', $upload->original_name);
        $this->assertTrue($upload->isImage());
        $media = $upload->getFirstMedia(MediaCollections::CHAT_PENDING);
        $this->assertSame(Disks::PRIVATE, $media->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($media->getPathRelativeToRoot());

        $exif = @exif_read_data(Storage::disk(Disks::PRIVATE)->path($media->getPathRelativeToRoot())) ?: [];
        $this->assertArrayNotHasKey('GPSLatitude', $exif);
        $this->assertSame(['id' => $upload->uuid, 'kind' => 'image', 'name' => 'Field trip.jpg', 'size' => $upload->size], $this->service->presentUpload($upload));
    }

    #[Test]
    public function a_heic_photo_is_converted_to_jpeg_when_staged(): void
    {
        $this->app->instance(HeicConverter::class, new class extends HeicConverter
        {
            public function toJpeg(string $path): string
            {
                $target = preg_replace('/\.heic$/i', '.jpg', $path);
                copy(ChatSampleFiles::jpegWithLocation(40, 30, 1), $target);

                return $target;
            }
        });

        $upload = $this->app->make(ChatAttachmentService::class)->stage($this->member, $this->file(ChatSampleFiles::jpegWithLocation(), 'IMG_0001.heic'));

        $this->assertSame('IMG_0001.jpg', $upload->original_name);
        $this->assertSame('jpg', $upload->extension);
        $this->assertSame('image/jpeg', $upload->mime_type);
    }

    #[Test]
    public function staging_needs_chat_access_and_uploads_switched_on(): void
    {
        try {
            $this->service->stage(User::factory()->applicant()->create(), $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));
            $this->fail('Applicants must not upload');
        } catch (AuthorizationException) {
        }

        $this->app->make(SettingService::class)->update([SettingKeys::CHAT_UPLOADS_ENABLED => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));
    }

    #[Test]
    public function each_staged_file_counts_toward_the_send_rate_limit(): void
    {
        $limiter = $this->app->make(RateLimiter::class);
        $key = RateLimits::chatSendKey($this->member->id);

        $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'one.pdf'));
        $this->assertSame(1, $limiter->attempts($key));

        for ($i = 1; $i < ChatSettings::SEND_LIMIT_PER_MINUTE; $i++) {
            $limiter->hit($key, 60);
        }

        $this->expectException(ChatRateLimitException::class);
        $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'two.pdf'));
    }

    #[Test]
    public function a_member_holds_at_most_five_waiting_files(): void
    {
        ChatUpload::factory()->count(ChatAttachments::MAX_FILES)->for($this->member)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'sixth.pdf'));
    }

    #[Test]
    public function sending_moves_the_files_onto_the_message_with_descriptions_in_order(): void
    {
        $photo = $this->service->stage($this->member, $this->file(ChatSampleFiles::png(), 'scan.png'));
        $notes = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));

        $message = $this->send($this->member, [['id' => $photo->uuid, 'alt' => 'A scanned page'], ['id' => $notes->uuid, 'alt' => '']]);

        $files = $message->fresh()->attachments();
        $this->assertSame(['scan.png', 'notes.pdf'], $files->pluck('file_name')->all());
        $this->assertSame('A scanned page', $files[0]->getCustomProperty(MediaProperties::ALT_TEXT));
        $this->assertSame(Disks::PRIVATE, $files[1]->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($files[1]->getPathRelativeToRoot());
        $this->assertNotNull($photo->fresh()->claimed_at);
        $this->assertSame($message->id, $notes->fresh()->message_id);
    }

    #[Test]
    public function a_retried_send_with_the_same_client_uuid_never_attaches_twice(): void
    {
        $notes = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));
        $uuid = (string) Str::uuid();

        $first = $this->send($this->member, [['id' => $notes->uuid]], uuid: $uuid);
        $again = $this->send($this->member, [['id' => $notes->uuid]], uuid: $uuid);

        $this->assertTrue($first->is($again));
        $this->assertSame(1, ChatMessage::query()->count());
        $this->assertCount(1, $first->fresh()->attachments());
    }

    #[Test]
    public function a_file_can_be_claimed_by_one_message_only(): void
    {
        $notes = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));
        $this->send($this->member, [['id' => $notes->uuid]]);

        $this->expectException(InvalidArgumentException::class);
        $this->send($this->member, [['id' => $notes->uuid]], 'Second message');
    }

    #[Test]
    public function only_your_own_files_can_be_sent(): void
    {
        $photo = $this->service->stage($this->member, $this->file(ChatSampleFiles::png(), 'scan.png'));
        $other = User::factory()->member()->create();

        try {
            $this->send($other, [['id' => $photo->uuid, 'alt' => 'Taken']]);
            $this->fail('Another member must not send my file');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame(0, ChatMessage::query()->count());
        $this->assertNull($photo->fresh()->claimed_at);
    }

    #[Test]
    public function an_image_sent_without_a_description_is_described_as_shared_by_its_member(): void
    {
        $this->member->forceFill(['name' => 'Amina Hassan'])->save();
        $blank = $this->service->stage($this->member, $this->file(ChatSampleFiles::png(), 'scan.png'));
        $missing = $this->service->stage($this->member, $this->file(ChatSampleFiles::png(), 'second.png'));

        $message = $this->send($this->member, [['id' => $blank->uuid, 'alt' => '   '], ['id' => $missing->uuid]]);

        $alts = $message->fresh()->attachments()->map(fn ($media) => $media->getCustomProperty(MediaProperties::ALT_TEXT))->all();
        $this->assertSame(['Image shared by Amina Hassan', 'Image shared by Amina Hassan'], $alts);
    }

    #[Test]
    public function the_alt_text_is_the_description_or_names_who_shared_the_image(): void
    {
        $this->member->forceFill(['name' => 'Yusuf Abdallah'])->save();

        $this->assertSame('A soil profile', $this->service->altText('  A soil profile ', $this->member));
        $this->assertSame('Image shared by Yusuf Abdallah', $this->service->altText('', $this->member));
        $this->assertSame(ChatAttachments::ALT_MAX_LENGTH, mb_strlen($this->service->altText(str_repeat('a', 300), $this->member)));
    }

    #[Test]
    public function a_finished_thumbnail_marks_its_message_changed_for_the_next_poll(): void
    {
        $photo = $this->service->stage($this->member, $this->file(ChatSampleFiles::png(), 'scan.png'));
        $notes = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));
        $message = $this->send($this->member, [['id' => $photo->uuid, 'alt' => 'A scan'], ['id' => $notes->uuid]]);
        [$image, $document] = $message->fresh()->attachments()->all();
        $before = $message->fresh()->updated_at;

        $this->assertFalse($this->service->conversionCompleted($image, ChatAttachments::CONVERSION_MEDIUM));
        $this->assertFalse($this->service->conversionCompleted($document, ChatAttachments::CONVERSION_THUMB));
        $this->assertTrue($this->service->conversionCompleted($image, ChatAttachments::CONVERSION_THUMB));

        $this->assertTrue($message->fresh()->updated_at->greaterThan($before));
        $this->assertTrue($message->fresh()->updated_at->greaterThan($message->created_at));
    }

    #[Test]
    public function a_thumbnail_made_while_the_file_is_still_waiting_marks_nothing(): void
    {
        $photo = $this->service->stage($this->member, $this->file(ChatSampleFiles::png(), 'scan.png'));

        $this->assertFalse($this->service->conversionCompleted($photo->getFirstMedia(MediaCollections::CHAT_PENDING), ChatAttachments::CONVERSION_THUMB));
    }

    #[Test]
    public function a_message_may_be_files_only(): void
    {
        $notes = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));

        $message = $this->send($this->member, [['id' => $notes->uuid]], body: '   ');

        $this->assertSame('', $message->body);
        $this->assertCount(1, $message->fresh()->attachments());
    }

    #[Test]
    public function deleting_a_message_deletes_its_files(): void
    {
        $notes = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));
        $message = $this->send($this->member, [['id' => $notes->uuid]]);
        $path = $message->fresh()->attachments()->first()->getPathRelativeToRoot();

        $this->app->make(ChatService::class)->delete($message->fresh(), $this->member);

        Storage::disk(Disks::PRIVATE)->assertMissing($path);
        $this->assertSame(0, $message->fresh()->media()->count());
    }

    #[Test]
    public function discarding_removes_only_your_own_waiting_file(): void
    {
        $notes = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'notes.pdf'));

        $this->assertFalse($this->service->discard(User::factory()->member()->create(), $notes->uuid));
        $this->assertTrue($this->service->discard($this->member, $notes->uuid));
        $this->assertNull($notes->fresh());
    }

    #[Test]
    public function pruning_removes_unsent_files_older_than_a_day_and_nothing_else(): void
    {
        $old = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'old.pdf'));
        $old->forceFill(['created_at' => now()->subHours(ChatAttachments::TEMPORARY_TTL_HOURS + 1)])->save();
        $fresh = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'fresh.pdf'));
        $sent = $this->service->stage($this->member, $this->file(ChatSampleFiles::pdf(), 'sent.pdf'));
        $this->send($this->member, [['id' => $sent->uuid]]);
        $sent->forceFill(['created_at' => now()->subDays(3)])->save();
        $oldPath = $old->getFirstMedia(MediaCollections::CHAT_PENDING)->getPathRelativeToRoot();

        $this->assertSame(1, $this->service->prune(CarbonImmutable::now()));

        $this->assertNull($old->fresh());
        Storage::disk(Disks::PRIVATE)->assertMissing($oldPath);
        $this->assertNotNull($fresh->fresh());
        $this->assertNotNull($sent->fresh());
    }

    #[Test]
    public function downloads_are_for_members_and_moderators_and_only_for_live_messages(): void
    {
        $photo = $this->service->stage($this->member, $this->file(ChatSampleFiles::png(), 'scan.png'));
        $message = $this->send($this->member, [['id' => $photo->uuid, 'alt' => 'A scan']]);
        $media = $message->fresh()->attachments()->first();

        $file = $this->service->download(User::factory()->member()->create(), $media->id, ChatAttachmentService::VARIANT_ORIGINAL);
        $this->assertSame('scan.png', $file['name']);
        $this->assertFalse($file['inline']);
        $this->assertTrue($this->service->download(User::factory()->admin()->create(), $media->id, ChatAttachmentService::VARIANT_THUMB)['inline']);

        try {
            $this->service->download(User::factory()->applicant()->create(), $media->id, ChatAttachmentService::VARIANT_ORIGINAL);
            $this->fail('Applicants must not download');
        } catch (AuthorizationException) {
        }

        $this->app->make(ChatService::class)->delete($message->fresh(), $this->member);
        $this->expectException(NotFoundHttpException::class);
        $this->service->download($this->member, $media->id, ChatAttachmentService::VARIANT_ORIGINAL);
    }

    #[Test]
    public function the_limits_come_from_the_site_settings_within_bounds(): void
    {
        $settings = $this->app->make(SettingService::class);
        $this->assertSame([ChatAttachments::IMAGE_MAX_MB, ChatAttachments::DOCUMENT_MAX_MB], [$this->service->imageMaxMegabytes(), $this->service->documentMaxMegabytes()]);

        $settings->update([SettingKeys::CHAT_IMAGE_MAX_MB => '4', SettingKeys::CHAT_DOCUMENT_MAX_MB => '999']);

        $this->assertSame(4, $this->service->imageMaxMegabytes());
        $this->assertSame(ChatAttachments::DOCUMENT_MAX_MB, $this->service->documentMaxMegabytes());
        $this->assertSame(4 * 1024 * 1024, $this->service->clientConfig()['imageMaxBytes']);
    }
}
