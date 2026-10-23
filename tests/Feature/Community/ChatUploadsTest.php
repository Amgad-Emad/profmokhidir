<?php

namespace Tests\Feature\Community;

use App\Constants\AdminNavigation;
use App\Constants\ChatAttachments;
use App\Constants\Disks;
use App\Constants\SettingKeys;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatUpload;
use App\Models\User;
use App\Services\ChatAttachmentService;
use App\Services\ChatService;
use App\Services\SettingService;
use App\Support\ChatSampleFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatUploadsTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $room;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PRIVATE);
        $this->room = ChatChannel::factory()->room()->create();
        $this->member = User::factory()->member()->create();
    }

    private function upload(string $path, string $name, ?User $as = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($as ?? $this->member)->postJson(route(ChatAttachments::ROUTE_UPLOAD), ['file' => new UploadedFile($path, $name, null, null, true)]);
    }

    /**
     * A message with one image and one document, sent the way the composer sends it.
     *
     * @return array{0: ChatMessage, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function sentMessage(): array
    {
        $image = $this->upload(ChatSampleFiles::png(), 'scan.png')->json('upload.id');
        $document = $this->upload(ChatSampleFiles::pdf(), 'notes.pdf')->json('upload.id');

        $result = Livewire::actingAs($this->member)->test(Room::class)
            ->call('send', 'Files attached', (string) Str::uuid(), 0, null, null, [['id' => $image, 'alt' => 'A scanned page'], ['id' => $document]]);

        $returned = [];
        $result->assertReturned(function (array $value) use (&$returned): bool {
            $returned = $value;

            return true;
        });

        $this->assertTrue($returned['ok'], json_encode($returned));
        [$first, $second] = $returned['message']['attachments'];

        return [ChatMessage::query()->findOrFail($returned['message']['id']), $first, $second];
    }

    #[Test]
    public function a_member_stages_a_file_and_gets_its_public_id_back(): void
    {
        $this->upload(ChatSampleFiles::pdf(), 'Problem set.pdf')
            ->assertCreated()
            ->assertJsonPath('upload.kind', 'document')
            ->assertJsonPath('upload.name', 'Problem set.pdf');

        $this->assertSame(1, ChatUpload::query()->where('user_id', $this->member->id)->count());
    }

    #[Test]
    public function guests_and_pending_applicants_cannot_upload(): void
    {
        $this->post(route(ChatAttachments::ROUTE_UPLOAD), ['file' => UploadedFile::fake()->create('notes.pdf', 3)])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->applicant()->create())->post(route(ChatAttachments::ROUTE_UPLOAD), ['file' => UploadedFile::fake()->create('notes.pdf', 3)])->assertRedirect(route('community'));

        $this->assertSame(0, ChatUpload::query()->count());
    }

    #[Test]
    public function the_type_is_checked_by_content_not_by_name(): void
    {
        $this->upload(ChatSampleFiles::executable(), 'innocent.pdf')->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->upload(ChatSampleFiles::executable(), 'photo.jpg')->assertUnprocessable();

        $this->assertSame(0, ChatUpload::query()->count());
    }

    #[Test]
    public function uploads_can_be_switched_off_in_site_settings(): void
    {
        $this->app->make(SettingService::class)->update([SettingKeys::CHAT_UPLOADS_ENABLED => false]);

        $this->upload(ChatSampleFiles::pdf(), 'notes.pdf')->assertUnprocessable()->assertJsonPath('message', \App\Constants\ChatCopy::UPLOADS_OFF);
        // @js() prints quotes as \u0022 inside the page.
        $html = $this->actingAs($this->member)->get(route('community.chat'))->assertOk()->getContent();
        $this->assertStringContainsString(str_replace('"', '\u0022', '"attachments":{"enabled":false'), $html);
    }

    #[Test]
    public function the_size_limit_follows_the_site_setting(): void
    {
        $this->app->make(SettingService::class)->update([SettingKeys::CHAT_DOCUMENT_MAX_MB => '1']);

        $big = ChatSampleFiles::pdf();
        file_put_contents($big, str_repeat('0', 1536 * 1024), FILE_APPEND);

        $this->upload($big, 'big.pdf')->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame(0, ChatUpload::query()->count());
    }

    #[Test]
    public function a_member_can_discard_their_own_waiting_file(): void
    {
        $id = $this->upload(ChatSampleFiles::pdf(), 'notes.pdf')->json('upload.id');

        $this->actingAs(User::factory()->member()->create())->deleteJson(route(ChatAttachments::ROUTE_DISCARD, $id))->assertOk();
        $this->assertSame(1, ChatUpload::query()->count());

        $this->actingAs($this->member)->deleteJson(route(ChatAttachments::ROUTE_DISCARD, $id))->assertOk();
        $this->assertSame(0, ChatUpload::query()->count());
    }

    #[Test]
    public function sent_files_come_back_with_signed_links_and_the_image_description(): void
    {
        [$message, $image, $document] = $this->sentMessage();

        $this->assertSame(['image', 'A scanned page', 'PNG'], [$image['kind'], $image['alt'], $image['type']]);
        $this->assertSame(['document', 'notes.pdf', 'PDF'], [$document['kind'], $document['name'], $document['type']]);
        $this->assertStringContainsString('signature=', $document['url']);
        $this->assertStringContainsString('expires=', $image['thumb']);
        $this->assertCount(2, $message->attachments());
    }

    #[Test]
    public function members_and_admins_download_while_guests_and_pending_users_are_refused(): void
    {
        [, $image, $document] = $this->sentMessage();

        $this->actingAs(User::factory()->member()->create())->get($document['url'])
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=notes.pdf');
        $this->actingAs(User::factory()->admin()->create())->get($image['url'])->assertOk();
        $this->actingAs($this->member)->get($image['thumb'])->assertOk()->assertHeader('content-type', 'image/webp');

        auth()->logout();
        $this->get($document['url'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->applicant()->create())->get($document['url'])->assertRedirect(route('community'));
        $this->actingAs(User::factory()->suspendedMember()->create())->get($document['url'])->assertRedirect(route('community'));
    }

    #[Test]
    public function an_unsigned_or_tampered_link_is_refused(): void
    {
        [, , $document] = $this->sentMessage();

        $unsigned = route(ChatAttachments::ROUTE_DOWNLOAD, ['media' => 1, 'variant' => ChatAttachmentService::VARIANT_ORIGINAL]);
        $this->actingAs($this->member)->get($unsigned)->assertForbidden();
        $this->actingAs($this->member)->get(str_replace('/original?', '/thumb?', $document['url']))->assertForbidden();
    }

    #[Test]
    public function deleting_a_message_deletes_its_files_and_their_links_stop_working(): void
    {
        [$message, , $document] = $this->sentMessage();

        Livewire::actingAs($this->member)->test(Room::class)->call('remove', $message->id)
            ->assertReturned(fn (array $result): bool => $result['ok'] === true && $result['message']['attachments'] === []);

        $this->actingAs($this->member)->get($document['url'])->assertNotFound();
        $this->assertSame([], Storage::disk(Disks::PRIVATE)->allFiles());
    }

    #[Test]
    public function a_report_shows_the_moderators_the_reported_files(): void
    {
        [$message, , $document] = $this->sentMessage();
        $this->app->make(ChatService::class)->report($message, User::factory()->member()->create(), 'Not ours to share');

        $this->actingAs(User::factory()->admin()->create())->get(route(AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS)))
            ->assertOk()
            ->assertSee('data-report-attachments', false)
            ->assertSee('notes.pdf')
            ->assertSee('“A scanned page”', false);
    }

    #[Test]
    public function the_composer_offers_the_paperclip_the_conduct_note_and_drag_and_drop(): void
    {
        $this->actingAs($this->member)->get(route('community.chat'))->assertOk()
            ->assertSee('data-attach', false)
            ->assertSee(\App\Constants\ChatCopy::ATTACH_NOTE)
            ->assertSee('x-on:drop="onDrop($event)"', false)
            ->assertSee('x-on:paste', false)
            ->assertSee('data-tray', false)
            ->assertSee('data-chat-lightbox', false);
    }
}
