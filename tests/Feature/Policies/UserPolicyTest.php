<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_policy_is_registered(): void
    {
        $this->assertInstanceOf(UserPolicy::class, Gate::getPolicyFor(User::class));
    }

    #[Test]
    public function only_admins_manage_users_and_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $subject = User::factory()->member()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('view', $subject));
        $this->assertTrue($admin->can('update', $subject));
        $this->assertTrue($admin->can('delete', $subject));
        $this->assertTrue($admin->can('assignRole', $subject));

        $this->assertFalse($editor->can('viewAny', User::class));
        $this->assertFalse($editor->can('create', User::class));
        $this->assertFalse($editor->can('view', $subject));
        $this->assertFalse($editor->can('update', $subject));
        $this->assertFalse($editor->can('delete', $subject));
        $this->assertFalse($editor->can('assignRole', $subject));
    }

    #[Test]
    public function only_admins_review_community_applications(): void
    {
        $applicant = User::factory()->applicant()->create();

        $this->assertTrue(User::factory()->admin()->create()->can('review', $applicant));
        $this->assertFalse(User::factory()->editor()->create()->can('review', $applicant));
        $this->assertFalse(User::factory()->member()->create()->can('review', $applicant));
    }

    #[Test]
    public function everyone_may_view_and_update_their_own_account_but_not_delete_or_rerole_it(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->editor()->create(), User::factory()->member()->create()] as $user) {
            $this->assertTrue($user->can('view', $user));
            $this->assertTrue($user->can('update', $user));
            $this->assertFalse($user->can('delete', $user));
            $this->assertFalse($user->can('assignRole', $user));
            $this->assertFalse($user->can('review', $user));
        }
    }
}
