<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Exceptions\TributeLimitReachedException;
use App\Models\AuditLog;
use App\Models\Tribute;
use App\Models\User;
use App\Services\TributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeServiceTest extends TestCase
{
    use RefreshDatabase;

    private TributeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(TributeService::class);
    }

    #[Test]
    public function submit_creates_a_pending_tribute_with_cleaned_fields_and_a_hashed_ip(): void
    {
        $tribute = $this->service->submit([
            'name' => '  Ahmed ',
            'relationship' => 'student',
            'location' => ' Khartoum ',
            'message' => ' '.str_repeat('x', 2500).' ',
            'email' => ' Ahmed@Example.COM ',
        ], '203.0.113.9');

        $this->assertSame(TributeStatus::Pending, $tribute->status);
        $this->assertSame('Ahmed', $tribute->name);
        $this->assertSame(TributeRelationship::Student, $tribute->relationship);
        $this->assertSame('Khartoum', $tribute->location);
        $this->assertSame(2000, mb_strlen($tribute->message));
        $this->assertSame('ahmed@example.com', $tribute->email);
        $this->assertSame($this->service->hashIp('203.0.113.9'), $tribute->ip_hash);
        $this->assertStringNotContainsString('203.0.113.9', $tribute->ip_hash);
        $this->assertNull($tribute->approved_at);
        $this->assertArrayNotHasKey('email', $tribute->toArray());
        $this->assertArrayNotHasKey('ip_hash', $tribute->toArray());
    }

    #[Test]
    public function submit_is_rate_limited_per_ip(): void
    {
        $data = ['name' => 'A', 'relationship' => TributeRelationship::Friend, 'message' => 'Hello'];

        for ($i = 0; $i < TributeService::SUBMISSION_LIMIT; $i++) {
            $this->service->submit($data, '198.51.100.1');
        }

        $this->service->submit($data, '198.51.100.2'); // another address is fine

        $this->expectException(TributeLimitReachedException::class);
        $this->service->submit($data, '198.51.100.1');
    }

    #[Test]
    public function approve_sets_approval_fields_and_is_audited(): void
    {
        $actor = User::factory()->editor()->create();
        $tribute = Tribute::factory()->pending()->create();

        $tribute = $this->service->approve($tribute, $actor);

        $this->assertSame(TributeStatus::Approved, $tribute->status);
        $this->assertSame($actor->id, $tribute->approved_by);
        $this->assertNotNull($tribute->approved_at);
        $this->assertTrue($tribute->approved_at->equalTo($tribute->published_at));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => AuditAction::TRIBUTE_APPROVED,
            'subject_type' => $tribute->getMorphClass(),
            'subject_id' => $tribute->id,
        ]);
    }

    #[Test]
    public function reject_clears_approval_fields_and_is_audited_with_a_note(): void
    {
        $actor = User::factory()->editor()->create();
        $tribute = Tribute::factory()->approved($actor)->create();

        $tribute = $this->service->reject($tribute, $actor, 'Off topic');

        $this->assertSame(TributeStatus::Rejected, $tribute->status);
        $this->assertNull($tribute->approved_by);
        $this->assertNull($tribute->approved_at);
        $this->assertNull($tribute->published_at);
        $log = AuditLog::query()->where('action', AuditAction::TRIBUTE_REJECTED)->where('subject_id', $tribute->id)->firstOrFail();
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame(['name' => $tribute->name, 'note' => 'Off topic'], $log->meta);
    }

    #[Test]
    public function delete_is_audited(): void
    {
        $actor = User::factory()->admin()->create();
        $tribute = Tribute::factory()->create();

        $this->assertTrue($this->service->delete($tribute, $actor));

        $this->assertDatabaseMissing('tributes', ['id' => $tribute->id]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $actor->id, 'action' => AuditAction::TRIBUTE_DELETED, 'subject_id' => $tribute->id]);
    }

    #[Test]
    public function approved_listing_is_cached_and_flushed_on_approval(): void
    {
        $actor = User::factory()->editor()->create();
        Tribute::factory()->approved($actor)->create();
        $pending = Tribute::factory()->pending()->create();
        Tribute::factory()->rejected()->create();

        $this->assertCount(1, $this->service->approved());
        $this->assertTrue(Cache::has(CacheKeys::TRIBUTES));

        $this->service->approve($pending, $actor);

        $this->assertFalse(Cache::has(CacheKeys::TRIBUTES));
        $this->assertCount(2, $this->service->approved());
    }

    #[Test]
    public function latest_approved_pending_and_pagination_helpers(): void
    {
        $actor = User::factory()->editor()->create();
        $old = Tribute::factory()->approved($actor)->create(['approved_at' => now()->subDays(10), 'published_at' => now()->subDays(10)]);
        $new = Tribute::factory()->approved($actor)->create(['approved_at' => now()->subDay(), 'published_at' => now()->subDay()]);
        Tribute::factory()->pending()->count(2)->create();

        $latest = $this->service->latestApproved(1);
        $this->assertTrue($latest->first()->is($new));
        $this->assertTrue(Cache::has(CacheKeys::TRIBUTES.'.latest'));

        $this->assertCount(2, $this->service->pending());
        $this->assertSame(2, $this->service->countPending());
        $this->assertSame(2, $this->service->paginateByStatus('pending')->total());
        $this->assertSame(4, $this->service->paginateByStatus(null)->total());
        $this->assertContains($old->id, $this->service->approved()->pluck('id')->all());
    }
}
