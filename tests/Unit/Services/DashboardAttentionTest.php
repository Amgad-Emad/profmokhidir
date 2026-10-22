<?php

namespace Tests\Unit\Services;

use App\Constants\AdminUx;
use App\Constants\Disks;
use App\Models\Album;
use App\Models\Award;
use App\Models\Tribute;
use App\Models\User;
use App\Services\AlbumService;
use App\Services\DashboardService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardAttentionTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $dashboard;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->dashboard = $this->app->make(DashboardService::class);
    }

    #[Test]
    public function cards_appear_only_when_something_waits_and_the_user_may_act_on_it(): void
    {
        $editor = User::factory()->editor()->create();

        $this->assertSame([], $this->dashboard->attentionCards($editor));

        Tribute::factory()->pending()->count(3)->create();
        $this->app->make(AlbumService::class)->addPhoto(Album::factory()->create(), PlaceholderImage::grey(20, 20));

        $cards = collect($this->dashboard->attentionCards($editor))->keyBy('key');

        $this->assertSame(3, $cards['tributes']['count']);
        $this->assertSame('Review tributes', $cards['tributes']['action']);
        $this->assertSame(1, $cards['missing-alt']['count']);
        $this->assertStringContainsString('missing_alt=1', $cards['missing-alt']['url']);

        $member = User::factory()->member()->create();
        $this->assertSame([], $this->dashboard->attentionCards($member));
    }

    #[Test]
    public function stale_drafts_are_gathered_across_types_oldest_first(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Old award']);
        $album = Album::factory()->draft()->create(['title' => 'Older album']);
        $award->forceFill(['updated_at' => now()->subDays(AdminUx::STALE_DRAFT_DAYS + 1)])->saveQuietly();
        $album->forceFill(['updated_at' => now()->subDays(AdminUx::STALE_DRAFT_DAYS + 10)])->saveQuietly();
        Award::factory()->draft()->create(['name' => 'New award']);

        $drafts = $this->dashboard->staleDrafts();

        $this->assertSame(2, $drafts['count']);
        $this->assertSame(['Older album', 'Old award'], array_column($drafts['items'], 'label'));
        $this->assertSame(route('admin.media.albums.edit', ['album' => $album->getKey()]), $drafts['items'][0]['url']);
    }

    #[Test]
    public function quick_actions_are_for_content_staff(): void
    {
        $actions = array_column($this->dashboard->quickActions(User::factory()->editor()->create()), 'label');

        $this->assertSame(['New publication', 'New commentary', 'Upload photos', 'Add CV entry'], $actions);
        $this->assertSame([], $this->dashboard->quickActions(User::factory()->member()->create()));
    }
}
