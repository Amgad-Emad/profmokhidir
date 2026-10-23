<?php

namespace Tests\Feature\Console;

use App\Console\Commands\PruneChatUploads;
use App\Models\ChatUpload;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruneChatUploadsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_unsent_uploads_older_than_a_day(): void
    {
        $stale = ChatUpload::factory()->stale()->create();
        $fresh = ChatUpload::factory()->create();

        $this->artisan('chat:prune-uploads')
            ->expectsOutputToContain('Removed 1 unsent chat upload older than 24 hours.')
            ->assertSuccessful();

        $this->assertNull($stale->fresh());
        $this->assertNotNull($fresh->fresh());
    }

    #[Test]
    public function the_scheduler_runs_it_every_hour(): void
    {
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'chat:prune-uploads'));

        $this->assertNotNull($event, PruneChatUploads::class.' is not scheduled');
        $this->assertSame('0 * * * *', $event->expression);
    }
}
