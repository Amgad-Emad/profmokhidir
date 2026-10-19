<?php

namespace Tests\Feature\Policies;

use App\Models\Tribute;
use App\Models\User;
use App\Policies\TributePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_policy_is_registered(): void
    {
        $this->assertInstanceOf(TributePolicy::class, Gate::getPolicyFor(Tribute::class));
    }

    #[Test]
    public function editors_and_admins_moderate_tributes_but_never_create_them(): void
    {
        $tribute = Tribute::factory()->pending()->create();

        foreach ([User::factory()->editor()->create(), User::factory()->admin()->create()] as $user) {
            $this->assertTrue($user->can('viewAny', Tribute::class));
            $this->assertTrue($user->can('view', $tribute));
            $this->assertTrue($user->can('update', $tribute));
            $this->assertTrue($user->can('approve', $tribute));
            $this->assertTrue($user->can('reject', $tribute));
            $this->assertTrue($user->can('delete', $tribute));
            $this->assertTrue($user->can('reorder', Tribute::class));
            $this->assertFalse($user->can('create', Tribute::class));
        }
    }

    #[Test]
    public function members_and_applicants_may_not_moderate(): void
    {
        $tribute = Tribute::factory()->pending()->create();

        foreach ([User::factory()->member()->create(), User::factory()->applicant()->create(), User::factory()->create()] as $user) {
            $this->assertFalse($user->can('viewAny', Tribute::class));
            $this->assertFalse($user->can('approve', $tribute));
            $this->assertFalse($user->can('reject', $tribute));
            $this->assertFalse($user->can('delete', $tribute));
        }
    }
}
