<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\CommunityStatus;
use App\Constants\Theme;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * @extends BaseEloquentRepository<User>
 */
class EloquentUserRepository extends BaseEloquentRepository implements UserRepositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', mb_strtolower(trim($email)))->first();
    }

    public function markEmailUnverified(User $user): User
    {
        $user->forceFill(['email_verified_at' => null])->save();

        return $user->refresh();
    }

    public function markEmailVerified(User $user): User
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->refresh();
    }

    public function updateTheme(User $user, Theme $theme): User
    {
        $user->forceFill(['theme' => $theme])->save();

        return $user->refresh();
    }

    public function dismissTwoFactorReminder(User $user): User
    {
        $user->forceFill(['two_factor_reminder_dismissed_at' => now()])->save();

        return $user->refresh();
    }

    public function countActiveWithRole(string $role, ?int $excludeId = null): int
    {
        try {
            return $this->query()
                ->role($role)
                ->whereNull('deactivated_at')
                ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
                ->count();
        } catch (RoleDoesNotExist) {
            return 0;
        }
    }

    public function search(?string $term = null, ?string $role = null, ?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) $term);

        try {
            return $this->query()
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%');
                }))
                ->when($role !== null, fn ($query) => $query->role($role))
                ->when($status !== null, fn ($query) => $query->communityStatus($status))
                ->with('roles')
                ->orderBy('name')
                ->orderBy('id')
                ->paginate($perPage);
        } catch (RoleDoesNotExist) {
            return $this->query()->whereRaw('1 = 0')->paginate($perPage);
        }
    }

    public function withRole(string $role): Collection
    {
        try {
            return $this->query()->role($role)->with('roles')->orderBy('name')->get();
        } catch (RoleDoesNotExist) {
            return new Collection;
        }
    }

    public function paginateByRole(?string $role = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->when($role !== null, fn ($query) => $query->role($role))
            ->with('roles')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function countByRole(string $role): int
    {
        return $this->query()->role($role)->count();
    }

    public function withCommunityStatus(CommunityStatus $status): Collection
    {
        return $this->query()->communityStatus($status)->with('roles')->orderBy('name')->get();
    }

    public function countByCommunityStatus(CommunityStatus $status): int
    {
        return $this->query()->communityStatus($status)->count();
    }

    public function paginateByCommunityStatus(?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->when($status !== null, fn ($query) => $query->communityStatus($status))
            ->with('roles')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
