<?php

namespace Tests\Unit\Services;

use App\Constants\AdminUx;
use App\Models\Award;
use App\Models\Publication;
use App\Models\User;
use App\Services\AdminSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminSearchService $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = $this->app->make(AdminSearchService::class);
    }

    #[Test]
    public function titles_of_every_type_link_to_their_edit_screen_with_the_status(): void
    {
        $publication = Publication::factory()->published()->create(['title' => 'Groundnut yields']);
        $award = Award::factory()->draft()->create(['name' => 'Groundnut prize']);
        Publication::factory()->create(['title' => 'Something else', 'abstract' => null, 'topic' => null]);

        $results = $this->search->search(User::factory()->editor()->create(), 'groundnut');
        $byLabel = collect($results)->keyBy('label');

        $this->assertCount(2, $results);
        $this->assertSame(route('admin.content.publications.edit', ['publication' => $publication->getKey()]), $byLabel['Groundnut yields']['url']);
        $this->assertSame('Published', $byLabel['Groundnut yields']['status']);
        $this->assertSame('Publications', $byLabel['Groundnut yields']['group']);
        $this->assertSame(route('admin.content.awards.edit', ['award' => $award->getKey()]), $byLabel['Groundnut prize']['url']);
        $this->assertSame('Draft', $byLabel['Groundnut prize']['status']);
    }

    #[Test]
    public function short_terms_and_staff_without_content_rights_get_nothing(): void
    {
        Publication::factory()->create(['title' => 'Groundnut yields']);

        $this->assertSame([], $this->search->search(User::factory()->editor()->create(), str_repeat('g', AdminUx::SEARCH_MIN_LENGTH - 1)));
        $this->assertSame([], $this->search->search(User::factory()->member()->create(), 'groundnut'));
    }

    #[Test]
    public function results_are_capped_per_type(): void
    {
        Publication::factory()->count(AdminUx::PALETTE_RESULTS_PER_TYPE + 2)->create(['title' => 'Sorghum trial']);

        $this->assertCount(AdminUx::PALETTE_RESULTS_PER_TYPE, $this->search->search(User::factory()->editor()->create(), 'sorghum'));
    }
}
