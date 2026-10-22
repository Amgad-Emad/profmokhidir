<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\Publication;
use App\Models\User;
use App\Services\ActivityFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityFeedServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityFeedService $feed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->feed = $this->app->make(ActivityFeedService::class);
    }

    #[Test]
    public function each_entry_reads_as_a_plain_sentence(): void
    {
        $amina = User::factory()->editor()->create(['name' => 'Amina']);
        $applicant = User::factory()->create(['name' => 'Omar']);
        $publication = Publication::factory()->create(['title' => 'Soil and Water']);

        $approved = AuditLog::factory()->for($amina, 'user')->action(AuditAction::TRIBUTE_APPROVED)->create(['meta' => ['name' => 'Yusuf']]);
        $published = AuditLog::factory()->for($amina, 'user')->action(AuditAction::CONTENT_PUBLISHED)->create([
            'subject_type' => $publication->getMorphClass(), 'subject_id' => $publication->getKey(), 'meta' => ['label' => 'Soil and Water'],
        ]);
        $trashed = AuditLog::factory()->for($amina, 'user')->action(AuditAction::CONTENT_DELETED)->create([
            'subject_type' => $publication->getMorphClass(), 'subject_id' => $publication->getKey(), 'meta' => [],
        ]);
        $welcomed = AuditLog::factory()->for($amina, 'user')->action(AuditAction::COMMUNITY_APPROVED)->create([
            'subject_type' => $applicant->getMorphClass(), 'subject_id' => $applicant->getKey(), 'meta' => [],
        ]);
        $visitor = AuditLog::factory()->action(AuditAction::COMMUNITY_APPLIED)->create(['user_id' => null, 'meta' => []]);

        $this->assertSame('Amina approved a tribute from Yusuf', $this->feed->sentence($approved));
        $this->assertSame('Amina published the publication “Soil and Water”', $this->feed->sentence($published));
        $this->assertSame('Amina moved a publication to the trash', $this->feed->sentence($trashed));
        $this->assertSame('Amina welcomed Omar to the Scientific Community', $this->feed->sentence($welcomed->load('subject')));
        $this->assertSame('A visitor applied to join the Scientific Community', $this->feed->sentence($visitor));
    }

    #[Test]
    public function sign_ins_are_left_out_and_the_newest_comes_first(): void
    {
        $amina = User::factory()->editor()->create(['name' => 'Amina']);
        AuditLog::factory()->for($amina, 'user')->action(AuditAction::TRIBUTE_APPROVED)->create(['meta' => ['name' => 'Yusuf'], 'created_at' => now()->subHour()]);
        AuditLog::factory()->for($amina, 'user')->action(AuditAction::ADMIN_LOGIN)->create(['created_at' => now()->subMinutes(30)]);
        AuditLog::factory()->for($amina, 'user')->action(AuditAction::TRIBUTE_REJECTED)->create(['meta' => ['name' => 'Sara'], 'created_at' => now()]);

        $entries = $this->feed->recent();

        $this->assertSame(['Amina declined a tribute from Sara', 'Amina approved a tribute from Yusuf'], array_column($entries, 'sentence'));
    }
}
