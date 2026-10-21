<?php

namespace Tests\Feature\Database;

use App\Constants\CommunityStatus;
use App\Constants\ContentStatus;
use App\Constants\Roles;
use App\Constants\SettingKeys;
use App\Constants\TributeStatus;
use App\Constants\Archive;
use App\Models\Album;
use App\Models\Award;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\Setting;
use App\Models\TeachingMaterial;
use App\Models\Tribute;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Seeding attaches real placeholder images (with synchronous conversions in
 * tests), so the whole dataset is seeded once and checked in one test.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_database_seeder_builds_the_full_placeholder_dataset(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $this->seed();

        $this->assertQuantities();
        $this->assertAccounts();
        $this->assertPublications();
        $this->assertMedia();
        $this->assertPlaceholderMarkers();
        $this->assertChat();
    }

    private function assertQuantities(): void
    {
        $this->assertSame(1, Profile::query()->count());
        $this->assertSame(count(SettingKeys::all()), Setting::query()->count());
        $this->assertSame(12, CvEntry::query()->count());
        $this->assertSame(15, Publication::query()->count());
        $this->assertSame(6, Award::query()->count());
        $this->assertSame(4, Video::query()->count());
        $this->assertSame(2, Album::query()->count());
        $this->assertSame(8, TeachingMaterial::query()->count());
        $this->assertSame(5, Commentary::query()->count());
        $this->assertSame(3, OtherItem::query()->count());
        $this->assertSame(6, Tribute::query()->withStatus(TributeStatus::Approved)->count());
        $this->assertSame(3, Tribute::query()->withStatus(TributeStatus::Pending)->count());
        $this->assertSame(1, Tribute::query()->published()->featured()->count());
        $this->assertSame(1, ChatChannel::query()->where('slug', 'general')->count());
        $this->assertGreaterThanOrEqual(3, ChatMessage::query()->count());
    }

    private function assertAccounts(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole(Roles::ADMIN));
        $this->assertTrue($editor->hasRole(Roles::EDITOR));
        $this->assertNotNull($admin->email_verified_at, 'Local staff accounts are seeded verified.');
        $this->assertNotNull($editor->email_verified_at);
        $this->assertSame(2, User::query()->communityStatus(CommunityStatus::Approved)->count());
        $this->assertSame(2, User::query()->communityStatus(CommunityStatus::Pending)->count());
        $this->assertSame(2, User::query()->role(Roles::MEMBER)->count());
        $this->assertSame(2, User::query()->role(Roles::APPLICANT)->count());
    }

    private function assertPublications(): void
    {
        $this->assertCount(6, Publication::query()->distinct()->pluck('type'));
        $this->assertGreaterThan(5, Publication::query()->distinct()->pluck('year')->count());
        $this->assertSame(15, Publication::query()->published()->count());
    }

    private function assertMedia(): void
    {
        $profile = Profile::query()->firstOrFail();
        $this->assertNotNull($profile->portrait());
        $this->assertSame('public', $profile->portrait()->disk);

        $albums = Album::query()->get();
        $this->assertSame(9, $albums->sum(fn (Album $album): int => $album->photos()->count()));
        $this->assertNotNull($albums->first()->cover());
        $this->assertSame(1, $albums->first()->photos()->filter(fn ($photo): bool => (bool) $photo->getAttribute(Archive::FEATURED))->count(), 'Each seeded wall features its first print');
        $this->assertSame(1, Video::query()->where(Archive::FEATURED, true)->count(), 'One seeded recording opens the lecture hall');

        foreach (Video::query()->get() as $video) {
            $this->assertNotNull($video->thumbnail(), "video {$video->id} has no thumbnail");
        }

        $this->assertSame(0, Publication::query()->get()->filter(fn (Publication $p): bool => $p->file() !== null)->count());

        $materials = TeachingMaterial::query()->get();
        $this->assertSame(7, $materials->filter(fn (TeachingMaterial $m): bool => $m->file() !== null)->count());
        $this->assertSame(1, $materials->filter(fn (TeachingMaterial $m): bool => filled($m->external_url))->count());

        foreach ($materials as $material) {
            if ($material->file() !== null) {
                $this->assertSame($material->fileDisk(), $material->file()->disk, "teaching material {$material->id} is on the wrong disk");
                $this->assertGreaterThan(0, $material->file()->size);
            }
        }
    }

    private function assertPlaceholderMarkers(): void
    {
        $profile = Profile::query()->firstOrFail();
        $this->assertStringContainsString('[Placeholder]', $profile->short_bio);
        $this->assertStringContainsString('[Placeholder]', $profile->full_bio);
        $this->assertSame(ContentStatus::Published, $profile->status);

        foreach ([CvEntry::class, Publication::class, Video::class, Album::class, TeachingMaterial::class, Commentary::class, OtherItem::class] as $model) {
            foreach ($model::query()->get() as $row) {
                $this->assertStringContainsString('[Placeholder]', $row->title, "{$model} #{$row->id}");
            }
        }

        foreach (Award::query()->get() as $award) {
            $this->assertStringContainsString('[Placeholder]', $award->name);
        }

        foreach (Tribute::query()->get() as $tribute) {
            $this->assertStringContainsString('[Placeholder]', $tribute->message);
        }
    }

    private function assertChat(): void
    {
        $channel = ChatChannel::query()->where('slug', 'general')->firstOrFail();

        $this->assertSame($channel->messages()->max('id'), $channel->last_message_id);
        $this->assertGreaterThanOrEqual(1, $channel->reads()->count());
    }
}
