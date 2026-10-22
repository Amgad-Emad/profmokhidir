<?php

namespace Tests\Unit\Repositories;

use App\Constants\RevisionKind;
use App\Models\Award;
use App\Models\Publication;
use App\Models\Revision;
use App\Models\User;
use App\Repositories\Contracts\RevisionRepositoryInterface;
use App\Repositories\Eloquent\EloquentRevisionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentRevisionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RevisionRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(RevisionRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentRevisionRepository::class, $this->repository);
    }

    #[Test]
    public function record_stores_the_record_the_author_the_kind_and_the_values(): void
    {
        $editor = User::factory()->editor()->create();
        $publication = Publication::factory()->create();

        $revision = $this->repository->record($publication, $editor->getKey(), RevisionKind::Autosave, ['title' => 'Draft title']);

        $this->assertSame($publication->getMorphClass(), $revision->revisionable_type);
        $this->assertSame($publication->getKey(), $revision->revisionable_id);
        $this->assertSame($editor->getKey(), $revision->user_id);
        $this->assertSame(RevisionKind::Autosave, $revision->fresh()->kind);
        $this->assertSame(['title' => 'Draft title'], $revision->fresh()->data);
        $this->assertNotNull($revision->created_at);
    }

    #[Test]
    public function history_is_per_record_newest_first_with_the_author(): void
    {
        $publication = Publication::factory()->create();
        $other = Award::factory()->create();
        $older = Revision::factory()->of($publication)->create(['created_at' => now()->subHour()]);
        $newer = Revision::factory()->of($publication)->create(['created_at' => now()]);
        Revision::factory()->of($other)->create();

        $history = $this->repository->forRecord($publication);

        $this->assertSame([$newer->getKey(), $older->getKey()], $history->modelKeys());
        $this->assertTrue($history->first()->relationLoaded('user'));
        $this->assertSame($newer->getKey(), $this->repository->latestFor($publication)?->getKey());
        $this->assertSame(2, $this->repository->countFor($publication));
        $this->assertSame(1, $this->repository->countFor($other));
    }

    #[Test]
    public function a_revision_is_only_found_through_its_own_record(): void
    {
        $publication = Publication::factory()->create();
        $other = Publication::factory()->create();
        $revision = Revision::factory()->of($other)->create();

        $this->assertNull($this->repository->findForRecord($publication, $revision->getKey()));
        $this->assertSame($revision->getKey(), $this->repository->findForRecord($other, $revision->getKey())?->getKey());
    }

    #[Test]
    public function prune_keeps_only_the_newest_revisions_of_the_record(): void
    {
        $publication = Publication::factory()->create();
        $other = Publication::factory()->create();

        foreach (range(1, 5) as $minutes) {
            Revision::factory()->of($publication)->create(['created_at' => now()->subMinutes(10 - $minutes)]);
        }
        Revision::factory()->of($other)->create();

        $this->assertSame(3, $this->repository->prune($publication, 2));
        $this->assertSame(2, $this->repository->countFor($publication));
        $this->assertSame(1, $this->repository->countFor($other));
    }
}
