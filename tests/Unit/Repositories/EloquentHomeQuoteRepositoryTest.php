<?php

namespace Tests\Unit\Repositories;

use App\Models\HomeQuote;
use App\Repositories\Contracts\HomeQuoteRepositoryInterface;
use App\Repositories\Eloquent\EloquentHomeQuoteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentHomeQuoteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private HomeQuoteRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(HomeQuoteRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentHomeQuoteRepository::class, $this->repository);
    }

    #[Test]
    public function search_matches_the_quote_attribution_and_source(): void
    {
        HomeQuote::factory()->create(['quote' => 'The light we pass on', 'attribution' => null, 'source' => null]);
        HomeQuote::factory()->create(['quote' => 'x', 'attribution' => 'A student of light', 'source' => null]);
        HomeQuote::factory()->create(['quote' => 'y', 'attribution' => null, 'source' => 'Lecture on light']);
        HomeQuote::factory()->create(['quote' => 'z', 'attribution' => null, 'source' => null]);

        $this->assertSame(3, $this->repository->search('light')->total());
        $this->assertContains('attribution', $this->repository->sortableColumns());
        $this->assertSame(['status'], $this->repository->filterColumns());
    }

    #[Test]
    public function published_lists_only_published_quotes_in_display_order_and_reorder_persists(): void
    {
        $b = HomeQuote::factory()->published()->sortOrder(2)->create();
        $a = HomeQuote::factory()->published()->sortOrder(1)->create();
        HomeQuote::factory()->draft()->create();

        $this->assertSame([$a->id, $b->id], $this->repository->published()->modelKeys());
        $this->assertSame(2, $this->repository->countPublished());

        $this->repository->reorder([$b->id, $a->id]);

        $this->assertSame([$b->id, $a->id], $this->repository->published()->modelKeys());
    }

    #[Test]
    public function deleted_quotes_go_to_the_trash_and_can_be_restored(): void
    {
        $quote = HomeQuote::factory()->published()->create();

        $this->repository->delete($quote);

        $this->assertSame(0, $this->repository->countPublished());
        $this->assertSame(1, $this->repository->countTrashed());
        $this->assertTrue($this->repository->restore($quote->id)->is($quote));
    }
}
