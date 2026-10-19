<?php

namespace Tests\Unit\Repositories;

use App\Constants\TeachingAccess;
use App\Models\TeachingMaterial;
use App\Repositories\Contracts\TeachingMaterialRepositoryInterface;
use App\Repositories\Eloquent\EloquentTeachingMaterialRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentTeachingMaterialRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TeachingMaterialRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(TeachingMaterialRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentTeachingMaterialRepository::class, $this->repository);
    }

    #[Test]
    public function published_with_access_limits_to_the_given_levels(): void
    {
        TeachingMaterial::factory()->published()->count(2)->create();
        TeachingMaterial::factory()->published()->membersOnly()->create();
        TeachingMaterial::factory()->draft()->membersOnly()->create();
        TeachingMaterial::factory()->draft()->create();

        $public = $this->repository->publishedWithAccess(TeachingAccess::Public);
        $this->assertCount(2, $public);
        $this->assertTrue($public->every(fn (TeachingMaterial $m): bool => $m->access === TeachingAccess::Public));
        $this->assertTrue($public->every(fn (TeachingMaterial $m): bool => $m->relationLoaded('media')));

        $this->assertCount(3, $this->repository->publishedWithAccess(TeachingAccess::Public, TeachingAccess::Members));
        $this->assertCount(1, $this->repository->publishedWithAccess(TeachingAccess::Members));
    }

    #[Test]
    public function search_filters_by_access_and_course(): void
    {
        TeachingMaterial::factory()->course('AGR 301')->create(['title' => 'Notes A']);
        TeachingMaterial::factory()->course('AGR 301')->membersOnly()->create(['title' => 'Notes B']);
        TeachingMaterial::factory()->course('AGR 412')->membersOnly()->create(['title' => 'Notes C']);

        $this->assertSame(3, $this->repository->search('Notes')->total());
        $this->assertSame(2, $this->repository->search(null, ['access' => TeachingAccess::Members])->total());
        $this->assertSame(1, $this->repository->search(null, ['access' => 'members', 'course' => 'AGR 301'])->total());
        $this->assertSame(2, $this->repository->search('AGR 301')->total());
    }
}
