<?php

namespace Tests\Unit\Services;

use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserRepositoryInterface&MockInterface $users;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = Mockery::mock(UserRepositoryInterface::class);
        $this->service = new UserService($this->users);
    }

    #[Test]
    public function it_creates_a_user_with_a_normalised_email_and_default_role(): void
    {
        $this->users->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'password' => 'secret-password',
                'role' => Roles::MEMBER,
            ])
            ->andReturn(new User);

        $this->service->create([
            'name' => 'Ada Lovelace',
            'email' => '  Ada@Example.com ',
            'password' => 'secret-password',
        ]);
    }

    #[Test]
    public function it_assigns_a_valid_role(): void
    {
        $user = new User;

        $this->users->shouldReceive('update')
            ->once()
            ->with($user, ['role' => Roles::EDITOR])
            ->andReturn($user);

        $this->assertSame($user, $this->service->assignRole($user, Roles::EDITOR));
    }

    #[Test]
    public function it_rejects_an_unknown_role(): void
    {
        $this->users->shouldNotReceive('update');

        $this->expectException(InvalidArgumentException::class);

        $this->service->assignRole(new User, 'superuser');
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
}
