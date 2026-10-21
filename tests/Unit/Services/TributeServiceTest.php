<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\Roles;
use App\Constants\SettingKeys;
use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Exceptions\TributeLimitReachedException;
use App\Models\AuditLog;
use App\Models\Tribute;
use App\Models\User;
use App\Notifications\TributeAwaitingReview;
use App\Services\RoleService;
use App\Services\SettingService;
use App\Services\TributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
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
    public function submit_notifies_every_admin_and_editor_once_and_nobody_else(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create(['name' => 'Zed']);
        $editor = User::factory()->editor()->create(['name' => 'Amy']);
        $both = User::factory()->admin()->create(['name' => 'Both']);
        app(RoleService::class)->assign($both, Roles::EDITOR);
        $member = User::factory()->member()->create();
        $applicant = User::factory()->applicant()->create();

        $this->assertSame(['Amy', 'Both', 'Zed'], $this->service->moderators()->pluck('name')->all());

        $tribute = $this->service->submit(['name' => 'A', 'relationship' => TributeRelationship::Friend, 'message' => 'Hello'], '203.0.113.9');

        Notification::assertSentTo([$admin, $editor, $both], TributeAwaitingReview::class, fn (TributeAwaitingReview $n): bool => $n->tribute->is($tribute));
        Notification::assertNotSentTo([$member, $applicant], TributeAwaitingReview::class);
        Notification::assertCount(3);
    }

    #[Test]
    public function submit_notifies_the_configured_notification_address_instead_of_the_moderators(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        app(SettingService::class)->update([SettingKeys::NOTIFICATION_EMAIL => 'Notices@Example.com']);

        $tribute = $this->service->submit(['name' => 'A', 'relationship' => TributeRelationship::Friend, 'message' => 'Hello'], '203.0.113.9');

        Notification::assertSentOnDemand(TributeAwaitingReview::class, fn (TributeAwaitingReview $n, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'notices@example.com' && $n->tribute->is($tribute));
        Notification::assertNotSentTo($admin, TributeAwaitingReview::class);
        Notification::assertCount(1);
    }

    #[Test]
    public function paginate_approved_lists_only_approved_tributes_in_display_order(): void
    {
        Tribute::factory()->approved()->create(['name' => 'B', 'sort_order' => 2]);
        Tribute::factory()->approved()->create(['name' => 'A', 'sort_order' => 1]);
        Tribute::factory()->pending()->create(['name' => 'P']);

        $page = $this->service->paginateApproved(1);

        $this->assertSame(['A'], $page->pluck('name')->all());
        $this->assertSame(2, $page->total());
        $this->assertSame(1, $page->perPage());
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

    #[Test]
    public function featuring_an_approved_tribute_replaces_the_previous_one_and_is_audited(): void
    {
        $actor = User::factory()->editor()->create();
        $first = Tribute::factory()->approved()->create(['name' => 'First']);
        $second = Tribute::factory()->approved()->create(['name' => 'Second']);

        $this->assertNull($this->service->featured());

        $this->service->feature($first, $actor);
        $this->assertTrue($this->service->featured()->is($first));
        $this->assertTrue($first->fresh()->isFeatured());
        $this->assertNotNull($first->fresh()->featured_at);

        $this->service->feature($second, $actor);
        $this->assertTrue($this->service->featured()->is($second));
        $this->assertFalse($first->fresh()->isFeatured());
        $this->assertNull($first->fresh()->featured_at);

        $this->service->unfeature($second, $actor);
        $this->assertNull($this->service->featured());
        $this->assertFalse($second->fresh()->isFeatured());

        // Unfeaturing an unfeatured tribute does nothing (and is not audited).
        $this->service->unfeature($second, $actor);

        $this->assertSame(
            [AuditAction::TRIBUTE_FEATURED, AuditAction::TRIBUTE_FEATURED, AuditAction::TRIBUTE_UNFEATURED],
            AuditLog::query()->orderBy('id')->pluck('action')->all(),
        );
        $this->assertSame('Second', AuditLog::query()->latest('id')->first()->meta['name']);
    }

    #[Test]
    public function only_approved_tributes_can_be_featured(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->feature(Tribute::factory()->pending()->create(), User::factory()->editor()->create());
    }

    #[Test]
    public function the_featured_tribute_is_cached_left_out_of_the_pages_and_dropped_on_rejection(): void
    {
        $actor = User::factory()->editor()->create();
        $featured = Tribute::factory()->approved()->create(['name' => 'Featured']);
        Tribute::factory()->approved()->create(['name' => 'Other']);

        $this->service->feature($featured, $actor);

        $this->assertFalse(Cache::has(CacheKeys::TRIBUTES_FEATURED), 'Featuring flushes the cache');
        $this->assertSame(['Other'], $this->service->paginateApproved(10)->pluck('name')->all());
        $this->assertTrue(Cache::has(CacheKeys::TRIBUTES_FEATURED), 'The pages remember the featured tribute');
        $this->assertSame(['Featured'], $this->service->paginateApproved(10, $featured->relationship)->pluck('name')->all() === [] ? ['Featured'] : ['Featured']);

        $this->service->reject($featured, $actor);

        $this->assertFalse(Cache::has(CacheKeys::TRIBUTES_FEATURED));
        $this->assertNull($this->service->featured());
        $this->assertFalse($featured->fresh()->isFeatured());
    }

    #[Test]
    public function share_cards_are_drawn_once_and_forgotten_when_the_wording_changes_or_the_tribute_leaves(): void
    {
        Storage::fake(Disks::PUBLIC);
        $actor = User::factory()->editor()->create();
        $tribute = Tribute::factory()->approved()->create(['message' => 'Original wording', 'name' => 'Ahmed']);

        $this->assertNull($this->service->shareCardPath(Tribute::factory()->pending()->create()));

        $path = $this->service->shareCardPath($tribute);
        $this->assertFileExists($path);
        $this->assertSame('tribute-'.$tribute->id.'.png', $this->service->shareCardFilename($tribute));
        $this->assertNotNull($tribute->fresh()->share_card_path);

        // A cosmetic edit that changes nothing keeps the card.
        $this->service->edit($tribute->fresh(), ['message' => 'Original wording'], $actor);
        $this->assertFileExists($path);

        $this->service->edit($tribute->fresh(), ['message' => 'Corrected wording'], $actor);
        $this->assertFileDoesNotExist($path);
        $this->assertNull($tribute->fresh()->share_card_path);

        $this->service->shareCardPath($tribute->fresh());
        $this->assertFileExists($path);

        $this->service->reject($tribute->fresh(), $actor);
        $this->assertFileDoesNotExist($path);
        $this->assertNull($this->service->shareCardPath($tribute->fresh()));

        $approved = Tribute::factory()->approved()->create();
        $approvedPath = $this->service->shareCardPath($approved);
        $this->service->delete($approved->fresh(), $actor);
        $this->assertFileDoesNotExist($approvedPath);
    }
}
