<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Constants\Locales;
use App\Constants\Roles;
use App\Exceptions\UserManagementException;
use App\Models\User;
use App\Notifications\StaffInvitation;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Accounts: creation, the staff invitation flow (set-password link through
 * the password broker, never a password in an email), role changes,
 * deactivation and the safeguards around administrators. Every change is
 * audited; roles are only ever touched through RoleService.
 */
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
        private readonly PasswordBrokerFactory $passwords,
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
            ...(Locales::isValid($data['locale'] ?? null) ? [Locales::COLUMN => $data['locale']] : []),
        ]);

        $this->roles->assign($user, $role, $actor);

        return $user;
    }

    /**
     * Invite an administrator or editor: the account is created with an
     * unusable random password and an unverified email, and the person
     * receives a set-password link (in the chosen language, else English).
     * Using the link verifies the email.
     *
     * @param  array{name: string, email: string, role: string, locale?: string|null}  $data
     */
    public function invite(array $data, ?User $actor = null): User
    {
        $this->guardStaffRole($data['role']);

        $user = $this->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Str::password(40),
            'role' => $data['role'],
            'locale' => $data['locale'] ?? null,
        ], $actor);

        $this->audit->record($actor, AuditAction::USER_INVITED, $user, ['email' => $user->email, 'role' => $data['role']]);
        $this->sendInvitation($user);

        return $user;
    }

    /**
     * Send a fresh set-password link to someone who has not used theirs yet.
     */
    public function resendInvitation(User $user, ?User $actor = null): User
    {
        if ($user->hasVerifiedEmail()) {
            throw UserManagementException::invitationAlreadyAccepted();
        }

        $this->audit->record($actor, AuditAction::USER_INVITE_RESENT, $user, ['email' => $user->email]);
        $this->sendInvitation($user);

        return $user;
    }

    /**
     * Whether the person still has to use their invitation link.
     */
    public function hasPendingInvitation(User $user): bool
    {
        return $this->isStaff($user) && ! $user->hasVerifiedEmail();
    }

    /**
     * Set a user's staff role (admin or editor), or none to withdraw dashboard
     * access while keeping any community role. Administrators cannot change
     * their own role and the last active administrator cannot be demoted.
     */
    public function changeStaffRole(User $user, ?string $staffRole, User $actor): User
    {
        if ($staffRole !== null) {
            $this->guardStaffRole($staffRole);
        }

        if ($actor->is($user)) {
            throw UserManagementException::cannotChangeOwnRole();
        }

        $current = $this->roles->rolesOf($user);

        if (in_array(Roles::ADMIN, $current, true) && $staffRole !== Roles::ADMIN && $this->isLastActiveAdmin($user)) {
            throw UserManagementException::lastActiveAdmin();
        }

        $roles = array_values(array_diff($current, Roles::staff()));

        if ($staffRole !== null) {
            $roles[] = $staffRole;
        }

        return $this->roles->sync($user, array_values(array_unique($roles)), $actor);
    }

    /**
     * Deactivated accounts cannot sign in. Nobody can deactivate themselves,
     * and the last active administrator cannot be deactivated.
     */
    public function deactivate(User $user, User $actor): User
    {
        if ($actor->is($user)) {
            throw UserManagementException::cannotDeactivateSelf();
        }

        if (! $user->isActive()) {
            return $user;
        }

        if ($this->isLastActiveAdmin($user)) {
            throw UserManagementException::lastActiveAdmin();
        }

        $user = $this->users->update($user, ['deactivated_at' => now()]);
        $this->audit->record($actor, AuditAction::USER_DEACTIVATED, $user, ['email' => $user->email]);

        return $user;
    }

    public function reactivate(User $user, ?User $actor = null): User
    {
        if ($user->isActive()) {
            return $user;
        }

        $user = $this->users->update($user, ['deactivated_at' => null]);
        $this->audit->record($actor, AuditAction::USER_REACTIVATED, $user, ['email' => $user->email]);

        return $user;
    }

    /**
     * True when no other active administrator exists.
     */
    public function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isActive() || ! in_array(Roles::ADMIN, $this->roles->rolesOf($user), true)) {
            return false;
        }

        return $this->users->countActiveWithRole(Roles::ADMIN, (int) $user->getKey()) === 0;
    }

    public function countActiveAdmins(): int
    {
        return $this->users->countActiveWithRole(Roles::ADMIN);
    }

    public function isStaff(User $user): bool
    {
        return array_intersect(Roles::staff(), $this->roles->rolesOf($user)) !== [];
    }

    /**
     * The user's staff role (admin or editor), if any.
     */
    public function staffRoleOf(User $user): ?string
    {
        $roles = $this->roles->rolesOf($user);

        foreach (Roles::staff() as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return null;
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

    /**
     * A password set through a reset or invitation link proves the address
     * works, so an unverified email is marked verified at the same time.
     */
    public function resetPassword(User $user, string $password): User
    {
        $user = $this->updatePassword($user, $password);

        if (! $user->hasVerifiedEmail()) {
            $user = $this->users->markEmailVerified($user);
        }

        return $user;
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

    public function find(int $id): ?User
    {
        return $this->users->find($id);
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

    /**
     * The Users & roles list.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function search(?string $term = null, ?string $role = null, ?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        if ($role !== null) {
            $this->guardRole($role);
        }

        return $this->users->search($term, $role, $status, $perPage);
    }

    /**
     * An admin list of accounts (Users & roles, the community screen): search and
     * UserRepositoryInterface::FILTER_* criteria.
     *
     * @param  array<string, string|list<string>>  $criteria
     * @return LengthAwarePaginator<int, User>
     */
    public function filter(?string $term, array $criteria, int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator
    {
        return $this->users->filter($term, $criteria, $perPage, $sortBy, $sortDirection);
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     */
    public function countFiltered(?string $term, array $criteria): int
    {
        return $this->users->countFiltered($term, $criteria);
    }

    private function sendInvitation(User $user): void
    {
        $token = $this->passwords->broker()->createToken($user);

        $user->notify(new StaffInvitation($token, $this->staffRoleOf($user)));
    }

    private function guardRole(string $role): void
    {
        if (! Roles::isValid($role)) {
            throw new InvalidArgumentException(sprintf('Unknown role "%s".', $role));
        }
    }

    private function guardStaffRole(string $role): void
    {
        $this->guardRole($role);

        if (! in_array($role, Roles::staff(), true)) {
            throw UserManagementException::notStaffRole($role);
        }
    }
}
