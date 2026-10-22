<?php

namespace Tests\Unit\Services;

use App\Constants\AdminUx;
use App\Constants\RevisionKind;
use App\Models\Publication;
use App\Models\Revision;
use App\Models\User;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevisionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RevisionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(RevisionService::class);
    }

    #[Test]
    public function a_save_is_recorded_with_its_author_but_an_identical_one_is_not_repeated(): void
    {
        $editor = User::factory()->editor()->create();
        $publication = Publication::factory()->create();

        $first = $this->service->record($publication, ['title' => 'One'], RevisionKind::Save, $editor);
        $same = $this->service->record($publication, ['title' => 'One'], RevisionKind::Autosave, $editor);
        $next = $this->service->record($publication, ['title' => 'Two'], RevisionKind::Autosave, $editor);

        $this->assertNotNull($first);
        $this->assertNull($same);
        $this->assertNotNull($next);
        $this->assertSame(2, $this->service->count($publication));
        $this->assertSame($editor->getKey(), $first->user_id);
    }

    #[Test]
    public function publishing_is_always_recorded_even_without_changes(): void
    {
        $publication = Publication::factory()->create();

        $this->service->record($publication, ['title' => 'One'], RevisionKind::Save);
        $published = $this->service->record($publication, ['title' => 'One'], RevisionKind::Publish);

        $this->assertNotNull($published);
        $this->assertSame(RevisionKind::Publish, $this->service->history($publication)->first()->kind);
    }

    #[Test]
    public function only_the_newest_revisions_are_kept(): void
    {
        $publication = Publication::factory()->create();

        foreach (range(1, AdminUx::REVISIONS_KEPT + 3) as $index) {
            $this->service->record($publication, ['title' => 'Version '.$index], RevisionKind::Autosave);
        }

        $this->assertSame(AdminUx::REVISIONS_KEPT, $this->service->count($publication));
        $this->assertSame(['title' => 'Version '.(AdminUx::REVISIONS_KEPT + 3)], $this->service->history($publication, 1)->first()->data);
    }

    #[Test]
    public function data_for_returns_a_revisions_values_only_for_its_own_record(): void
    {
        $publication = Publication::factory()->create();
        $other = Publication::factory()->create();
        $revision = Revision::factory()->of($publication)->create(['data' => ['title' => 'Earlier']]);

        $this->assertSame(['title' => 'Earlier'], $this->service->dataFor($publication, $revision->getKey()));
        $this->assertNull($this->service->dataFor($other, $revision->getKey()));
    }
}
