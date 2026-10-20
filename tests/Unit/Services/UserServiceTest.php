<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserRepositoryInterface&MockInterface $users;

    private RoleService&MockInterface $roles;

    private AuditLogService&MockInterface $audit;

    private PasswordBrokerFactory&MockInterface $passwords;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = Mockery::mock(UserRepositoryInterface::class);
        $this->roles = Mockery::mock(RoleService::class);
        $this->audit = Mockery::mock(AuditLogService::class);
        $this->passwords = Mockery::mock(PasswordBrokerFactory::class);
        $this->service = new UserService($this->users, $this->roles, $this->audit, $this->passwords);
    }

    #[Test]
    public function it_creates_a_user_with_a_normalised_email_and_the_default_role(): void
    {
        $user = new User;

        $this->users->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'password' => 'secret-password',
            ])
            ->andReturn($user);

        $this->roles->shouldReceive('assign')->once()->with($user, Roles::APPLICANT, null)->andReturn($user);

        $this->assertSame($user, $this->service->create([
            'name' => ' Ada Lovelace ',
            'email' => '  Ada@Example.com ',
            'password' => 'secret-password',
        ]));
    }

    #[Test]
    public function it_creates_a_user_with_an_explicit_role_and_actor(): void
    {
        $user = new User;
        $actor = new User;

        $this->users->shouldReceive('create')->once()->andReturn($user);
        $this->roles->shouldReceive('assign')->once()->with($user, Roles::EDITOR, $actor)->andReturn($user);

        $this->service->create([
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'secret-password',
            'role' => Roles::EDITOR,
        ], $actor);
    }

    #[Test]
    public function it_rejects_an_unknown_role_on_create(): void
    {
        $this->users->shouldNotReceive('create');

        $this->expectException(InvalidArgumentException::class);

        $this->service->create([
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'secret-password',
            'role' => 'owner',
        ]);
    }

    #[Test]
    public function it_assigns_and_removes_valid_roles_through_the_role_service(): void
    {
        $user = new User;

        $this->roles->shouldReceive('assign')->once()->with($user, Roles::EDITOR, null)->andReturn($user);
        $this->roles->shouldReceive('remove')->once()->with($user, Roles::EDITOR, null)->andReturn($user);

        $this->assertSame($user, $this->service->assignRole($user, Roles::EDITOR));
        $this->assertSame($user, $this->service->removeRole($user, Roles::EDITOR));
    }

    #[Test]
    public function it_rejects_an_unknown_role_on_assign_and_sync(): void
    {
        $this->roles->shouldNotReceive('assign');
        $this->roles->shouldNotReceive('sync');

        try {
            $this->service->assignRole(new User, 'superuser');
            $this->fail('Expected exception');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $this->service->syncRoles(new User, [Roles::ADMIN, 'superuser']);
    }

    #[Test]
    public function it_syncs_valid_roles(): void
    {
        $user = new User;
        $this->roles->shouldReceive('sync')->once()->with($user, [Roles::ADMIN, Roles::MEMBER], null)->andReturn($user);

        $this->assertSame($user, $this->service->syncRoles($user, [Roles::ADMIN, Roles::MEMBER]));
    }

    #[Test]
    public function update_strips_role_keys_and_normalises_the_email(): void
    {
        $user = new User;

        $this->users->shouldReceive('update')
            ->once()
            ->with($user, ['name' => 'New', 'email' => 'new@example.com'])
            ->andReturn($user);

        $this->service->update($user, [
            'name' => 'New',
            'email' => ' NEW@Example.com ',
            'role' => Roles::ADMIN,
            'roles' => [Roles::ADMIN],
        ]);
    }

    #[Test]
    public function delete_records_an_audit_entry(): void
    {
        $user = new User(['email' => 'gone@example.com']);
        $user->id = 42;
        $actor = new User;

        $this->audit->shouldReceive('record')
            ->once()
            ->with($actor, AuditAction::USER_DELETED, null, ['user_id' => 42, 'email' => 'gone@example.com']);
        $this->users->shouldReceive('delete')->once()->with($user)->andReturn(true);

        $this->assertTrue($this->service->delete($user, $actor));
    }

    #[Test]
    public function staff_merges_admins_and_editors_sorted_by_name(): void
    {
        $admin = new User(['name' => 'Zed']);
        $admin->id = 1;
        $editor = new User(['name' => 'Amy']);
        $editor->id = 2;

        $this->users->shouldReceive('withRole')->with(Roles::ADMIN)->once()->andReturn(new Collection([$admin]));
        $this->users->shouldReceive('withRole')->with(Roles::EDITOR)->once()->andReturn(new Collection([$editor, $admin]));

        $staff = $this->service->staff();

        $this->assertSame(['Amy', 'Zed'], $staff->pluck('name')->all());
    }

    #[Test]
    public function paginate_guards_the_role(): void
    {
        $this->users->shouldNotReceive('paginateByRole');

        $this->expectException(InvalidArgumentException::class);

        $this->service->paginate('nobody');
    }

    #[Test]
    public function find_by_email_delegates(): void
    {
        $user = new User;
        $this->users->shouldReceive('findByEmail')->once()->with('a@b.c')->andReturn($user);

        $this->assertSame($user, $this->service->findByEmail('a@b.c'));
    }
}
