<?php

namespace Tests\Feature\Policies;

use App\Constants\Pages;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Page copy is edited by editors and admins (the "manage content"
 * permission); pages are fixed by the design, so nobody creates or deletes one.
 */
class PagePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function editors_and_admins_may_edit_and_publish_pages_and_nobody_else(): void
    {
        $page = Page::factory()->page(Pages::HOME)->create();

        foreach ([User::factory()->editor()->create(), User::factory()->admin()->create()] as $user) {
            foreach (['view', 'update', 'publish', 'manageMedia'] as $ability) {
                $this->assertTrue($user->can($ability, $page), $ability);
            }
            $this->assertTrue($user->can('viewAny', Page::class));
            $this->assertFalse($user->can('create', Page::class));
            $this->assertFalse($user->can('delete', $page));
        }

        foreach ([User::factory()->member()->create(), User::factory()->applicant()->create(), User::factory()->create()] as $user) {
            $this->assertFalse($user->can('viewAny', Page::class));
            $this->assertFalse($user->can('update', $page));
            $this->assertFalse($user->can('publish', $page));
        }
    }
}
