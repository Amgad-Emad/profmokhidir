<?php

namespace Tests\Feature\Community;

use App\Constants\ChatAttachments;
use App\Constants\Disks;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\User;
use App\Support\ChatSampleFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Tests\TestCase;

/**
 * A chat image's WebP thumbnail is a queued conversion. Until a worker has
 * made it, the message links to the original through the same signed route
 * (the room scales it down); once it is made, the message is marked changed
 * and the next poll brings the thumbnail link.
 */
class ChatImageThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $room;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PRIVATE);
        $this->room = ChatChannel::factory()->room()->create();
        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan']);
    }

    /**
     * @return array<string, mixed>
     */
    private function returned(mixed $component): array
    {
        $returned = [];
        $component->assertReturned(function (array $value) use (&$returned): bool {
            $returned = $value;

            return true;
        });

        return $returned;
    }

    private function fetch(string $url): TestResponse
    {
        return $this->actingAs($this->member)->get($url);
    }

    #[Test]
    public function an_image_has_a_usable_url_before_and_after_its_thumbnail_is_made(): void
    {
        // No worker: the conversion job waits in the queue, as it does locally without one.
        Queue::fake();

        $id = $this->actingAs($this->member)->postJson(route(ChatAttachments::ROUTE_UPLOAD), [
            'file' => new UploadedFile(ChatSampleFiles::png(), 'pasted-image.png', null, null, true),
        ])->assertCreated()->json('upload.id');
        Queue::assertPushed(PerformConversionsJob::class);

        $sent = $this->returned(Livewire::actingAs($this->member)->test(Room::class)
            ->call('send', 'A pasted image', (string) Str::uuid(), 0, null, null, [['id' => $id]]));
        $this->assertTrue($sent['ok'], json_encode($sent));
        $before = $sent['message']['attachments'][0];

        $this->assertFalse($before['thumb_ready']);
        $this->assertSame('Image shared by Amina Hassan', $before['alt']);
        $this->assertStringContainsString('/original?', $before['thumb']);
        $response = $this->fetch($before['thumb'])->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertNotSame('', $response->streamedContent());

        // The worker runs the job (a queued job is unserialized, so it sees the media on the message now).
        Queue::pushed(PerformConversionsJob::class)->each(fn (PerformConversionsJob $job) => $this->app->call([unserialize(serialize($job)), 'handle']));

        $synced = $this->returned(Livewire::actingAs($this->member)->test(Room::class)
            ->call('sync', $sent['sync']['cursor'], $sent['sync']['since']));
        $changed = collect($synced['changed'])->firstWhere('id', $sent['message']['id']);
        $this->assertNotNull($changed, 'the next poll brings the message again');
        $after = $changed['attachments'][0];

        $this->assertTrue($after['thumb_ready']);
        $this->assertStringContainsString('/thumb?', $after['thumb']);
        $this->assertSame($sent['message']['version'], $changed['version']);
        $response = $this->fetch($after['thumb'])->assertOk();
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function a_thumbnail_made_before_sending_is_used_straight_away(): void
    {
        // The test queue is sync: the conversion runs while the file waits in the tray.
        $id = $this->actingAs($this->member)->postJson(route(ChatAttachments::ROUTE_UPLOAD), [
            'file' => new UploadedFile(ChatSampleFiles::png(), 'scan.png', null, null, true),
        ])->assertCreated()->json('upload.id');

        $sent = $this->returned(Livewire::actingAs($this->member)->test(Room::class)
            ->call('send', '', (string) Str::uuid(), 0, null, null, [['id' => $id, 'alt' => 'A scanned page']]));
        $image = $sent['message']['attachments'][0];

        $this->assertTrue($image['thumb_ready']);
        $this->assertSame('A scanned page', $image['alt']);
        $this->assertStringContainsString('/thumb?', $image['thumb']);
        $this->assertSame('image/webp', $this->fetch($image['thumb'])->assertOk()->headers->get('Content-Type'));
    }

    #[Test]
    public function the_grid_breathes_until_an_image_has_loaded(): void
    {
        $this->actingAs($this->member)->get(route('community.chat'))->assertOk()
            ->assertSee(":class=\"{ 'is-loaded': a.loaded }\"", false)
            ->assertSee('x-on:load="a.loaded = true"', false)
            ->assertSee('x-on:error="a.loaded = true"', false)
            ->assertSee(':data-thumb-ready=', false);
    }
}
