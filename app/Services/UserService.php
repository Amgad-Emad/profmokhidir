<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Create a user and give them a Spatie role (APPLICANT unless told otherwise).
     * The password is hashed by the model cast.
     *
     * @param  array{name: string, email: string, password: string, role?: string}  $data
     */
    public function create(array $data, ?User $actor = null): User
    {
        $role = $data['role'] ?? Roles::DEFAULT;
        $this->guardRole($role);

        $user = $this->users->create([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'password' => $data['password'],
        ]);

        $this->roles->assign($user, $role, $actor);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        unset($data['role'], $data['roles']);

        if (array_key_exists('email', $data)) {
            $data['email'] = mb_strtolower(trim((string) $data['email']));
        }

        return $this->users->update($user, $data);
    }

    /**
     * Change the user's own name and email. A new email address must be verified
     * again before the account can be used.
     */
    public function updateProfile(User $user, string $name, string $email): User
    {
        $email = mb_strtolower(trim($email));
        $emailChanged = $email !== $user->email;

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $user = $this->users->markEmailUnverified($user);
        }

        $user = $this->users->update($user, ['name' => trim($name), 'email' => $email]);

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * Set a new password (hashed by the model cast).
     */
    public function updatePassword(User $user, string $password): User
    {
        return $this->users->update($user, ['password' => $password]);
    }

    public function assignRole(User $user, string $role, ?User $actor = null): User
    {
        $this->guardRole($role);

        return $this->roles->assign($user, $role, $actor);
    }

    public function removeRole(User $user, string $role, ?User $actor = null): User
    {
        $this->guardRole($role);

        return $this->roles->remove($user, $role, $actor);
    }

    /**
     * @param  list<string>  $roles
     */
    public function syncRoles(User $user, array $roles, ?User $actor = null): User
    {
        foreach ($roles as $role) {
            $this->guardRole($role);
        }

        return $this->roles->sync($user, $roles, $actor);
    }

    public function delete(User $user, ?User $actor = null): bool
    {
        $this->audit->record($actor, AuditAction::USER_DELETED, null, [
            'user_id' => $user->getKey(),
            'email' => $user->email,
        ]);

        return $this->users->delete($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    /**
     * Admins and editors, by name.
     *
     * @return Collection<int, User>
     */
    public function staff(): Collection
    {
        return $this->users->withRole(Roles::ADMIN)
            ->merge($this->users->withRole(Roles::EDITOR))
            ->unique('id')
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
