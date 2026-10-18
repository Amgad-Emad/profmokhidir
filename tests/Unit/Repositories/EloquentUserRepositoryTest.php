<?php

namespace Tests\Unit\Repositories;

use App\Constants\Roles;
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
        $this->assertSame('profmokhidir_testing', config('database.connections.mysql.database'));
    }

    #[Test]
    public function it_creates_finds_updates_and_deletes_users(): void
    {
        $user = $this->repository->create([
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => 'secret-password',
            'role' => Roles::EDITOR,
        ]);

        $this->assertSame($user->id, $this->repository->find($user->id)?->id);
        $this->assertSame($user->id, $this->repository->findByEmail('GRACE@example.com')?->id);

        $updated = $this->repository->update($user->id, ['name' => 'Rear Admiral Hopper']);
        $this->assertSame('Rear Admiral Hopper', $updated->name);

        $this->assertTrue($this->repository->delete($user));
        $this->assertNull($this->repository->find($user->id));
    }

    #[Test]
    public function it_filters_and_counts_by_role(): void
    {
        User::factory()->admin()->create();
        User::factory()->editor()->count(2)->create();
        User::factory()->member()->count(3)->create();

        $this->assertSame(1, $this->repository->countByRole(Roles::ADMIN));
        $this->assertCount(2, $this->repository->withRole(Roles::EDITOR));
        $this->assertSame(3, $this->repository->paginateByRole(Roles::MEMBER)->total());
        $this->assertSame(6, $this->repository->paginate()->total());
    }

    #[Test]
    public function published_helpers_require_a_published_scope(): void
    {
        $this->expectException(LogicException::class);

        $this->repository->published();
    }
}
