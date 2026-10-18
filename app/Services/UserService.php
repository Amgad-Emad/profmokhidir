<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class UserService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /**
     * Create a user. The password is hashed by the model cast; the role defaults to MEMBER.
     *
     * @param  array{name: string, email: string, password: string, role?: string}  $data
     */
    public function create(array $data): User
    {
        $role = $data['role'] ?? Roles::DEFAULT;
        $this->guardRole($role);

        return $this->users->create([
            'name' => $data['name'],
            'email' => mb_strtolower(trim($data['email'])),
            'password' => $data['password'],
            'role' => $role,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        if (array_key_exists('role', $data)) {
            $this->guardRole((string) $data['role']);
        }

        if (array_key_exists('email', $data)) {
            $data['email'] = mb_strtolower(trim((string) $data['email']));
        }

        return $this->users->update($user, $data);
    }

    public function assignRole(User $user, string $role): User
    {
        $this->guardRole($role);

        return $this->users->update($user, ['role' => $role]);
    }

    public function delete(User $user): bool
    {
        return $this->users->delete($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    /**
     * @return Collection<int, User>
     */
    public function staff(): Collection
    {
        return $this->users->withRole(Roles::ADMIN)
            ->merge($this->users->withRole(Roles::EDITOR))
            ->sortBy('name')
            ->values();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $role = null, int $perPage = 15): LengthAwarePaginator
    {
        if ($role !== null) {
            $this->guardRole($role);
        }

        return $this->users->paginateByRole($role, $perPage);
    }

    private function guardRole(string $role): void
    {
        if (! Roles::isValid($role)) {
            throw new InvalidArgumentException(sprintf('Unknown role "%s".', $role));
        }
    }
}
