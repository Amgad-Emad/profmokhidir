<?php

namespace Tests\Unit\Repositories;

use App\Models\OtherItem;
use App\Repositories\Contracts\OtherItemRepositoryInterface;
use App\Repositories\Eloquent\EloquentOtherItemRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentOtherItemRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OtherItemRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(OtherItemRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentOtherItemRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_by_slug_and_checks_slug_existence(): void
    {
        $draft = OtherItem::factory()->draft()->create(['slug' => 'draft-item']);
        $published = OtherItem::factory()->published()->create(['slug' => 'press-profile']);

        $this->assertTrue($draft->is($this->repository->findBySlug('draft-item')));
        $this->assertNull($this->repository->findPublishedBySlug('draft-item'));
        $this->assertTrue($published->is($this->repository->findPublishedBySlug('press-profile')));

        $this->assertTrue($this->repository->slugExists('press-profile'));
        $this->assertFalse($this->repository->slugExists('press-profile', $published->id));
        $this->assertFalse($this->repository->slugExists('missing'));
    }

    #[Test]
    public function it_lists_published_items_in_a_category_and_the_categories(): void
    {
        OtherItem::factory()->published()->category('In the press')->count(2)->create();
        OtherItem::factory()->published()->category('Archive')->create();
        OtherItem::factory()->draft()->category('Announcements')->create();
        OtherItem::factory()->published()->create(['category' => null]);

        $press = $this->repository->publishedInCategory('In the press');
        $this->assertCount(2, $press);
        $this->assertTrue($press->every(fn (OtherItem $item): bool => $item->relationLoaded('media')));

        $this->assertSame(['Archive', 'In the press'], $this->repository->publishedCategories());
    }
}
