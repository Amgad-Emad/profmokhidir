<?php

namespace Tests\Feature\Policies;

use App\Models\Album;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Models\Video;
use App\Policies\ContentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every content type resolves to a policy extending ContentPolicy: editors and
 * admins ("manage content") may do everything, members and applicants nothing.
 */
class ContentPoliciesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<class-string>
     */
    private function contentModels(): array
    {
        return [
            Album::class, Award::class, Commentary::class, CvEntry::class, OtherItem::class,
            Profile::class, Publication::class, TeachingMaterial::class, Video::class,
        ];
    }

    #[Test]
    public function every_content_model_has_a_content_policy(): void
    {
        foreach ($this->contentModels() as $model) {
            $policy = Gate::getPolicyFor($model);

            $this->assertInstanceOf(ContentPolicy::class, $policy, $model);
            $this->assertSame($model, $policy::MODEL, $model);
        }
    }

    #[Test]
    public function editors_and_admins_may_manage_every_content_type(): void
    {
        $editor = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();

        foreach ($this->contentModels() as $model) {
            $instance = $model::factory()->create();

            foreach ([$editor, $admin] as $user) {
                $this->assertTrue($user->can('viewAny', $model), "{$model} viewAny");
                $this->assertTrue($user->can('create', $model), "{$model} create");
                $this->assertTrue($user->can('reorder', $model), "{$model} reorder");
                $this->assertTrue($user->can('view', $instance), "{$model} view");
                $this->assertTrue($user->can('update', $instance), "{$model} update");
                $this->assertTrue($user->can('publish', $instance), "{$model} publish");
                $this->assertTrue($user->can('manageMedia', $instance), "{$model} manageMedia");
                $this->assertTrue($user->can('delete', $instance), "{$model} delete");
            }
        }
    }

    #[Test]
    public function members_applicants_and_plain_users_may_not_manage_content(): void
    {
        $users = [
            User::factory()->member()->create(),
            User::factory()->applicant()->create(),
            User::factory()->create(),
        ];

        foreach ($this->contentModels() as $model) {
            $instance = $model::factory()->create();

            foreach ($users as $user) {
                $this->assertFalse($user->can('viewAny', $model), "{$model} viewAny");
                $this->assertFalse($user->can('create', $model), "{$model} create");
                $this->assertFalse($user->can('update', $instance), "{$model} update");
                $this->assertFalse($user->can('delete', $instance), "{$model} delete");
                $this->assertFalse($user->can('publish', $instance), "{$model} publish");
            }
        }
    }

    #[Test]
    public function the_teaching_material_policy_keeps_its_download_rule(): void
    {
        $material = TeachingMaterial::factory()->published()->create();

        $this->assertTrue(Gate::forUser(User::factory()->editor()->create())->allows('download', $material));
        $this->assertTrue(Gate::allows('download', $material));
    }
}
