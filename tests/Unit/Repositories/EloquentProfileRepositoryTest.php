<?php

namespace Tests\Unit\Repositories;

use App\Constants\ContentStatus;
use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Eloquent\EloquentProfileRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentProfileRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProfileRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ProfileRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentProfileRepository::class, $this->repository);
    }

    #[Test]
    public function current_returns_the_single_row_or_null(): void
    {
        $this->assertNull($this->repository->current());

        $profile = Profile::factory()->draft()->create();

        $this->assertTrue($profile->is($this->repository->current()));
        $this->assertTrue($this->repository->current()->relationLoaded('media'));
    }

    #[Test]
    public function current_published_ignores_a_draft_profile(): void
    {
        Profile::factory()->draft()->create();

        $this->assertNull($this->repository->currentPublished());

        Profile::factory()->published()->create();

        $this->assertNotNull($this->repository->currentPublished());
        $this->assertSame(ContentStatus::Published, $this->repository->currentPublished()->status);
    }

    #[Test]
    public function save_creates_the_row_then_updates_it(): void
    {
        $created = $this->repository->save(['name' => 'Mohamed Osman Khidir', 'title' => 'Professor']);

        $this->assertSame(1, Profile::query()->count());

        $updated = $this->repository->save(['title' => 'Professor Emeritus', 'expertise' => ['Agronomy']]);

        $this->assertSame(1, Profile::query()->count());
        $this->assertTrue($created->is($updated));
        $this->assertSame('Professor Emeritus', $updated->title);
        $this->assertSame(['Agronomy'], $updated->expertise);
        $this->assertSame('Mohamed Osman Khidir', $updated->name);
    }
}
