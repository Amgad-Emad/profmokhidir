<?php

namespace Tests\Unit\Repositories;

use App\Constants\Placeholders;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\Profile;
use App\Repositories\Contracts\AwardRepositoryInterface;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The content repository helpers behind the dashboard: placeholder copy,
 * drafts left alone too long, and records fetched for bulk actions.
 */
class ContentRepositoryAttentionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function count_containing_finds_the_marker_in_search_and_long_text_columns_but_not_in_the_trash(): void
    {
        $awards = $this->app->make(AwardRepositoryInterface::class);
        Award::factory()->create(['name' => 'Real award', 'organisation' => 'Royal Society', 'description' => null]);
        Award::factory()->create(['name' => Placeholders::MARKER.' Sample award']);
        Award::factory()->create(['name' => 'Trashed', 'description' => Placeholders::MARKER.' text'])->delete();

        $this->assertSame(1, $awards->countContaining(Placeholders::MARKER));

        $commentaries = $this->app->make(CommentaryRepositoryInterface::class);
        Commentary::factory()->create(['title' => 'Real essay', 'summary' => 'Real', 'byline' => null, 'epigraph' => null, 'body' => '<p>'.Placeholders::MARKER.' body</p>']);

        $this->assertSame(1, $commentaries->countContaining(Placeholders::MARKER));

        $profiles = $this->app->make(ProfileRepositoryInterface::class);
        Profile::factory()->create(['name' => 'Prof', 'title' => null, 'affiliation' => null, 'short_bio' => null, 'full_bio' => null, 'contact_note' => null, 'expertise' => [Placeholders::MARKER.' Soil'], 'key_facts' => []]);

        $this->assertSame(1, $profiles->countContaining(Placeholders::MARKER));
    }

    #[Test]
    public function the_placeholder_filter_narrows_the_admin_search(): void
    {
        $awards = $this->app->make(AwardRepositoryInterface::class);
        Award::factory()->create(['name' => 'Real award', 'organisation' => 'Royal Society', 'description' => null]);
        $sample = Award::factory()->create(['name' => Placeholders::MARKER.' Sample award']);

        $results = $awards->search(null, [ContentRepositoryInterface::FILTER_PLACEHOLDER => true]);

        $this->assertSame([$sample->getKey()], $results->getCollection()->modelKeys());
    }

    #[Test]
    public function stale_drafts_are_old_drafts_only_oldest_first(): void
    {
        $awards = $this->app->make(AwardRepositoryInterface::class);
        $oldest = Award::factory()->draft()->create();
        $older = Award::factory()->draft()->create();
        $fresh = Award::factory()->draft()->create();
        $published = Award::factory()->published()->create();
        $oldest->forceFill(['updated_at' => now()->subDays(40)])->saveQuietly();
        $older->forceFill(['updated_at' => now()->subDays(20)])->saveQuietly();
        $published->forceFill(['updated_at' => now()->subDays(60)])->saveQuietly();

        $before = now()->subDays(14);

        $this->assertSame([$oldest->getKey(), $older->getKey()], $awards->staleDrafts($before)->modelKeys());
        $this->assertSame([$oldest->getKey()], $awards->staleDrafts($before, 1)->modelKeys());
        $this->assertSame(2, $awards->countStaleDrafts($before));
        $this->assertNotContains($fresh->getKey(), $awards->staleDrafts($before)->modelKeys());
    }

    #[Test]
    public function find_many_returns_live_records_in_display_order(): void
    {
        $awards = $this->app->make(AwardRepositoryInterface::class);
        $second = Award::factory()->sortOrder(2)->create();
        $first = Award::factory()->sortOrder(1)->create();
        $trashed = Award::factory()->sortOrder(3)->create();
        $trashed->delete();

        $this->assertSame([$first->getKey(), $second->getKey()], $awards->findMany([$second->getKey(), $first->getKey(), $trashed->getKey()])->modelKeys());
    }
}
