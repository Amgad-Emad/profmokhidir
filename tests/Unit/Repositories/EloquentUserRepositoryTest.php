<?php

namespace Tests\Unit\Repositories;

use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Constants\Theme;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(UserRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentUserRepository::class, $this->repository);
    }

    #[Test]
    public function it_runs_against_the_testing_database(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertStringStartsWith('profmokhidir_testing', config('database.connections.mysql.database'));
    }

    #[Test]
    public function it_creates_finds_updates_and_deletes_users(): void
    {
        $user = $this->repository->create([
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertSame($user->id, $this->repository->find($user->id)?->id);
        $this->assertSame($user->id, $this->repository->findByEmail('GRACE@example.com')?->id);
        $this->assertSame(CommunityStatus::None, $user->fresh()->community_status);

        $updated = $this->repository->update($user->id, ['name' => 'Rear Admiral Hopper']);
        $this->assertSame('Rear Admiral Hopper', $updated->name);

        $this->assertTrue($this->repository->delete($user));
        $this->assertNull($this->repository->find($user->id));
    }

    #[Test]
    public function it_filters_and_counts_by_spatie_role(): void
    {
        User::factory()->admin()->create();
        User::factory()->editor()->count(2)->create();
        User::factory()->member()->count(3)->create();
        User::factory()->create(); // no role

        $this->assertSame(1, $this->repository->countByRole(Roles::ADMIN));
        $this->assertCount(2, $this->repository->withRole(Roles::EDITOR));
        $this->assertCount(0, $this->repository->withRole('no-such-role'));
        $this->assertSame(3, $this->repository->paginateByRole(Roles::MEMBER)->total());
        $this->assertSame(7, $this->repository->paginate()->total());
        $this->assertSame(7, $this->repository->paginateByRole()->total());

        $editors = $this->repository->withRole(Roles::EDITOR);
        $this->assertTrue($editors->first()->relationLoaded('roles'));
        $this->assertTrue($editors->every(fn (User $user): bool => $user->hasRole(Roles::EDITOR)));
    }

    #[Test]
    public function it_filters_and_counts_by_community_status(): void
    {
        User::factory()->member()->count(2)->create();
        User::factory()->applicant()->count(3)->create();
        User::factory()->rejectedApplicant()->create();

        $this->assertCount(2, $this->repository->withCommunityStatus(CommunityStatus::Approved));
        $this->assertSame(3, $this->repository->countByCommunityStatus(CommunityStatus::Pending));
        $this->assertSame(1, $this->repository->countByCommunityStatus(CommunityStatus::Rejected));
        $this->assertSame(3, $this->repository->paginateByCommunityStatus(CommunityStatus::Pending)->total());
        $this->assertSame(6, $this->repository->paginateByCommunityStatus()->total());
    }

    #[Test]
    public function published_helpers_require_a_published_scope(): void
    {
        $this->expectException(LogicException::class);

        $this->repository->published();
    }

    #[Test]
    public function mark_email_unverified_clears_the_timestamp(): void
    {
        $user = User::factory()->create();

        $user = $this->repository->markEmailUnverified($user);

        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function update_theme_keeps_the_colour_theme_choice(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->theme, 'nothing chosen yet: the device decides');

        $updated = $this->repository->updateTheme($user, Theme::Dark);

        $this->assertSame(Theme::Dark, $updated->theme);
        $this->assertSame(Theme::Dark, $user->fresh()->theme);

        $this->repository->updateTheme($user, Theme::Light);
        $this->assertSame(Theme::Light, $user->fresh()->theme);
    }

    #[Test]
    public function it_remembers_that_the_two_factor_reminder_was_dismissed(): void
    {
        $user = User::factory()->admin()->create();
        $this->assertNull($user->two_factor_reminder_dismissed_at);

        $dismissed = $this->repository->dismissTwoFactorReminder($user);

        $this->assertNotNull($dismissed->two_factor_reminder_dismissed_at);
        $this->assertNotNull($user->fresh()->two_factor_reminder_dismissed_at);
    }
}
